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

        if ($request->ajax() || $request->wantsJson()) {
            $dateObj = \Carbon\Carbon::parse($date);
            $year = $dateObj->year;
            $month = $dateObj->month;
            $summary = $this->attendance->monthlySummary($userId, $year, $month);
            $dayEval = $this->attendance->evaluate($userId, $date);
            $d = \Carbon\Carbon::parse($date);
            $ds = DayStart::where('user_id', $userId)->where('date', $date)->first();

            return response()->json([
                'success' => true,
                'message' => "Attendance for {$date} successfully corrected.",
                'day'     => [
                    'date'           => $date,
                    'day'            => (int) $d->day,
                    'day_name'       => $d->format('D'),
                    'day_of_week'    => (int) $d->dayOfWeek,
                    'is_today'       => $d->isToday(),
                    'is_future'      => $d->isFuture(),
                    'is_sunday'      => $d->isSunday(),
                    'status'         => $dayEval['status'] ?? 'not_yet',
                    'label'          => $dayEval['label'] ?? '—',
                    'hours'          => $dayEval['hours'] ?? null,
                    'is_auto_closed' => (bool) ($ds?->is_auto_closed ?? false),
                    'flagged_reason' => $ds?->flagged_reason ?? null,
                ],
                'summary' => [
                    'present'           => $summary['present'] ?? 0,
                    'half_day_present'  => $summary['half_day_present'] ?? 0,
                    'short_shift'       => $summary['short_shift'] ?? 0,
                    'on_leave'          => $summary['on_leave'] ?? 0,
                    'absent'            => $summary['absent'] ?? 0,
                    'holiday'           => $summary['holiday'] ?? 0,
                    'weekly_off'        => $summary['weekly_off'] ?? 0,
                    'half_day_total'    => ($summary['half_day_present'] ?? 0) + ($summary['short_shift'] ?? 0),
                    'holiday_off_total' => ($summary['holiday'] ?? 0) + ($summary['weekly_off'] ?? 0),
                ],
            ]);
        }

        return back()->with('success', "Attendance for {$date} successfully corrected by admin.");
    }

    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('causer.roles')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.audit-logs.index', compact('logs'));
    }
}
