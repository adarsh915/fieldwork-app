<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAdjustment;
use App\Models\AuditLog;
use App\Models\DayStart;
use App\Services\AttendanceService;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceAdjustmentWebController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function adjust(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'date'       => 'required|date',
            'new_status' => 'required|string|in:present,half_day_present,short_shift,on_leave,holiday,weekly_off,absent',
            'reason'     => 'required|string|min:3',
            'start_time' => 'nullable',
            'end_time'   => 'nullable',
        ]);

        $userId = (int) $request->user_id;
        $date   = $request->date;

        $scopedIds = DataScope::visibleUserIds('attendance');
        if ($scopedIds !== null && !in_array($userId, $scopedIds)) {
            abort(403, 'Unauthorized: Cannot adjust attendance for an employee outside your scope.');
        }

        $evalBefore = $this->attendance->evaluate($userId, $date);
        $dayStart   = DayStart::where('user_id', $userId)->where('date', $date)->first();

        DB::transaction(function () use ($request, $userId, $date, $evalBefore, $dayStart) {
            $adj = AttendanceAdjustment::updateOrCreate(
                ['user_id' => $userId, 'date' => $date],
                [
                    'original_status'     => $evalBefore['status'],
                    'new_status'          => $request->new_status,
                    'original_start_time' => $dayStart?->start_time,
                    'new_start_time'      => $request->start_time ?? $dayStart?->start_time,
                    'original_end_time'   => $dayStart?->end_time,
                    'new_end_time'        => $request->end_time ?? $dayStart?->end_time,
                    'reason'              => $request->reason,
                    'adjusted_by'         => auth()->id(),
                ]
            );

            AuditLog::logEvent(
                event: 'attendance.manual_adjustment',
                auditable: $adj,
                oldValues: ['status' => $evalBefore['status']],
                newValues: ['status' => $request->new_status, 'start_time' => $request->start_time, 'end_time' => $request->end_time],
                reason: $request->reason,
                causerUserId: auth()->id()
            );
        });

        return back()->with('success', "Attendance for {$date} successfully corrected by admin.");
    }

    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('causer:id,name,email')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.audit-logs.index', compact('logs'));
    }
}
