<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAdjustment;
use App\Models\AuditLog;
use App\Models\DayStart;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAttendanceAdjustmentController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    /**
     * PATCH /api/admin/attendance/{user_id}/{date}/adjust
     * Manually override status, times, or KM for any date.
     */
    public function adjustAttendance(Request $request, int $userId, string $date): JsonResponse
    {
        // Authorization: Check if user has permission to adjust attendance
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $canAdjust = $user->can('attendance.adjust') || $user->can('attendance.manage') || $isSuperAdmin;

        if (!$canAdjust) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to adjust attendance.',
            ], 403);
        }

        // Hierarchy Scope Check: Ensure target user is within visible scope
        if (!$isSuperAdmin) {
            $scopedIds = DataScope::visibleUserIds('attendance');
            if ($scopedIds !== null && !in_array($userId, $scopedIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This user is outside your organizational scope.',
                ], 403);
            }
        }

        $request->validate([
            'new_status' => 'required|string|in:present,half_day_present,short_shift,on_leave,holiday,weekly_off,absent',
            'reason'     => 'required|string|min:3',
            'start_time' => 'nullable|date_format:H:i,H:i:s',
            'end_time'   => 'nullable|date_format:H:i,H:i:s',
            'new_km'     => 'nullable|numeric|min:0',
        ]);

        $evalBefore = $this->attendance->evaluate($userId, $date);

        $adj = DB::transaction(function () use ($request, $userId, $date, $evalBefore) {
            $dayStart = DayStart::where('user_id', $userId)->where('date', $date)->first();

            $adjustment = AttendanceAdjustment::updateOrCreate(
                ['user_id' => $userId, 'date' => $date],
                [
                    'original_status'     => $evalBefore['status'],
                    'new_status'          => $request->new_status,
                    'original_start_time' => $dayStart?->start_time,
                    'new_start_time'      => $request->start_time ?? $dayStart?->start_time,
                    'original_end_time'   => $dayStart?->end_time,
                    'new_end_time'        => $request->end_time ?? $dayStart?->end_time,
                    'original_km'         => $dayStart?->closing_km,
                    'new_km'              => $request->new_km ?? $dayStart?->closing_km,
                    'reason'              => $request->reason,
                    'adjusted_by'         => $request->user()->id,
                ]
            );

            // Audit log entry
            AuditLog::logEvent(
                event: 'attendance.manual_adjustment',
                auditable: $adjustment,
                oldValues: ['status' => $evalBefore['status']],
                newValues: ['status' => $request->new_status, 'start_time' => $request->start_time, 'end_time' => $request->end_time],
                reason: $request->reason,
                causerUserId: $request->user()->id
            );

            return $adjustment;
        });

        return response()->json([
            'success' => true,
            'message' => "Attendance for {$date} successfully adjusted.",
            'data'    => $adj,
        ]);
    }

    /**
     * PATCH /api/admin/leaves/{id}/edit
     * Direct admin edit of leave application.
     */
    public function editLeave(Request $request, int $id): JsonResponse
    {
        // Authorization: Check if user has permission to edit leaves
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $canEdit = $user->can('leaves.manage') || $user->can('leaves.edit') || $isSuperAdmin;

        if (!$canEdit) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to edit leave applications.',
            ], 403);
        }

        $leave = Leave::findOrFail($id);

        // Hierarchy Scope Check: Ensure leave owner is within visible scope
        if (!$isSuperAdmin) {
            $scopedIds = DataScope::visibleUserIds('leaves');
            if ($scopedIds !== null && !in_array($leave->user_id, $scopedIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This leave application is outside your organizational scope.',
                ], 403);
            }
        }

        $request->validate([
            'reason'    => 'required|string|min:3',
            'from_date' => 'sometimes|date',
            'to_date'   => 'sometimes|date|after_or_equal:from_date',
            'duration'  => 'sometimes|string|in:full,half_am,half_pm',
            'status'    => 'sometimes|string|in:pending,approved,rejected,cancelled',
        ]);

        $old = $leave->toArray();

        DB::transaction(function () use ($leave, $request, $old) {
            $leave->update($request->only(['from_date', 'to_date', 'duration', 'status', 'leave_type_id']));

            AuditLog::logEvent(
                event: 'leave.admin_edit',
                auditable: $leave,
                oldValues: $old,
                newValues: $leave->fresh()->toArray(),
                reason: $request->reason,
                causerUserId: $request->user()->id
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Leave record updated by admin.',
            'data'    => $leave->fresh(),
        ]);
    }

    /**
     * PATCH /api/admin/leave-balances/{id}/adjust
     * Manual add/subtract leave balance.
     */
    public function adjustBalance(Request $request, int $id): JsonResponse
    {
        // Authorization: Check if user has permission to adjust leave balances
        $user = $request->user();
        $isSuperAdmin = $user->hasRole(['Super Admin', 'super_admin']);
        $canAdjust = $user->can('leave-balance.adjust') || $user->can('leaves.manage') || $isSuperAdmin;

        if (!$canAdjust) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to adjust leave balances.',
            ], 403);
        }

        $balance = LeaveBalance::findOrFail($id);

        // Hierarchy Scope Check: Ensure leave balance owner is within visible scope
        if (!$isSuperAdmin) {
            $scopedIds = DataScope::visibleUserIds('leaves');
            if ($scopedIds !== null && !in_array($balance->user_id, $scopedIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This leave balance is outside your organizational scope.',
                ], 403);
            }
        }

        $request->validate([
            'allocated' => 'required|numeric|min:0',
            'used'      => 'required|numeric|min:0',
            'reason'    => 'required|string|min:3',
        ]);

        $old = $balance->toArray();

        DB::transaction(function () use ($balance, $request, $old) {
            $balance->update([
                'allocated' => $request->allocated,
                'used'      => $request->used,
            ]);

            AuditLog::logEvent(
                event: 'leave_balance.adjusted',
                auditable: $balance,
                oldValues: $old,
                newValues: $balance->fresh()->toArray(),
                reason: $request->reason,
                causerUserId: $request->user()->id
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Leave balance updated by admin.',
            'data'    => $balance->fresh(),
        ]);
    }

    /**
     * GET /api/admin/audit-logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::with('causer:id,name,email')
            ->orderByDesc('id');

        if ($request->has('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->has('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate(20),
        ]);
    }
}
