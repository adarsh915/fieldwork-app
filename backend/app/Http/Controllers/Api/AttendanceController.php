<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\DataScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    // ─── Employee Endpoints ───────────────────────────────────────────────────

    /**
     * GET /api/attendance/calendar?year=2026&month=9
     * My monthly attendance calendar with summary.
     */
    public function myCalendar(Request $request): JsonResponse
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
            return response()->json(['success' => false, 'message' => 'Invalid year or month.'], 422);
        }

        $userId   = $request->user()->id;
        $calendar = $this->attendance->monthlyCalendar($userId, $year, $month);
        $summary  = $this->attendance->monthlySummary($userId, $year, $month);

        return response()->json([
            'success'  => true,
            'calendar' => $calendar,
            'summary'  => $summary,
        ]);
    }

    // ─── Admin Endpoints ──────────────────────────────────────────────────────

    /**
     * GET /api/admin/attendance?user_id=5&year=2026&month=9
     * View any employee's monthly attendance (admin).
     */
    public function adminCalendar(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id');
        $year   = (int) $request->input('year',  now()->year);
        $month  = (int) $request->input('month', now()->month);

        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'user_id is required.'], 422);
        }

        $scopedUserIds = DataScope::visibleUserIds('attendance');
        if ($scopedUserIds !== null && !in_array($userId, $scopedUserIds)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to view this employee attendance.'], 403);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $calendar = $this->attendance->monthlyCalendar($userId, $year, $month);
        $summary  = $this->attendance->monthlySummary($userId, $year, $month);

        return response()->json([
            'success'  => true,
            'user'     => ['id' => $user->id, 'name' => $user->name],
            'calendar' => $calendar,
            'summary'  => $summary,
        ]);
    }

    /**
     * GET /api/admin/attendance/team?year=2026&month=9
     * All employees' monthly summary in one call (admin).
     */
    public function teamSummary(Request $request): JsonResponse
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $scopedUserIds = DataScope::visibleUserIds('attendance');
        $usersQuery = User::where('is_active', true);
        if ($scopedUserIds !== null) {
            $usersQuery->whereIn('id', $scopedUserIds);
        }

        $users = $usersQuery->get(['id', 'name']);

        $report = $users->map(fn ($u) => [
            'user'    => $u,
            'summary' => $this->attendance->monthlySummary($u->id, $year, $month),
        ]);

        return response()->json(['success' => true, 'data' => $report]);
    }
}
