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
use Yajra\DataTables\Facades\DataTables;

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

        if ($request->filled('date_from')) {
            $query->whereDate('to_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('from_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('leaveType', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('rejected_reason', 'like', "%{$search}%");
            });
        }

        $leaves = $query->with(['user.roles', 'leaveType'])->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $users  = User::with('roles')
            ->where('is_active', true)
            ->when($scopedUserIds !== null, fn($q) => $q->whereIn('id', $scopedUserIds))
            ->orderBy('name')
            ->get();

        // Summary counts (scoped)
        $countQuery    = Leave::query()->when($scopedUserIds !== null, fn($q) => $q->whereIn('user_id', $scopedUserIds));
        $pendingCount  = (clone $countQuery)->where('status', 'pending')->count();
        $approvedCount = (clone $countQuery)->where('status', 'approved')->count();
        $rejectedCount = (clone $countQuery)->where('status', 'rejected')->count();
        $conflictCount = (clone $countQuery)->where('has_conflict', true)->where('conflict_status', 'pending_review')->count();

        // Conflicts query (scoped)
        $conflictTab = $request->input('conflict_tab', 'pending');
        $conflictsQuery = Leave::with(['user.roles', 'leaveType', 'resolvedBy.roles'])
            ->where('has_conflict', true)
            ->when(!empty($scopedUserIds), fn($q) => $q->whereIn('user_id', $scopedUserIds))
            ->when($conflictTab === 'resolved', fn ($q) => $q->where('conflict_status', 'resolved'))
            ->when($conflictTab !== 'resolved', fn ($q) => $q->where('conflict_status', 'pending_review'));

        $search = $request->input('search_custom');
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }

        if (!empty($search) && is_string($search)) {
            $conflictsQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('leaveType', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhere('resolution_note', 'like', "%{$search}%");
            });
        }

        $conflicts = $conflictsQuery->orderBy('conflict_date', 'desc')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'records' => $leaves->map(function ($leave) {
                    return [
                        'id'                  => $leave->id,
                        'user_id'             => $leave->user_id,
                        'user_name'           => $leave->user ? $leave->user->name_with_role : 'Unknown',
                        'leave_type_code'     => $leave->leaveType?->code ?? 'L',
                        'leave_type_name'     => $leave->leaveType?->name ?? 'Leave',
                        'from_date_formatted' => $leave->from_date ? $leave->from_date->format('d M Y') : '—',
                        'to_date_formatted'   => $leave->to_date ? $leave->to_date->format('d M Y') : '—',
                        'duration'            => $leave->duration,
                        'duration_formatted'  => ucwords(str_replace('_', ' ', $leave->duration ?? '')),
                        'days_count'          => (float) $leave->days_count,
                        'reason'              => $leave->reason ?? '-',
                        'status'              => $leave->status,
                        'rejected_reason'     => $leave->rejected_reason,
                        'approve_url'         => route('admin.leave.approve', $leave->id),
                        'reject_url'          => route('admin.leave.reject', $leave->id),
                        'cancel_url'          => route('admin.leave.cancel', $leave->id),
                    ];
                }),
                'conflicts' => $conflicts->map(function ($c) {
                    return [
                        'id'                      => $c->id,
                        'user_name'               => $c->user ? $c->user->name_with_role : 'Unknown',
                        'leave_type_code'         => $c->leaveType?->code ?? 'L',
                        'leave_type_name'         => $c->leaveType?->name ?? 'Leave',
                        'from_date_formatted'     => $c->from_date ? $c->from_date->format('d M Y') : '—',
                        'to_date_formatted'       => $c->to_date ? $c->to_date->format('d M Y') : '—',
                        'conflict_date_formatted' => $c->conflict_date ? Carbon::parse($c->conflict_date)->format('d M Y') : '—',
                        'conflict_status'         => $c->conflict_status,
                        'resolved_action'         => $c->resolved_action,
                        'resolution_note'         => $c->resolution_note,
                        'resolved_by_name'        => $c->resolvedBy ? $c->resolvedBy->name_with_role : 'Admin',
                        'resolved_at_formatted'   => $c->resolved_at ? $c->resolved_at->format('d M Y H:i') : null,
                        'resolve_url'             => route('admin.leave.resolve-conflict', $c->id),
                    ];
                }),
                'counts' => [
                    'pending'   => $pendingCount,
                    'approved'  => $approvedCount,
                    'rejected'  => $rejectedCount,
                    'conflicts' => $conflictCount,
                ],
                'pagination' => [
                    'current_page' => $leaves->currentPage(),
                    'last_page'    => $leaves->lastPage(),
                    'per_page'     => $leaves->perPage(),
                    'total'        => $leaves->total(),
                    'from'         => $leaves->firstItem() ?? 0,
                    'to'           => $leaves->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.leave.index', compact(
            'leaves', 'users', 'pendingCount', 'approvedCount', 'rejectedCount', 'conflictCount', 'conflicts', 'conflictTab'
        ));
    }

    /**
     * DataTables endpoint for Leave Applications
     */
    public function data(Request $request)
    {
        $user = $request->user();
        $scopedUserIds = DataScope::visibleUserIds('leaves');

        $baseQuery = Leave::query();

        if ($scopedUserIds !== null) {
            $baseQuery->whereIn('user_id', $scopedUserIds);
        }

        if ($request->filled('user_id')) {
            if ($scopedUserIds === null || in_array($request->user_id, $scopedUserIds)) {
                $baseQuery->where('user_id', $request->user_id);
            } else {
                $baseQuery->whereRaw('1=0');
            }
        }

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $baseQuery->where(function ($q) use ($month) {
                $q->whereMonth('from_date', $month->month)->whereYear('from_date', $month->year)
                  ->orWhere(fn ($q2) => $q2->whereMonth('to_date', $month->month)->whereYear('to_date', $month->year));
            });
        }

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('to_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $baseQuery->whereDate('from_date', '<=', $request->date_to);
        }

        $search = $request->input('search_custom');
        if (empty($search)) {
            $search = $request->input('search.value');
        }
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }

        if (!empty($search) && is_string($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%"))
                  ->orWhereHas('leaveType', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('rejected_reason', 'like', "%{$search}%");
            });
        }

        // Summary counts respecting current filters (excluding status filter)
        $countQuery = (clone $baseQuery);
        $pendingCount  = (clone $countQuery)->where('status', 'pending')->count();
        $approvedCount = (clone $countQuery)->where('status', 'approved')->count();
        $rejectedCount = (clone $countQuery)->where('status', 'rejected')->count();
        $conflictCount = (clone $countQuery)->where('has_conflict', true)->where('conflict_status', 'pending_review')->count();

        $counts = [
            'pending'   => $pendingCount,
            'approved'  => $approvedCount,
            'rejected'  => $rejectedCount,
            'conflicts' => $conflictCount,
        ];

        // Apply status filter for table rows
        $query = (clone $baseQuery)->with(['user.roles', 'leaveType', 'approvedBy']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $canApprove = $user->can('leaves.approve') || $user->can('leaves.manage') || $user->hasRole('Super Admin');
        $canReject  = $user->can('leaves.reject') || $user->can('leaves.manage') || $user->hasRole('Super Admin');
        $canCancel  = $user->can('leaves.manage') || $user->can('leaves.approve') || $user->hasRole('Super Admin');

        return DataTables::of($query)
            ->addColumn('employee', function ($leave) {
                return [
                    'name' => $leave->user ? $leave->user->name_with_role : 'Unknown',
                    'role' => $leave->user?->role_name ?? ($leave->user?->roles->first()?->name ?? 'Field Worker'),
                    'employee_id' => $leave->user?->employee_id ?? '—',
                ];
            })
            ->addColumn('leave_type', function ($leave) {
                return [
                    'code' => $leave->leaveType?->code ?? 'L',
                    'name' => $leave->leaveType?->name ?? 'Leave',
                ];
            })
            ->addColumn('date_range', function ($leave) {
                return [
                    'from' => $leave->from_date ? $leave->from_date->format('d M Y') : '—',
                    'to'   => $leave->to_date ? $leave->to_date->format('d M Y') : '—',
                    'raw_from' => $leave->from_date ? $leave->from_date->format('Y-m-d') : '',
                ];
            })
            ->addColumn('duration', function ($leave) {
                return [
                    'formatted'  => ucwords(str_replace('_', ' ', $leave->duration ?? '')),
                    'days_count' => (float) $leave->days_count,
                ];
            })
            ->addColumn('reason', function ($leave) {
                return [
                    'reason'          => $leave->reason ?? '-',
                    'rejected_reason' => $leave->rejected_reason,
                ];
            })
            ->addColumn('status', function ($leave) {
                return $leave->status;
            })
            ->addColumn('actions', function ($leave) use ($canApprove, $canReject, $canCancel) {
                return [
                    'id'          => $leave->id,
                    'user_name'   => $leave->user?->name ?? 'Unknown',
                    'status'      => $leave->status,
                    'days_count'  => (float) $leave->days_count,
                    'can_approve' => $canApprove,
                    'can_reject'  => $canReject,
                    'can_cancel'  => $canCancel,
                    'approve_url' => route('admin.leave.approve', $leave->id),
                    'reject_url'  => route('admin.leave.reject', $leave->id),
                    'cancel_url'  => route('admin.leave.cancel', $leave->id),
                ];
            })
            ->with('counts', $counts)
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * DataTables endpoint for Attendance Work Conflicts
     */
    public function conflictsData(Request $request)
    {
        $user = $request->user();
        $scopedUserIds = DataScope::visibleUserIds('leaves');

        $conflictTab = $request->input('conflict_tab', 'pending');
        $conflictsQuery = Leave::with(['user.roles', 'leaveType', 'resolvedBy.roles'])
            ->where('has_conflict', true)
            ->when(!empty($scopedUserIds), fn($q) => $q->whereIn('user_id', $scopedUserIds))
            ->when($conflictTab === 'resolved', fn ($q) => $q->where('conflict_status', 'resolved'))
            ->when($conflictTab !== 'resolved', fn ($q) => $q->where('conflict_status', 'pending_review'));

        if ($request->filled('user_id')) {
            if ($scopedUserIds === null || in_array($request->user_id, $scopedUserIds)) {
                $conflictsQuery->where('user_id', $request->user_id);
            } else {
                $conflictsQuery->whereRaw('1=0');
            }
        }

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $conflictsQuery->where(function ($q) use ($month) {
                $q->whereMonth('conflict_date', $month->month)->whereYear('conflict_date', $month->year);
            });
        }

        $search = $request->input('search_custom');
        if (empty($search)) {
            $search = $request->input('search.value');
        }
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }

        if (!empty($search) && is_string($search)) {
            $conflictsQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%"))
                  ->orWhereHas('leaveType', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhere('resolution_note', 'like', "%{$search}%");
            });
        }

        $canResolveConflict = $user->can('leaves.manage') || $user->can('leaves.approve') || $user->hasRole('Super Admin');

        return DataTables::of($conflictsQuery)
            ->addColumn('employee', function ($c) {
                return [
                    'name' => $c->user ? $c->user->name_with_role : 'Unknown',
                    'role' => $c->user?->role_name ?? ($c->user?->roles->first()?->name ?? 'Field Worker'),
                    'employee_id' => $c->user?->employee_id ?? '—',
                ];
            })
            ->addColumn('leave_type', function ($c) {
                return [
                    'code' => $c->leaveType?->code ?? 'L',
                    'name' => $c->leaveType?->name ?? 'Leave',
                ];
            })
            ->addColumn('leave_range', function ($c) {
                return [
                    'from' => $c->from_date ? $c->from_date->format('d M Y') : '—',
                    'to'   => $c->to_date ? $c->to_date->format('d M Y') : '—',
                ];
            })
            ->addColumn('conflict_date', function ($c) {
                return [
                    'formatted' => $c->conflict_date ? Carbon::parse($c->conflict_date)->format('d M Y') : '—',
                    'raw'       => $c->conflict_date ? Carbon::parse($c->conflict_date)->format('Y-m-d') : '',
                ];
            })
            ->addColumn('status', function ($c) {
                return $c->conflict_status;
            })
            ->addColumn('resolution', function ($c) {
                return [
                    'action'       => $c->resolved_action,
                    'note'         => $c->resolution_note,
                    'by_name'      => $c->resolvedBy ? $c->resolvedBy->name_with_role : 'Admin',
                    'at_formatted' => $c->resolved_at ? $c->resolved_at->format('d M Y H:i') : null,
                ];
            })
            ->addColumn('actions', function ($c) use ($canResolveConflict) {
                return [
                    'id'                   => $c->id,
                    'user_name'            => $c->user?->name ?? 'Unknown',
                    'conflict_status'      => $c->conflict_status,
                    'conflict_date'        => $c->conflict_date ? Carbon::parse($c->conflict_date)->format('d M Y') : '—',
                    'leave_range'          => ($c->from_date ? $c->from_date->format('d M Y') : '—') . ' → ' . ($c->to_date ? $c->to_date->format('d M Y') : '—'),
                    'can_resolve_conflict' => $canResolveConflict,
                    'resolve_url'          => route('admin.leave.resolve-conflict', $c->id),
                ];
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Approve leave application.
     */
    public function approve(Request $request, Leave $leave)
    {
        if (!$request->user()->can('leaves.approve') && !$request->user()->can('leaves.manage') && !$request->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized to approve leave requests.');
        }

        if ($leave->user_id === $request->user()->id && !$request->user()->hasRole(['Super Admin', 'super_admin'])) {
            abort(403, 'Conflict of Interest: You cannot approve your own leave.');
        }

        $actionableIds = DataScope::actionableSubordinateIds('leaves');
        if ($actionableIds !== null && !in_array($leave->user_id, $actionableIds, true)) {
            abort(403, 'Unauthorized: You can only approve leave requests for subordinates strictly below you.');
        }

        if ($leave->status !== 'pending') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Leave request has already been processed.'], 422);
            }
            return back()->with('error', 'Leave request has already been processed.');
        }

        // Guard 2: Verify employee has enough available balance before approving
        if ($leave->leaveType && $leave->leaveType->days_per_year > 0) {
            $year = $leave->from_date->year;
            $balances = $this->attendance->getOrCreateBalances($leave->user_id, $year);
            $balance = $balances->firstWhere('leave_type_id', $leave->leave_type_id);
            $available = $balance ? $balance->available : 0;

            if ($leave->days_count > $available) {
                $msg = "Cannot approve request. Employee only has {$available} day(s) remaining for {$leave->leaveType->name}, but this request requires {$leave->days_count} day(s).";
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }
        }

        $oldStatus = $leave->status;
        DB::transaction(function () use ($leave, $oldStatus) {
            $leave->disableAuditLog = true;
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

            AuditLog::logEvent(
                event: 'leave.approved',
                auditable: $leave,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()->toDateTimeString()],
                reason: 'Leave application approved by ' . (auth()->user()?->name ?? 'Admin')
            );
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Leave application approved successfully.']);
        }

        return back()->with('success', 'Leave application approved successfully.');
    }

    /**
     * Reject leave application with reason.
     */
    public function reject(Request $request, Leave $leave)
    {
        if (!$request->user()->can('leaves.reject') && !$request->user()->can('leaves.manage') && !$request->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized to reject leave requests.');
        }

        if ($leave->user_id === $request->user()->id && !$request->user()->hasRole(['Super Admin', 'super_admin'])) {
            abort(403, 'Conflict of Interest: You cannot reject your own leave.');
        }

        $actionableIds = DataScope::actionableSubordinateIds('leaves');
        if ($actionableIds !== null && !in_array($leave->user_id, $actionableIds, true)) {
            abort(403, 'Unauthorized: You can only reject leave requests for subordinates strictly below you.');
        }

        $validated = $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        if ($leave->status !== 'pending') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Leave request has already been processed.'], 422);
            }
            return back()->with('error', 'Leave request has already been processed.');
        }

        $oldStatus = $leave->status;
        $leave->disableAuditLog = true;
        $leave->update([
            'status'          => 'rejected',
            'approved_by'     => auth()->id(),
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        AuditLog::logEvent(
            event: 'leave.rejected',
            auditable: $leave,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'rejected', 'rejected_reason' => $validated['rejected_reason']],
            reason: $validated['rejected_reason']
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Leave application rejected successfully.']);
        }

        return back()->with('success', 'Leave application rejected successfully.');
    }

    /**
     * Cancel an approved leave application (restores/refunds deducted balance).
     */
    public function cancelApproved(Request $request, Leave $leave)
    {
        if (!$request->user()->can('leaves.manage') && !$request->user()->can('leaves.approve') && !$request->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized to cancel approved leave requests.');
        }

        $scopedUserIds = DataScope::visibleUserIds('leaves');
        if ($scopedUserIds !== null && !in_array($leave->user_id, $scopedUserIds, true)) {
            abort(403, 'Unauthorized to cancel this leave request.');
        }

        if (! in_array($leave->status, ['approved', 'cancellation_requested'], true)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Only approved or cancellation-requested leaves can be cancelled.'], 422);
            }
            return back()->with('error', 'Only approved or cancellation-requested leaves can be cancelled.');
        }

        $oldStatus = $leave->status;
        DB::transaction(function () use ($leave, $oldStatus) {
            $leave->disableAuditLog = true;
            $leave->update([
                'status' => 'cancelled',
            ]);

            $this->attendance->restoreBalance(
                $leave->user_id,
                $leave->leave_type_id,
                $leave->days_count,
                $leave->from_date->year
            );

            AuditLog::logEvent(
                event: 'leave.cancelled',
                auditable: $leave,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'cancelled'],
                reason: 'Approved leave cancelled by ' . (auth()->user()?->name ?? 'Admin') . " ({$leave->days_count} day(s) refunded)"
            );
        });

        $msg = "Leave cancelled successfully. {$leave->days_count} day(s) refunded to employee balance.";
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Resolve an attendance conflict via Web Admin Panel.
     */
    public function resolveConflict(Request $request, Leave $leave)
    {
        if (!$request->user()->can('leaves.manage') && !$request->user()->can('leaves.approve') && !$request->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized to resolve conflict for this leave request.');
        }

        $scopedUserIds = DataScope::visibleUserIds('leaves');
        if ($scopedUserIds !== null && !in_array($leave->user_id, $scopedUserIds, true)) {
            abort(403, 'Unauthorized to resolve conflict for this leave request.');
        }

        $validated = $request->validate([
            'resolved_action' => 'required|in:marked_present,kept_as_leave',
            'resolution_note' => 'required|string|min:3|max:1000',
        ]);

        if ($leave->conflict_status === 'resolved') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'This conflict has already been resolved.'], 422);
            }
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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Attendance conflict resolved successfully.']);
        }

        return back()->with('success', 'Attendance conflict resolved successfully.');
    }
}
