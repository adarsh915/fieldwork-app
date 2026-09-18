<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceWebController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    /**
     * Display employee monthly attendance calendar & team summary.
     */
    public function index(Request $request)
    {
        $users = User::where('is_active', true)->orderBy('name')->get();

        $selectedUserId = $request->integer('user_id', $users->first()?->id ?? 0);
        $monthInput     = $request->input('month', Carbon::now()->format('Y-m'));

        $date  = Carbon::parse($monthInput);
        $year  = $date->year;
        $month = $date->month;

        $selectedUser = User::find($selectedUserId);

        $calendar = [];
        $summary  = ['present' => 0, 'on_leave' => 0, 'holiday' => 0, 'weekly_off' => 0, 'absent' => 0];

        if ($selectedUser) {
            $calendar = $this->attendance->monthlyCalendar($selectedUser->id, $year, $month);
            $summary  = $this->attendance->monthlySummary($selectedUser->id, $year, $month);
        }

        // Team summary for all users for this month
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
