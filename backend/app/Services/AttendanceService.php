<?php

namespace App\Services;

use App\Models\DayStart;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * AttendanceService
 *
 * Single source of truth for evaluating any employee's attendance status
 * for any given date. All evaluation logic lives here — controllers are thin.
 *
 * Priority order (ties resolved top-down):
 *   1. day_starts record exists  → PRESENT / HALF_DAY_PRESENT / SHORT_SHIFT (based on duration threshold)
 *   2. Approved leave covers date → ON_LEAVE
 *   3. Holiday declared           → HOLIDAY
 *   4. Sunday (weekly off)        → WEEKLY_OFF
 *   5. Past date, no record       → ABSENT
 *   6. Today or future            → NOT_YET
 */
class AttendanceService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Evaluate attendance status for a single user on a single date.
     *
     * @param  int    $userId
     * @param  string $date   YYYY-MM-DD
     * @return array{status: string, label: string, meta: array}
     */
    public function evaluate(int $userId, string $date): array
    {
        // 0. Admin Manual Adjustment (Highest priority — Admin correction always wins)
        $adj = \App\Models\AttendanceAdjustment::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if ($adj) {
            return $this->result($adj->new_status, $this->formatStatusLabel($adj->new_status) . ' (Admin Override)', ['adjustment' => $adj]);
        }

        // 1. Physical work (day_starts)
        $dayStart = DayStart::where('user_id', $userId)
            ->where('date', $date)
            ->first(['id', 'status', 'start_time', 'end_time', 'is_auto_closed', 'flagged_reason']);

        if ($dayStart) {
            return $this->evaluateDayStart($dayStart);
        }

        // 3. Holiday declared
        $holiday = Holiday::where('date', $date)->first(['id', 'name', 'type']);
        if ($holiday) {
            return $this->result('holiday', $holiday->name, ['holiday' => $holiday]);
        }

        // 4. Sunday (weekly off)
        if ($d->isSunday()) {
            return $this->result('weekly_off', 'Weekly Off');
        }

        // 5. Approved leave
        $leave = Leave::where('user_id', $userId)
            ->approved()
            ->forDate($date)
            ->with('leaveType:id,name,code')
            ->first();

        if ($leave) {
            $label = 'On Leave';
            if ($leave->leaveType) {
                $label .= " ({$leave->leaveType->code})";
            }
            return $this->result('on_leave', $label, ['leave' => $leave]);
        }

        // 6. Today or future
        if ($d->isFuture() || $d->isToday()) {
            return $this->result('not_yet', '—');
        }

        // 7. Past date with no work, leave, or holiday
        return $this->result('absent', 'Absent');
    }

    /**
     * Get monthly calendar grid for a user.
     */
    public function monthlyCalendar(int $userId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        // Pre-fetch everything needed for the month in 4 queries (avoid N+1)
        $adjustments = \App\Models\AttendanceAdjustment::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());

        $dayStarts = DayStart::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'date', 'status', 'start_time', 'end_time', 'is_auto_closed', 'flagged_reason'])
            ->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        $leaves = Leave::where('user_id', $userId)
            ->approved()
            ->where('from_date', '<=', $end->toDateString())
            ->where('to_date', '>=', $start->toDateString())
            ->with('leaveType:id,name,code')
            ->get();

        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'date', 'name', 'type'])
            ->keyBy(fn ($h) => Carbon::parse($h->date)->toDateString());

        $calendar = [];
        $current  = $start->copy();

        while ($current->lte($end)) {
            $date = $current->toDateString();
            $calendar[$date] = $this->evaluateFromCache($userId, $date, $current, $adjustments, $dayStarts, $leaves, $holidays);
            $current->addDay();
        }

        return $calendar;
    }

    /**
     * Monthly summary counts for a user.
     */
    public function monthlySummary(int $userId, int $year, int $month): array
    {
        $counts = [
            'present'          => 0,
            'half_day_present' => 0,
            'short_shift'      => 0,
            'on_leave'         => 0,
            'holiday'          => 0,
            'weekly_off'       => 0,
            'absent'           => 0,
        ];

        foreach ($this->monthlyCalendar($userId, $year, $month) as $day) {
            $s = $day['status'];
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
        }

        return $counts;
    }

    // ─── Leave Balance ────────────────────────────────────────────────────────

    public function getOrCreateBalances(int $userId, int $year): Collection
    {
        $types = LeaveType::active()->get();

        foreach ($types as $type) {
            LeaveBalance::firstOrCreate(
                ['user_id' => $userId, 'leave_type_id' => $type->id, 'year' => $year],
                [
                    'allocated'       => $type->days_per_year,
                    'used'            => 0,
                    'carried_forward' => 0,
                ]
            );
        }

        return LeaveBalance::where('user_id', $userId)
            ->where('year', $year)
            ->whereHas('leaveType', fn ($q) => $q->active())
            ->with('leaveType:id,name,code,days_per_year,accrual_frequency,days_per_month')
            ->get();
    }

    public function deductBalance(int $userId, int $leaveTypeId, float $days, int $year): void
    {
        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $userId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
            ['allocated' => 0, 'used' => 0, 'carried_forward' => 0]
        );
        $balance->increment('used', $days);
    }

    public function restoreBalance(int $userId, int $leaveTypeId, float $days, int $year): void
    {
        LeaveBalance::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->decrement('used', $days);
    }

    public function getPendingDays(int $userId, int $leaveTypeId, int $year): float
    {
        $pendingLeaves = Leave::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->pending()
            ->whereYear('from_date', $year)
            ->get();

        return (float) $pendingLeaves->sum(fn ($l) => $l->days_count);
    }

    public function getEffectiveAvailable(int $userId, int $leaveTypeId, int $year): float
    {
        $balances = $this->getOrCreateBalances($userId, $year);
        $balance  = $balances->firstWhere('leave_type_id', $leaveTypeId);

        if (! $balance) {
            return 0.0;
        }

        $pendingDays = $this->getPendingDays($userId, $leaveTypeId, $year);

        return max(0.0, (float) $balance->available - $pendingDays);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Evaluate DayStart record duration against dynamic settings thresholds.
     */
    private function evaluateDayStart(DayStart $dayStart): array
    {
        if (! $dayStart->end_time) {
            return $this->result('present', 'Present (Active Session)', ['day_start' => $dayStart]);
        }

        $fullThreshold = (float) $this->settings->get('full_day_hours_threshold', 8.0);
        $halfThreshold = (float) $this->settings->get('half_day_hours_threshold', 4.0);

        $startTime = Carbon::parse($dayStart->start_time);
        $endTime   = Carbon::parse($dayStart->end_time);

        // If end_time is earlier than start_time (overnight shift across midnight)
        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }

        $hours = $startTime->diffInMinutes($endTime) / 60.0;

        if ($hours >= $fullThreshold) {
            return $this->result('present', 'Present', ['day_start' => $dayStart, 'hours' => round($hours, 1)]);
        }

        if ($hours >= $halfThreshold) {
            return $this->result('half_day_present', 'Half Day Present', ['day_start' => $dayStart, 'hours' => round($hours, 1)]);
        }

        return $this->result('short_shift', 'Short Shift', ['day_start' => $dayStart, 'hours' => round($hours, 1)]);
    }

    private function evaluateFromCache(
        int $userId,
        string $date,
        Carbon $d,
        $adjustments,
        $dayStarts,
        $leaves,
        $holidays
    ): array {
        if ($adjustments->has($date)) {
            $adj = $adjustments->get($date);
            return $this->result($adj->new_status, $this->formatStatusLabel($adj->new_status) . ' (Admin Override)', ['adjustment' => $adj]);
        }

        if ($dayStarts->has($date)) {
            return $this->evaluateDayStart($dayStarts->get($date));
        }

        if ($holidays->has($date)) {
            return $this->result('holiday', $holidays->get($date)->name, ['holiday' => $holidays->get($date)]);
        }

        if ($d->isSunday()) {
            return $this->result('weekly_off', 'Weekly Off');
        }

        $leave = $leaves->first(fn ($l) => $l->coversDate($date));
        if ($leave) {
            $code  = $leave->leaveType->code ?? 'L';
            return $this->result('on_leave', "On Leave ({$code})", ['leave' => $leave]);
        }

        if ($d->isFuture() || $d->isToday()) {
            return $this->result('not_yet', '—');
        }

        return $this->result('absent', 'Absent');
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            'present'          => 'Present',
            'half_day_present' => 'Half Day Present',
            'short_shift'      => 'Short Shift',
            'on_leave'         => 'On Leave',
            'holiday'          => 'Holiday',
            'weekly_off'       => 'Weekly Off',
            'absent'           => 'Absent',
            default            => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function result(string $status, string $label, array $meta = []): array
    {
        return array_merge(['status' => $status, 'label' => $label], $meta);
    }
}
