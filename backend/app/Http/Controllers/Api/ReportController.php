<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use App\Services\DataScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Mobile MIS / Performance Summary API with hierarchical Data Scoping & Date Filtering.
     * GET /api/reports/mobile-summary
     */
    public function mobileSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ── 1. Determine User Data Scope & Team Members ────────────────────────
        $allowedIds = DataScope::visibleUserIds('reports');
        $isAllScope  = ($allowedIds === null);
        $isTeamScope = (!$isAllScope && count($allowedIds) > 1);

        $scopeType = $isAllScope ? 'all' : ($isTeamScope ? 'team' : 'own');

        // Team members for dropdown selection
        $teamMembers = [];
        if ($isAllScope) {
            $teamMembers = User::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id', 'department', 'position'])
                ->toArray();
        } elseif ($isTeamScope) {
            $teamMembers = User::whereIn('id', $allowedIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id', 'department', 'position'])
                ->toArray();
        }

        // ── 2. Determine Selected Employee Target IDs ──────────────────────────
        $selectedUserId = $request->input('user_id');
        if ($selectedUserId && $selectedUserId !== 'all') {
            $targetUserId = (int) $selectedUserId;
            // Validate that logged-in user has permission to view this target user
            if (!$isAllScope && !in_array($targetUserId, $allowedIds)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized user selection.'], 403);
            }
            $targetIds = [$targetUserId];
        } else {
            if ($isAllScope) {
                $targetIds = User::where('is_active', true)->pluck('id')->toArray();
            } else {
                $targetIds = $allowedIds;
            }
        }

        // ── 3. Determine Date Range ────────────────────────────────────────────
        $period = $request->input('period', 'today'); // today, week, month, year, custom
        $now = Carbon::now();

        switch ($period) {
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate   = $now->copy()->endOfWeek();
                $periodLabel = 'This Week (' . $startDate->format('d M') . ' - ' . $endDate->format('d M') . ')';
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate   = $now->copy()->endOfMonth();
                $periodLabel = $now->format('F Y');
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate   = $now->copy()->endOfYear();
                $periodLabel = 'Year ' . $now->format('Y');
                break;
            case 'custom':
                $startStr = $request->input('start_date');
                $endStr   = $request->input('end_date');
                $startDate = $startStr ? Carbon::parse($startStr)->startOfDay() : $now->copy()->startOfMonth();
                $endDate   = $endStr ? Carbon::parse($endStr)->endOfDay() : $now->copy()->endOfDay();
                $periodLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
                break;
            case 'today':
            default:
                $startDate = $now->copy()->startOfDay();
                $endDate   = $now->copy()->endOfDay();
                $periodLabel = 'Today, ' . $now->format('d M Y');
                $period = 'today';
                break;
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr   = $endDate->toDateString();

        // ── 4. Aggregate Key Performance Indicators (KPIs) ──────────────────────
        
        // A. Visits
        $visitsCount = Visit::whereIn('user_id', $targetIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $completedVisitsCount = Visit::whereIn('user_id', $targetIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('check_out_time')
            ->count();

        // B. Orders
        $ordersQuery = Order::whereIn('user_id', $targetIds)
            ->whereBetween('order_date', [$startDateStr, $endDateStr]);

        $ordersCount = $ordersQuery->count();
        $ordersValue = (float) $ordersQuery->sum('total_amount');

        // C. Payments Collected
        $paymentsQuery = Payment::whereIn('collected_by', $targetIds)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $paymentsCount = $paymentsQuery->count();
        $paymentsTotal = (float) $paymentsQuery->sum('amount');
        $confirmedPaymentsTotal = (float) (clone $paymentsQuery)->where('status', 'confirmed')->sum('amount');

        // D. Expenses Logged
        $expensesQuery = ExpenseBill::whereIn('user_id', $targetIds)
            ->whereBetween('date', [$startDateStr, $endDateStr]);

        $expensesCount = $expensesQuery->count();
        $expensesTotal = (float) $expensesQuery->sum('amount');
        $approvedExpensesTotal = (float) (clone $expensesQuery)->where('status', 'approved')->sum('amount');
        $pendingExpensesTotal = (float) (clone $expensesQuery)->where('status', 'pending')->sum('amount');

        // E. Day Starts & KM Traveled
        $dayStarts = DayStart::whereIn('user_id', $targetIds)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->get();

        $workingDays = $dayStarts->pluck('date')->unique()->count();

        $totalKm = 0.0;
        foreach ($dayStarts as $ds) {
            if ($ds->closing_km && $ds->opening_km && $ds->closing_km > $ds->opening_km) {
                $totalKm += (float) ($ds->closing_km - $ds->opening_km);
            } elseif ($ds->gps_calculated_km) {
                $totalKm += (float) $ds->gps_calculated_km;
            }
        }

        // Active fieldworkers today
        $activeFieldworkersCount = DayStart::whereIn('user_id', $targetIds)
            ->where('date', Carbon::today()->toDateString())
            ->distinct('user_id')
            ->count();

        // ── 5. Activity Breakdown (Team vs Personal) ───────────────────────────
        $breakdown = [];
        $isMultiUserView = count($targetIds) > 1;

        if ($isMultiUserView) {
            // Group performance by employee
            $usersMap = User::whereIn('id', $targetIds)->get()->keyBy('id');

            // Visits per user
            $userVisits = Visit::whereIn('user_id', $targetIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('user_id', DB::raw('count(*) as count'))
                ->groupBy('user_id')
                ->pluck('count', 'user_id')
                ->toArray();

            // Orders per user
            $userOrders = Order::whereIn('user_id', $targetIds)
                ->whereBetween('order_date', [$startDateStr, $endDateStr])
                ->select('user_id', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            // Payments per user
            $userPayments = Payment::whereIn('collected_by', $targetIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('collected_by', DB::raw('sum(amount) as total'))
                ->groupBy('collected_by')
                ->pluck('total', 'collected_by')
                ->toArray();

            // Expenses per user
            $userExpenses = ExpenseBill::whereIn('user_id', $targetIds)
                ->whereBetween('date', [$startDateStr, $endDateStr])
                ->select('user_id', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            // Today's Day Start status per user
            $todayStarts = DayStart::whereIn('user_id', $targetIds)
                ->where('date', Carbon::today()->toDateString())
                ->get()
                ->keyBy('user_id');

            foreach ($targetIds as $tid) {
                $emp = $usersMap->get($tid);
                if (!$emp) continue;

                $dayStatus = 'Not Started';
                $todayDS = $todayStarts->get($tid);
                if ($todayDS) {
                    $dayStatus = $todayDS->end_time ? 'Completed' : 'Working';
                }

                $ord = $userOrders->get($tid);
                $exp = $userExpenses->get($tid);

                $breakdown[] = [
                    'user_id'         => $emp->id,
                    'name'            => $emp->name,
                    'employee_id'     => $emp->employee_id ?? ('#' . $emp->id),
                    'position'        => $emp->position ?? 'Field Worker',
                    'day_status'      => $dayStatus,
                    'visits_count'    => (int) ($userVisits[$tid] ?? 0),
                    'orders_count'    => (int) ($ord ? $ord->count : 0),
                    'orders_value'    => (float) ($ord ? $ord->total : 0),
                    'payments_total'  => (float) ($userPayments[$tid] ?? 0),
                    'expenses_total'  => (float) ($exp ? $exp->total : 0),
                    'expenses_count'  => (int) ($exp ? $exp->count : 0),
                ];
            }

            // Sort by visits and orders descending
            usort($breakdown, fn($a, $b) => ($b['visits_count'] + $b['orders_count']) <=> ($a['visits_count'] + $a['orders_count']));
        }

        // Fetch recent visits & activity history with notes, party, and user
        $recentVisits = Visit::whereIn('user_id', $targetIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['party', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($v) {
                return [
                    'type'            => 'visit',
                    'id'              => $v->id,
                    'title'           => $v->party ? $v->party->name : 'Visit',
                    'party_name'      => $v->party ? $v->party->name : null,
                    'party_city'      => $v->party ? $v->party->city : null,
                    'user_name'       => $v->user ? $v->user->name : null,
                    'subtitle'        => $v->purpose ?? 'General Visit',
                    'purpose'         => $v->purpose,
                    'notes'           => $v->notes ?? $v->remarks ?? null,
                    'time'            => $v->check_in_time ? $v->check_in_time->format('h:i A') : $v->created_at->format('h:i A'),
                    'check_out_time'  => $v->check_out_time ? $v->check_out_time->format('h:i A') : null,
                    'date'            => $v->created_at->format('d M Y'),
                    'status'          => $v->check_out_time ? 'Completed' : 'Checked In',
                    'is_success'      => (bool) $v->check_out_time,
                    'check_in_photo'  => $v->check_in_photo_url,
                ];
            });

        if (!$isMultiUserView) {
            $breakdown = $recentVisits;
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'scope'          => $scopeType,
                'is_multi_user'  => $isMultiUserView,
                'period'         => $period,
                'period_label'   => $periodLabel,
                'start_date'     => $startDateStr,
                'end_date'       => $endDateStr,
                'team_members'   => $teamMembers,
                'kpis'           => [
                    'visits_count'             => $visitsCount,
                    'completed_visits_count'   => $completedVisitsCount,
                    'orders_count'             => $ordersCount,
                    'orders_value'             => $ordersValue,
                    'payments_count'           => $paymentsCount,
                    'payments_total'           => $paymentsTotal,
                    'confirmed_payments_total' => $confirmedPaymentsTotal,
                    'expenses_count'           => $expensesCount,
                    'expenses_total'           => $expensesTotal,
                    'approved_expenses_total'  => $approvedExpensesTotal,
                    'pending_expenses_total'   => $pendingExpensesTotal,
                    'km_traveled'              => round($totalKm, 1),
                    'working_days'             => $workingDays,
                    'active_fieldworkers'      => $activeFieldworkersCount,
                    'total_team_count'         => count($targetIds),
                ],
                'breakdown'      => $breakdown,
                'activities'     => $recentVisits,
            ],
        ]);
    }
}
