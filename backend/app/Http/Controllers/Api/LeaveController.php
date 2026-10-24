<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyLeaveRequest;
use App\Http\Requests\RejectLeaveRequest;
use App\Models\AttendanceAdjustment;
use App\Models\AuditLog;
use App\Models\DayStart;
use App\Models\Leave;
use App\Services\ActivityNotificationService;
use App\Services\AttendanceService;
use App\Services\DataScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    // ─── Employee Endpoints ───────────────────────────────────────────────────

    /**
     * GET /api/leaves
     * My/team leave applications (paginated, with server-side filtering & instant counts).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Ultra-fast single aggregate query for tab badge counts (uses composite index)
        $statusCounts = Leave::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_all,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as total_rejected,
                SUM(CASE WHEN status IN ("cancellation_requested", "cancelled") THEN 1 ELSE 0 END) as total_cancelled
            ')
            ->first();

        $query = Leave::query()
            ->with(['leaveType:id,name,code', 'user:id,name,employee_id'])
            ->where('user_id', $userId);

        // Status filtering
        if ($request->filled('status') && $request->status !== 'all') {
            $status = strtolower(trim($request->status));
            if ($status === 'cancelled') {
                $query->whereIn('status', ['cancellation_requested', 'cancelled']);
            } else {
                $query->where('status', $status);
            }
        }

        // Leave type filter
        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        // Search in reason, rejected_reason, or leave type name/code
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'LIKE', "%{$search}%")
                  ->orWhere('rejected_reason', 'LIKE', "%{$search}%")
                  ->orWhereHas('leaveType', function ($ltQ) use ($search) {
                      $ltQ->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('code', 'LIKE', "%{$search}%");
                  });
            });
        }

        $query->orderBy('from_date', 'desc')
              ->orderBy('id', 'desc');

        $countsArray = [
            'all'       => (int) ($statusCounts->total_all ?? 0),
            'pending'   => (int) ($statusCounts->total_pending ?? 0),
            'approved'  => (int) ($statusCounts->total_approved ?? 0),
            'rejected'  => (int) ($statusCounts->total_rejected ?? 0),
            'cancelled' => (int) ($statusCounts->total_cancelled ?? 0),
        ];

        // Server-side pagination
        if ($request->has('page') || $request->has('per_page')) {
            $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
            $paginated = $query->paginate($perPage);

            return response()->json([
                'success'      => true,
                'data'         => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'has_more'     => $paginated->hasMorePages(),
                'counts'       => $countsArray,
            ]);
        }

        $leaves = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $leaves,
            'total'   => $leaves->count(),
            'counts'  => $countsArray,
        ]);
    }

    /**
     * GET /api/leaves/balance
     * My leave balance for the current year (auto-provisions rows if missing).
     */
    public function balance(Request $request): JsonResponse
    {
        $balances = $this->attendance->getOrCreateBalances(
            $request->user()->id,
            now()->year
        );

        return response()->json(['success' => true, 'data' => $balances]);
    }

    /**
     * GET /api/leaves/types
     * Active leave types (for mobile dropdown).
     */
    public function types(): JsonResponse
    {
        $types = \App\Models\LeaveType::active()->get(['id', 'name', 'code', 'days_per_year']);
        return response()->json(['success' => true, 'data' => $types]);
    }

    /**
     * POST /api/leaves/apply
     * Apply for leave.
     */
    public function apply(ApplyLeaveRequest $request): JsonResponse
    {
        $user = $request->user();

        // Business rule: block overlap with existing pending/approved leave
        $overlap = Leave::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where('from_date', '<=', $request->to_date)
            ->where('to_date', '>=', $request->from_date)
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'A leave application already exists for overlapping dates.',
            ], 409);
        }

        // Guard 1: Calculate requested days & verify against effective available balance (including pending requests)
        $leaveType = \App\Models\LeaveType::find($request->leave_type_id);
        if ($leaveType && ($leaveType->days_per_year > 0 || $leaveType->days_per_month > 0)) {
            $year = \Carbon\Carbon::parse($request->from_date)->year;

            $tempLeave = new Leave([
                'user_id'       => $user->id,
                'leave_type_id' => $request->leave_type_id,
                'from_date'     => $request->from_date,
                'to_date'       => $request->to_date,
                'duration'      => $request->input('duration', 'full'),
            ]);
            $requestedDays = $tempLeave->days_count;

            // Guard 0: Reject application if selected range consists entirely of non-working days (holidays / weekly off)
            if ($requestedDays <= 0) {
                $msg = 'The selected date(s) consist entirely of declared company holidays or weekly off days. No leave application is required.';
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors'  => [
                        'from_date' => [$msg],
                    ],
                ], 422);
            }

            $effectiveAvailable = $this->attendance->getEffectiveAvailable($user->id, $leaveType->id, $year);

            if ($requestedDays > $effectiveAvailable) {
                $pendingDays = $this->attendance->getPendingDays($user->id, $leaveType->id, $year);
                $msg = "Insufficient leave balance. You have {$effectiveAvailable} day(s) available for {$leaveType->name} ({$pendingDays} day(s) already reserved in pending requests).";
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors'  => [
                        'leave_type_id' => [$msg],
                    ],
                ], 422);
            }
        }

        $leave = Leave::create([
            'user_id'       => $user->id,
            'leave_type_id' => $request->leave_type_id,
            'from_date'     => $request->from_date,
            'to_date'       => $request->to_date,
            'duration'      => $request->input('duration', 'full'),
            'reason'        => $request->reason,
            'status'        => 'pending',
        ]);

        ActivityNotificationService::notifyLeaveApplied($leave);

        return response()->json([
            'success' => true,
            'message' => 'Leave application submitted successfully.',
            'data'    => $leave->load('leaveType:id,name,code'),
        ], 201);
    }

    /**
     * DELETE /api/leaves/{id}/cancel
     * Cancel a pending leave (employee can only cancel their own pending leaves).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $leave = Leave::where('user_id', $request->user()->id)->find($id);

        if (! $leave) {
            return response()->json(['success' => false, 'message' => 'Leave not found.'], 404);
        }

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending leaves can be cancelled. Contact admin for approved leave cancellation.',
            ], 422);
        }

        $leave->delete();

        return response()->json(['success' => true, 'message' => 'Leave cancelled successfully.']);
    }

    /**
     * POST /api/leaves/{id}/request-cancellation
     * Request cancellation for an approved leave.
     */
    public function requestCancellation(Request $request, int $id): JsonResponse
    {
        $leave = Leave::where('user_id', $request->user()->id)->find($id);

        if (! $leave) {
            return response()->json(['success' => false, 'message' => 'Leave application not found.'], 404);
        }

        if ($leave->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation request can only be submitted for approved leaves.',
            ], 422);
        }

        $updateData = ['status' => 'cancellation_requested'];

        if ($leave->has_conflict && $leave->conflict_status === 'pending_review') {
            $updateData['conflict_status'] = 'resolved';
            $updateData['resolved_action'] = 'marked_present';
            $updateData['resolved_at']     = now();
            $updateData['resolution_note'] = 'Auto-resolved via employee-initiated cancellation request.';
        }

        $leave->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Leave cancellation request submitted. Awaiting admin approval.',
            'data'    => $leave->fresh('leaveType:id,name,code'),
        ]);
    }

    // ─── Admin Endpoints ──────────────────────────────────────────────────────

    /**
     * GET /api/admin/leaves
     * All leave applications (admin view, filterable by status/user).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Leave::with([
            'user:id,name',
            'leaveType:id,name,code',
            'approvedBy:id,name',
        ])->orderBy('created_at', 'desc');

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /**
     * POST /api/admin/leaves/{id}/approve
     * Approve a pending leave and deduct balance.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('leaves.approve') && !$request->user()->can('leaves.manage') && !$request->user()->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve leave requests.'], 403);
        }

        $leave = Leave::with('leaveType')->find($id);

        if (! $leave) {
            return response()->json(['success' => false, 'message' => 'Leave not found.'], 404);
        }

        if ($leave->user_id === $request->user()->id && !$request->user()->hasRole(['Super Admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Conflict of Interest: You cannot approve your own leave.'], 403);
        }

        $actionableIds = DataScope::actionableSubordinateIds('leaves');
        if ($actionableIds !== null && !in_array($leave->user_id, $actionableIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only approve leave requests for subordinates strictly below you.'], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This leave has already been processed.'], 422);
        }

        // Guard 2: Verify employee has enough available balance before approving
        if ($leave->leaveType && $leave->leaveType->days_per_year > 0) {
            $year = $leave->from_date->year;
            $balances = $this->attendance->getOrCreateBalances($leave->user_id, $year);
            $balance = $balances->firstWhere('leave_type_id', $leave->leave_type_id);
            $available = $balance ? $balance->available : 0;

            if ($leave->days_count > $available) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot approve request. Employee only has {$available} day(s) remaining for {$leave->leaveType->name}, but this request requires {$leave->days_count} day(s).",
                ], 422);
            }
        }

        DB::transaction(function () use ($leave, $request) {
            $leave->update([
                'status'      => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            // Deduct from leave balance
            $this->attendance->deductBalance(
                $leave->user_id,
                $leave->leave_type_id,
                $leave->days_count,
                $leave->from_date->year
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Leave approved.',
            'data'    => $leave->refresh()->load(['user:id,name', 'leaveType:id,name,code']),
        ]);
    }

    /**
     * POST /api/admin/leaves/{id}/reject
     * Reject a pending leave with a mandatory reason.
     */
    public function reject(RejectLeaveRequest $request, int $id): JsonResponse
    {
        if (!$request->user()->can('leaves.reject') && !$request->user()->can('leaves.manage') && !$request->user()->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to reject leave requests.'], 403);
        }

        $leave = Leave::find($id);

        if (! $leave) {
            return response()->json(['success' => false, 'message' => 'Leave not found.'], 404);
        }

        if ($leave->user_id === $request->user()->id && !$request->user()->hasRole(['Super Admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Conflict of Interest: You cannot reject your own leave.'], 403);
        }

        $actionableIds = DataScope::actionableSubordinateIds('leaves');
        if ($actionableIds !== null && !in_array($leave->user_id, $actionableIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You can only reject leave requests for subordinates strictly below you.'], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This leave has already been processed.'], 422);
        }

        $leave->update([
            'status'          => 'rejected',
            'approved_by'     => $request->user()->id,
            'rejected_reason' => $request->reason,
        ]);

        return response()->json(['success' => true, 'message' => 'Leave rejected.']);
    }

    /**
     * GET /api/admin/leaves/conflicts
     * Fetch all leave records with attendance conflicts.
     */
    public function indexConflicts(Request $request): JsonResponse
    {
        $status = $request->input('status', 'pending_review');

        $conflicts = Leave::with(['user:id,name,email', 'leaveType:id,name,code', 'resolvedBy:id,name'])
            ->where('has_conflict', true)
            ->when($status === 'resolved', fn ($q) => $q->where('conflict_status', 'resolved'))
            ->when($status !== 'resolved', fn ($q) => $q->where('conflict_status', 'pending_review'))
            ->orderBy('conflict_date', 'desc')
            ->get()
            ->map(function ($leave) {
                $dayStart = DayStart::where('user_id', $leave->user_id)
                    ->where('date', $leave->conflict_date)
                    ->first();

                $hoursWorked = 0.0;
                if ($dayStart && $dayStart->start_time && $dayStart->end_time) {
                    $hoursWorked = round(Carbon::parse($dayStart->end_time)->diffInMinutes(Carbon::parse($dayStart->start_time)) / 60, 2);
                } elseif ($dayStart && $dayStart->start_time) {
                    $hoursWorked = round(Carbon::now()->diffInMinutes(Carbon::parse($dayStart->start_time)) / 60, 2);
                }

                return [
                    'id'               => $leave->id,
                    'user_id'          => $leave->user_id,
                    'employee_name'    => $leave->user?->name,
                    'leave_type'       => $leave->leaveType?->code,
                    'leave_type_name'  => $leave->leaveType?->name,
                    'from_date'        => $leave->from_date?->format('Y-m-d'),
                    'to_date'          => $leave->to_date?->format('Y-m-d'),
                    'duration'         => $leave->duration,
                    'days_count'       => $leave->days_count,
                    'conflict_date'    => $leave->conflict_date?->format('Y-m-d'),
                    'hours_worked'     => $hoursWorked,
                    'day_start_status' => $dayStart?->status ?? 'active',
                    'conflict_status'  => $leave->conflict_status,
                    'resolved_action'  => $leave->resolved_action,
                    'resolution_note'  => $leave->resolution_note,
                    'resolved_by'      => $leave->resolvedBy?->name,
                    'resolved_at'      => $leave->resolved_at?->toIso8601String(),
                ];
            });

        $pendingCount  = Leave::where('has_conflict', true)->where('conflict_status', 'pending_review')->count();
        $resolvedCount = Leave::where('has_conflict', true)->where('conflict_status', 'resolved')->count();

        return response()->json([
            'success' => true,
            'data'    => $conflicts,
            'meta'    => [
                'pending_count'  => $pendingCount,
                'resolved_count' => $resolvedCount,
            ],
        ]);
    }

    /**
     * PATCH /api/admin/leaves/{id}/resolve-conflict
     * Resolve a flagged attendance conflict.
     */
    public function resolveConflict(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('leaves.manage') && !$request->user()->can('leaves.approve') && !$request->user()->hasRole('Super Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to resolve attendance conflicts.'], 403);
        }

        $validated = $request->validate([
            'resolved_action' => 'required|in:marked_present,kept_as_leave',
            'resolution_note' => 'required|string|min:3|max:1000',
        ]);

        $leave = Leave::find($id);

        if (! $leave) {
            return response()->json(['success' => false, 'message' => 'Leave record not found.'], 404);
        }

        if ($leave->conflict_status === 'resolved') {
            return response()->json(['success' => false, 'message' => 'This conflict has already been resolved.'], 422);
        }

        DB::transaction(function () use ($leave, $validated, $request) {
            $user = $request->user();

            if ($validated['resolved_action'] === 'marked_present') {
                // 1. Create/Update AttendanceAdjustment override
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

                // 2. Refund 1.0 (or 0.5 for half-day) day back to employee's leave balance
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

            // 3. Update Leave record conflict attributes
            $leave->update([
                'conflict_status' => 'resolved',
                'resolved_by'     => $user->id,
                'resolved_action' => $validated['resolved_action'],
                'resolved_at'     => now(),
                'resolution_note' => $validated['resolution_note'],
            ]);

            // 4. Record event in Audit Log
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

        return response()->json([
            'success' => true,
            'message' => 'Conflict resolved successfully.',
            'data'    => $leave->fresh(['user:id,name', 'leaveType:id,name,code', 'resolvedBy:id,name']),
        ]);
    }
}
