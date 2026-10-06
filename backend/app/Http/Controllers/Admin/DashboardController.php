<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Visit;
use App\Services\DataScope;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Initial page load — only data needed for first paint.
     * KPI numbers are fetched via AJAX from statsJson().
     */
    public function index()
    {
        return view('admin.dashboard', [
            'recentUsers'  => $this->scopedUsersQuery()->with('roles')->latest()->limit(5)->get(),
            'recentVisits' => $this->scopedVisitsQuery()->with(['user', 'party', 'dayStart'])->latest('check_in_time')->limit(6)->get(),
        ]);
    }

    /**
     * AJAX: GET /admin/dashboard/stats
     * Returns all KPI counts as JSON for async card rendering.
     */
    public function statsJson(): JsonResponse
    {
        $visitQuery = $this->scopedVisitsQuery();
        $orderQuery = $this->scopedOrdersQuery();

        return response()->json([
            'users'            => $this->scopedUsersQuery()->count(),
            'parties'          => $this->scopedPartiesQuery()->count(),
            'visits'           => (clone $visitQuery)->count(),
            'completed_visits' => (clone $visitQuery)->where(function ($q) {
                $q->where('status', 'completed')->orWhereNotNull('check_out_time');
            })->count(),
            'orders'           => (clone $orderQuery)->count(),
            'phone_orders'     => (clone $orderQuery)->where('order_source', 'phone')->count(),
            'visit_orders'     => (clone $orderQuery)->where(function ($q) {
                $q->where('order_source', 'visit')->orWhereNull('order_source');
            })->count(),
            'order_value'      => (clone $orderQuery)->sum('total_amount'),
            'roles'            => Role::count(),
        ]);
    }

    // ─── Scoped query helpers ──────────────────────────────────────────────────

    private function scopedUsersQuery()
    {
        $ids = DataScope::visibleUserIds('employees');
        $q   = User::query();
        if ($ids !== null) $q->whereIn('id', $ids);
        return $q;
    }

    private function scopedPartiesQuery()
    {
        $q = Customer::query();
        DataScope::applyParties($q);
        return $q;
    }

    private function scopedVisitsQuery()
    {
        $ids = DataScope::visibleUserIds('visits');
        $q   = Visit::query();
        if ($ids !== null) $q->whereIn('user_id', $ids);
        return $q;
    }

    private function scopedOrdersQuery()
    {
        $ids = DataScope::visibleUserIds('orders');
        $q   = Order::query();
        if ($ids !== null) $q->whereIn('user_id', $ids);
        return $q;
    }
}

