<?php

namespace App\Services;

use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\Leave;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Resolve date range from preset or custom dates.
     */
    public function resolveDateRange(?string $preset = 'this_month', ?string $from = null, ?string $to = null): array
    {
        $now = Carbon::today();

        switch ($preset) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $label = 'Today (' . $now->format('d M Y') . ')';
                break;

            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                $label = 'Yesterday (' . $start->format('d M Y') . ')';
                break;

            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                $label = 'This Week (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end   = $now->copy()->subWeek()->endOfWeek();
                $label = 'Last Week (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                $label = 'This Month (' . $now->format('F Y') . ')';
                break;

            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end   = $now->copy()->subMonth()->endOfMonth();
                $label = 'Last Month (' . $start->format('F Y') . ')';
                break;

            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                $label = 'This Year (' . $now->format('Y') . ')';
                break;

            case 'custom':
                $start = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
                $end   = $to   ? Carbon::parse($to)->endOfDay()     : $now->copy()->endOfDay();
                $label = $start->format('d M Y') . ' - ' . $end->format('d M Y');
                break;

            default:
                $preset = 'this_month';
                $start  = $now->copy()->startOfMonth();
                $end    = $now->copy()->endOfMonth();
                $label  = 'This Month (' . $now->format('F Y') . ')';
                break;
        }

        return [
            'preset'     => $preset,
            'start_date' => $start,
            'end_date'   => $end,
            'from'       => $start->format('Y-m-d'),
            'to'         => $end->format('Y-m-d'),
            'label'      => $label,
        ];
    }

    /**
     * Get Employee 360 Performance Matrix.
     */
    public function getEmployeePerformanceMatrix(array $dateRange, ?int $filterUserId = null, ?array $scopedUserIds = null): array
    {
        $startDate = $dateRange['start_date']->format('Y-m-d');
        $endDate   = $dateRange['end_date']->format('Y-m-d');

        // 1. Fetch relevant users
        $userQuery = User::query()->where('is_active', true);

        if ($scopedUserIds !== null) {
            $userQuery->whereIn('id', $scopedUserIds);
        } else {
            $userQuery->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Field Worker', 'field_worker', 'Employee', 'Manager', 'manager']);
            });
        }

        if ($filterUserId) {
            $userQuery->where('id', $filterUserId);
        }

        $users = $userQuery->with(['roles', 'reportingManager:id,name'])->orderBy('name')->get();
        $targetUserIds = $users->pluck('id')->toArray();

        if (empty($targetUserIds)) {
            return [
                'users_matrix' => collect(),
                'totals'       => $this->emptyTotals(),
            ];
        }

        // 2. Aggregate DayStarts
        $dayStartStats = DayStart::query()
            ->whereIn('user_id', $targetUserIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw("
                user_id,
                count(id) as days_started,
                sum(greatest(0, coalesce(closing_km, 0) - coalesce(opening_km, 0))) as total_km,
                sum(case when closing_km is null and date < CURRENT_DATE() then 1 else 0 end) as auto_closed_count
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 3. Aggregate Visits
        $visitStats = Visit::query()
            ->whereIn('user_id', $targetUserIds)
            ->whereBetween(DB::raw('date(check_in_time)'), [$startDate, $endDate])
            ->selectRaw("
                user_id,
                count(id) as total_visits,
                sum(case when status = 'completed' then 1 else 0 end) as completed_visits
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 4. Aggregate Orders
        $orderStats = Order::query()
            ->whereIn('user_id', $targetUserIds)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw("
                user_id,
                count(id) as total_orders,
                sum(coalesce(total_amount, 0)) as total_order_amount,
                sum(case when order_source = 'visit' or order_source is null then 1 else 0 end) as visit_orders_count,
                sum(case when order_source = 'visit' or order_source is null then total_amount else 0 end) as visit_orders_amount,
                sum(case when order_source = 'phone' and (is_after_day_end = false or is_after_day_end is null) then 1 else 0 end) as phone_shift_orders_count,
                sum(case when order_source = 'phone' and is_after_day_end = true then 1 else 0 end) as phone_night_orders_count,
                sum(case when order_source = 'phone' then total_amount else 0 end) as phone_orders_amount
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 5. Aggregate Payments Collected
        $paymentStats = Payment::query()
            ->whereIn('collected_by', $targetUserIds)
            ->whereBetween(DB::raw('date(coalesce(collection_datetime, created_at))'), [$startDate, $endDate])
            ->selectRaw("
                collected_by as user_id,
                count(id) as total_payments_count,
                sum(amount) as total_collected_amount,
                sum(case when status in ('confirmed', 'approved') then amount else 0 end) as confirmed_collected_amount
            ")
            ->groupBy('collected_by')
            ->get()
            ->keyBy('user_id');

        // 6. Aggregate Leaves
        $leaveStats = Leave::query()
            ->whereIn('user_id', $targetUserIds)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('from_date', [$startDate, $endDate])
                  ->orWhereBetween('to_date', [$startDate, $endDate]);
            })
            ->selectRaw("
                user_id,
                count(id) as total_leaves_count,
                sum(case when duration = 'half_day' then 0.5 else 1.0 end) as total_leave_days
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 7. Aggregate Expenses Claimed
        $expenseStats = ExpenseBill::query()
            ->whereIn('user_id', $targetUserIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw("
                user_id,
                count(id) as total_expense_claims,
                sum(amount) as total_claimed_amount,
                sum(case when status = 'approved' then amount else 0 end) as approved_expense_amount
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 8. Combine into unified matrix
        $matrix = $users->map(function ($user) use (
            $dayStartStats,
            $visitStats,
            $orderStats,
            $paymentStats,
            $leaveStats,
            $expenseStats
        ) {
            $ds = $dayStartStats->get($user->id);
            $vs = $visitStats->get($user->id);
            $os = $orderStats->get($user->id);
            $ps = $paymentStats->get($user->id);
            $ls = $leaveStats->get($user->id);
            $es = $expenseStats->get($user->id);

            $daysStarted       = (int) ($ds?->days_started ?? 0);
            $totalKm           = (float) ($ds?->total_km ?? 0);
            $autoClosedCount   = (int) ($ds?->auto_closed_count ?? 0);

            $totalVisits       = (int) ($vs?->total_visits ?? 0);
            $completedVisits   = (int) ($vs?->completed_visits ?? 0);

            $totalOrders       = (int) ($os?->total_orders ?? 0);
            $totalOrderAmount  = (float) ($os?->total_order_amount ?? 0);
            $visitOrders       = (int) ($os?->visit_orders_count ?? 0);
            $visitOrderAmount  = (float) ($os?->visit_orders_amount ?? 0);
            $phoneShiftOrders  = (int) ($os?->phone_shift_orders_count ?? 0);
            $phoneNightOrders  = (int) ($os?->phone_night_orders_count ?? 0);
            $phoneOrderAmount  = (float) ($os?->phone_orders_amount ?? 0);
            $totalPhoneOrders  = $phoneShiftOrders + $phoneNightOrders;

            $totalCollections  = (float) ($ps?->total_collected_amount ?? 0);
            $confirmedCollections = (float) ($ps?->confirmed_collected_amount ?? 0);

            $leaveDays         = (float) ($ls?->total_leave_days ?? 0);

            $claimedExpenses   = (float) ($es?->total_claimed_amount ?? 0);
            $approvedExpenses  = (float) ($es?->approved_expense_amount ?? 0);

            $avgVisitsPerDay   = $daysStarted > 0 ? round($totalVisits / $daysStarted, 1) : 0;

            return [
                'user_id'              => $user->id,
                'name'                 => $user->name,
                'employee_code'        => $user->employee_id ?? 'EMP-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                'role'                 => $user->roles->first()?->name ?? 'Field Worker',
                'manager'              => $user->reportingManager?->name ?? '—',
                'days_started'         => $daysStarted,
                'total_km'             => $totalKm,
                'auto_closed_count'    => $autoClosedCount,
                'total_visits'         => $totalVisits,
                'completed_visits'     => $completedVisits,
                'avg_visits_per_day'   => $avgVisitsPerDay,
                'total_orders'         => $totalOrders,
                'total_order_amount'   => $totalOrderAmount,
                'visit_orders_count'   => $visitOrders,
                'visit_orders_amount'  => $visitOrderAmount,
                'phone_shift_orders'   => $phoneShiftOrders,
                'phone_night_orders'   => $phoneNightOrders,
                'total_phone_orders'   => $totalPhoneOrders,
                'phone_orders_amount'  => $phoneOrderAmount,
                'total_collections'    => $totalCollections,
                'confirmed_collections'=> $confirmedCollections,
                'leave_days'           => $leaveDays,
                'claimed_expenses'     => $claimedExpenses,
                'approved_expenses'    => $approvedExpenses,
            ];
        });

        // 9. Compute Overall Totals
        $totals = [
            'total_employees'      => $matrix->count(),
            'active_employees'     => $matrix->where('days_started', '>', 0)->count(),
            'total_days_started'   => $matrix->sum('days_started'),
            'total_km'             => $matrix->sum('total_km'),
            'total_visits'         => $matrix->sum('total_visits'),
            'total_orders'         => $matrix->sum('total_orders'),
            'total_order_amount'   => $matrix->sum('total_order_amount'),
            'total_visit_orders'   => $matrix->sum('visit_orders_count'),
            'total_phone_shift'    => $matrix->sum('phone_shift_orders'),
            'total_phone_night'    => $matrix->sum('phone_night_orders'),
            'total_phone_orders'   => $matrix->sum('total_phone_orders'),
            'total_collections'    => $matrix->sum('total_collections'),
            'confirmed_collections'=> $matrix->sum('confirmed_collections'),
            'total_leave_days'     => $matrix->sum('leave_days'),
            'total_expenses'       => $matrix->sum('claimed_expenses'),
            'approved_expenses'    => $matrix->sum('approved_expenses'),
        ];

        return [
            'users_matrix' => $matrix,
            'totals'       => $totals,
        ];
    }

    /**
     * Get 360° Scorecard for a specific employee.
     */
    public function getEmployeeScorecard(int $userId, array $dateRange): array
    {
        $user = User::with(['roles', 'reportingManager'])->findOrFail($userId);
        $matrixData = $this->getEmployeePerformanceMatrix($dateRange, $userId);
        $employeeKPI = $matrixData['users_matrix']->first() ?? [];

        $startDate = $dateRange['start_date']->format('Y-m-d');
        $endDate   = $dateRange['end_date']->format('Y-m-d');

        // Recent DayStarts in date range
        $dayStarts = DayStart::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Recent Orders in date range
        $orders = Order::where('user_id', $userId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->with(['party:id,name,party_code', 'items'])
            ->orderBy('order_date', 'desc')
            ->limit(50)
            ->get();

        // Recent Visits in date range
        $visits = Visit::where('user_id', $userId)
            ->whereBetween(DB::raw('date(check_in_time)'), [$startDate, $endDate])
            ->with('party:id,name,city')
            ->orderBy('check_in_time', 'desc')
            ->limit(50)
            ->get();

        // Approved Leaves
        $leaves = Leave::where('user_id', $userId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('from_date', [$startDate, $endDate])
                  ->orWhereBetween('to_date', [$startDate, $endDate]);
            })
            ->with('leaveType')
            ->orderBy('from_date', 'desc')
            ->get();

        // 12-Month Sales & Orders Trend (Last 12 months)
        $twelveMonthsAgo = Carbon::today()->subMonths(11)->startOfMonth();
        $monthlyTrend = Order::where('user_id', $userId)
            ->where('order_date', '>=', $twelveMonthsAgo)
            ->selectRaw("
                DATE_FORMAT(order_date, '%Y-%m') as ym,
                DATE_FORMAT(order_date, '%b %y') as month_label,
                count(*) as orders_count,
                sum(total_amount) as total_amount,
                sum(case when order_source = 'phone' then 1 else 0 end) as phone_orders_count
            ")
            ->groupBy('ym', 'month_label')
            ->orderBy('ym', 'asc')
            ->get();

        return [
            'user'          => $user,
            'kpi'           => $employeeKPI,
            'day_starts'    => $dayStarts,
            'orders'        => $orders,
            'visits'        => $visits,
            'leaves'        => $leaves,
            'monthly_trend' => $monthlyTrend,
        ];
    }

    /**
     * Get Daily Operational Ledger.
     */
    public function getDailyLedger(array $dateRange, ?int $filterUserId = null, ?array $scopedUserIds = null)
    {
        $startDate = $dateRange['start_date']->format('Y-m-d');
        $endDate   = $dateRange['end_date']->format('Y-m-d');

        $query = DayStart::query()
            ->with(['user:id,name,employee_id', 'expenses', 'visits'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($scopedUserIds !== null) {
            $query->whereIn('user_id', $scopedUserIds);
        }

        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();
    }

    private function emptyTotals(): array
    {
        return [
            'total_employees'      => 0,
            'active_employees'     => 0,
            'total_days_started'   => 0,
            'total_km'             => 0,
            'total_visits'         => 0,
            'total_orders'         => 0,
            'total_order_amount'   => 0,
            'total_visit_orders'   => 0,
            'total_phone_shift'    => 0,
            'total_phone_night'    => 0,
            'total_phone_orders'   => 0,
            'total_collections'    => 0,
            'confirmed_collections'=> 0,
            'total_leave_days'     => 0,
            'total_expenses'       => 0,
            'approved_expenses'    => 0,
        ];
    }
}
