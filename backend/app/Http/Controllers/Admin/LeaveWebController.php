<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAdjustment;
use App\Models\AuditLog;
use App\Models\DayStart;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\DataScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveWebController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    /**
     * Display list of leave applications with filtering.
     */
    public function index(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('leaves');

        $query = Leave::with(['user', 'leaveType', 'approvedBy']);

        if ($scopedUserIds !== null) {
            $query->whereIn('user_id', $scopedUserIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            if ($scopedUserIds === null || in_array($request->user_id, $scopedUserIds)) {
                $query->where('user_id', $request->user_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->where(function ($q) use ($month) {
                $q->whereMonth('from_date', $month->month)->whereYear('from_date', $month->year)
                  ->orWhere(fn ($q2) => $q2->whereMonth('to_date', $month->month)->whereYear('to_date', $month->year));
            });
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(15);
        $users  = $scopedUserIds === null
            ? User::where('is_active', true)->get()
            : User::whereIn('id', $scopedUserIds)->where('is_active', true)->get();

        // Summary counts (scoped)
        $countQuery    = Leave::query()->when($scopedUserIds !== null, fn($q) => $q->whereIn('user_id', $scopedUserIds));
        $pendingCount  = (clone $countQuery)->where('status', 'pending')->count();
        $approvedCount = (clone $countQuery)->where('status', 'approved')->count();
        $rejectedCount = (clone $countQuery)->where('status', 'rejected')->count();
        $conflictCount = (clone $countQuery)->where('has_conflict', true)->where('conflict_status', 'pending_review')->count();

        // Conflicts query (scoped)
        $conflictTab = $request->input('conflict_tab', 'pending');
        $conflicts = Leave::with(['user', 'leaveType', 'resolvedBy'])
            ->where('has_conflict', true)
            ->when(!empty($scopedUserIds), fn($q) => $q->whereIn('user_id', $scopedUserIds))
            ->when($conflictTab === 'resolved', fn ($q) => $q->where('conflict_status', 'resolved'))
            ->when($conflictTab !== 'resolved', fn ($q) => $q->where('conflict_status', 'pending_review'))
            ->orderBy('conflict_date', 'desc')
            ->get();

        return view('admin.leave.index', compact(
            'leaves', 'users', 'pendingCount', 'approvedCount', 'rejectedCount', 'conflictCount', 'conflicts', 'conflictTab'
        ));
    }

    /**
     * Approve leave application.
     */
    public function approve(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', 'Leave request has already been processed.');
        }

        // Guard 2: Verify employee has enough available balance before approving
        if ($leave->leaveType && $leave->leaveType->days_per_year > 0) {
            $year = $leave->from_date->year;
            $balances = $this->attendance->getOrCreateBalances($leave->user_id, $year);
            $balance = $balances->firstWhere('leave_type_id', $leave->leave_type_id);
            $available = $balance ? $balance->available : 0;

            if ($leave->days_count > $available) {
                return back()->with('error', "Cannot approve request. Employee only has {$available} day(s) remaining for {$leave->leaveType->name}, but this request requires {$leave->days_count} day(s).");
            }
        }

        DB::transaction(function () use ($leave) {
            $leave->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->attendance->deductBalance(
                $leave->user_id,
                $leave->leave_type_id,
                $leave->days_count,
                $leave->from_date->year
            );
        });

        return back()->with('success', 'Leave application approved successfully.');
    }

    /**
     * Reject leave application with reason.
     */
    public function reject(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'Leave request has already been processed.');
        }

        $leave->update([
            'status'          => 'rejected',
            'approved_by'     => auth()->id(),
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        return back()->with('success', 'Leave application rejected successfully.');
    }

    /**
     * Cancel an approved leave application (restores/refunds deducted balance).
     */
    public function cancelApproved(Request $request, Leave $leave)
    {
        if (! in_array($leave->status, ['approved', 'cancellation_requested'], true)) {
            return back()->with('error', 'Only approved or cancellation-requested leaves can be cancelled.');
        }

        DB::transaction(function () use ($leave) {
            $leave->update([
                'status' => 'cancelled',
            ]);

            $this->attendance->restoreBalance(
                $leave->user_id,
                $leave->leave_type_id,
                $leave->days_count,
                $leave->from_date->year
            );
        });

        return back()->with('success', "Leave cancelled successfully. {$leave->days_count} day(s) refunded to employee balance.");
    }

    /**
     * Resolve an attendance conflict via Web Admin Panel.
     */
    public function resolveConflict(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'resolved_action' => 'required|in:marked_present,kept_as_leave',
            'resolution_note' => 'required|string|min:3|max:1000',
        ]);

        if ($leave->conflict_status === 'resolved') {
            return back()->with('error', 'This conflict has already been resolved.');
        }

        DB::transaction(function () use ($leave, $validated, $request) {
            $user = $request->user();

            if ($validated['resolved_action'] === 'marked_present') {
                AttendanceAdjustment::updateOrCreate(
                    [
                        'user_id' => $leave->user_id,
                        'date'    => $leave->conflict_date,
                    ],
                    [
                        'adjusted_by' => $user->id,
                        'old_status'  => 'on_leave',
                        'new_status'  => 'present',
                        'reason'      => $validated['resolution_note'],
                    ]
                );

                $refundDays = $leave->duration === 'full' ? 1.0 : 0.5;
                $this->attendance->restoreBalance(
                    $leave->user_id,
                    $leave->leave_type_id,
                    $refundDays,
                    Carbon::parse($leave->conflict_date)->year
                );
            }

            $oldValues = [
                'conflict_status' => $leave->conflict_status,
                'resolved_action' => $leave->resolved_action,
            ];

            $leave->update([
                'conflict_status' => 'resolved',
                'resolved_by'     => $user->id,
                'resolved_action' => $validated['resolved_action'],
                'resolved_at'     => now(),
                'resolution_note' => $validated['resolution_note'],
            ]);

            AuditLog::logEvent(
                event: 'conflict_resolved',
                auditable: $leave,
                oldValues: $oldValues,
                newValues: [
                    'conflict_status' => 'resolved',
                    'resolved_action' => $validated['resolved_action'],
                    'resolution_note' => $validated['resolution_note'],
                ],
                reason: $validated['resolution_note'],
                causerUserId: $user->id
            );
        });

        return back()->with('success', 'Attendance conflict resolved successfully.');
    }
}
