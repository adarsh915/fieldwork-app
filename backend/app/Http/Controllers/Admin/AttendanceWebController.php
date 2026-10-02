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
        $usersQuery = User::where('is_active', true)->orderBy('name');
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

        $selectedUser = User::find($selectedUserId);

        $calendar = [];
        $summary  = ['present' => 0, 'on_leave' => 0, 'holiday' => 0, 'weekly_off' => 0, 'absent' => 0];

        if ($selectedUser) {
            $calendar = $this->attendance->monthlyCalendar($selectedUser->id, $year, $month);
            $summary  = $this->attendance->monthlySummary($selectedUser->id, $year, $month);
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
            'summary',
            'teamSummary'
        ));
    }
}
