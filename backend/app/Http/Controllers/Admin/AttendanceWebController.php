<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\DataScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceWebController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    /**
     * Display employee monthly attendance calendar & team summary.
     * Scoped: only shows users within the logged-in user's visible team.
     */
    public function index(Request $request)
    {
        // Scoped user list
        $visibleIds = DataScope::visibleUserIds('attendance');
        $usersQuery = User::with('roles')->where('is_active', true)->orderBy('name');
        if ($visibleIds !== null) {
            $usersQuery->whereIn('id', $visibleIds);
        }
        $users = $usersQuery->get();

        $selectedUserId = $request->integer('user_id', $users->first()?->id ?? 0);
        $monthInput     = $request->input('month', Carbon::now()->format('Y-m'));

        $date  = Carbon::parse($monthInput);
        $year  = $date->year;
        $month = $date->month;

        // Scope check: prevent viewing a user outside your visible scope
        if ($visibleIds !== null && !in_array($selectedUserId, $visibleIds)) {
            $selectedUserId = $users->first()?->id ?? 0;
        }

        $selectedUser = User::with('roles')->find($selectedUserId);

        $calendar = [];
        $summary  = ['present' => 0, 'half_day_present' => 0, 'short_shift' => 0, 'on_leave' => 0, 'holiday' => 0, 'weekly_off' => 0, 'absent' => 0];

        if ($selectedUser) {
            $calendar = $this->attendance->monthlyCalendar($selectedUser->id, $year, $month);
            $summary  = $this->attendance->monthlySummary($selectedUser->id, $year, $month);
        }

        // Format calendar array for client-side consumption
        $formattedCalendar = [];
        foreach ($calendar as $dateStr => $dayInfo) {
            $d = Carbon::parse($dateStr);
            $dayStart = $dayInfo['day_start'] ?? null;
            $formattedCalendar[] = [
                'date'           => $dateStr,
                'day'            => (int) $d->day,
                'day_name'       => $d->format('D'),
                'day_of_week'    => (int) $d->dayOfWeek, // 0 = Sunday
                'is_today'       => $d->isToday(),
                'is_future'      => $d->isFuture(),
                'is_sunday'      => $d->isSunday(),
                'status'         => $dayInfo['status'] ?? 'not_yet',
                'label'          => $dayInfo['label'] ?? '—',
                'hours'          => $dayInfo['hours'] ?? null,
                'is_auto_closed' => (bool) ($dayStart?->is_auto_closed ?? false),
                'flagged_reason' => $dayStart?->flagged_reason ?? null,
            ];
        }

        $summaryData = [
            'present'           => $summary['present'] ?? 0,
            'half_day_present'  => $summary['half_day_present'] ?? 0,
            'short_shift'       => $summary['short_shift'] ?? 0,
            'on_leave'          => $summary['on_leave'] ?? 0,
            'absent'            => $summary['absent'] ?? 0,
            'holiday'           => $summary['holiday'] ?? 0,
            'weekly_off'        => $summary['weekly_off'] ?? 0,
            'half_day_total'    => ($summary['half_day_present'] ?? 0) + ($summary['short_shift'] ?? 0),
            'holiday_off_total' => ($summary['holiday'] ?? 0) + ($summary['weekly_off'] ?? 0),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'user'              => $selectedUser ? [
                    'id'          => $selectedUser->id,
                    'name'        => $selectedUser->name_with_role,
                    'email'       => $selectedUser->email,
                ] : null,
                'year'              => $year,
                'month'             => $month,
                'month_input'       => $monthInput,
                'month_name'        => Carbon::create($year, $month, 1)->format('F Y'),
                'start_day_of_week' => Carbon::create($year, $month, 1)->dayOfWeek,
                'days_in_month'     => Carbon::create($year, $month, 1)->daysInMonth,
                'summary'           => $summaryData,
                'calendar'          => $formattedCalendar,
            ]);
        }

        // Team summary — scoped to visible users only
        $teamSummary = $users->map(fn ($u) => [
            'user'    => $u,
            'summary' => $this->attendance->monthlySummary($u->id, $year, $month),
        ]);

        return view('admin.attendance.index', compact(
            'users',
            'selectedUser',
            'selectedUserId',
            'monthInput',
            'year',
            'month',
            'calendar',
            'formattedCalendar',
            'summary',
            'summaryData',
            'teamSummary'
        ));
    }
}
