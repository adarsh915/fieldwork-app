<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Visit;
use App\Models\Transaction;
use App\Models\PartyEmployeeMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldWorkerManagementController extends Controller
{
    /**
     * Field Worker Management Dashboard - Overview of all field workers with stats
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::role('Field Worker')
            ->with(['assignments' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $fieldWorkers = $query->paginate(15);

        // Calculate stats for each field worker
        $fieldWorkers->each(function ($fw) {
            $fw->stats = $this->getFieldWorkerStats($fw->id);
        });

        $departments = User::role('Field Worker')->distinct()->pluck('department');

        return view('admin.field-workers.index', compact('fieldWorkers', 'departments'));
    }

    /**
     * Get comprehensive stats for a specific field worker
     */
    private function getFieldWorkerStats($userId)
    {
        $assignments = PartyEmployeeMap::where('employee_id', $userId)
            ->where('is_active', true)
            ->pluck('party_id');

        return [
            'total_parties' => $assignments->count(),
            'total_orders' => Order::whereIn('party_id', $assignments)->count(),
            'pending_orders' => Order::whereIn('party_id', $assignments)
                ->where('status', 'pending')->count(),
            'total_visits' => Visit::where('user_id', $userId)->count(),
            'pending_payments' => Transaction::whereIn('party_id', $assignments)
                ->where('status', 'pending')
                ->sum('amount'),
            'total_revenue' => Order::whereIn('party_id', $assignments)
                ->sum('total_amount'),
            'avg_order_value' => Order::whereIn('party_id', $assignments)->avg('total_amount') ?? 0,
        ];
    }

    /**
     * Show detailed breakdown of field worker metrics
     */
    public function showMetrics($id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('view', $fieldWorker);

        $activeAssignments = PartyEmployeeMap::where('employee_id', $id)
            ->where('is_active', true)
            ->with(['party'])
            ->orderBy('assigned_at', 'desc')
            ->paginate(10, ['*'], 'parties');

        $orders = Order::where('user_id', $id)
            ->with(['party'])
            ->orderBy('order_date', 'desc')
            ->paginate(10, ['*'], 'orders');

        $stats = $this->getFieldWorkerStats($id);

        return view('admin.field-workers.metrics', compact('fieldWorker', 'activeAssignments', 'orders', 'stats'));
    }

    /**
     * Show Recent Visits with details
     */
    public function showVisits($id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('view', $fieldWorker);

        $visits = Visit::where('user_id', $id)
            ->with(['party'])
            ->orderBy('check_in_time', 'desc')
            ->paginate(15);

        $upcomingVisits = Visit::where('user_id', $id)
            ->where('check_in_time', '>', now())
            ->with(['party'])
            ->orderBy('check_in_time', 'asc')
            ->get();

        return view('admin.field-workers.visits', compact('fieldWorker', 'visits', 'upcomingVisits'));
    }

    /**
     * Show Visit Details (modal/popup details)
     */
    public function visitDetails($fieldWorkerId, $visitId)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($fieldWorkerId);
        $this->authorize('view', $fieldWorker);

        $visit = Visit::where('user_id', $fieldWorkerId)
            ->with(['party', 'user'])
            ->findOrFail($visitId);

        // Get related data for this visit
        $partyOrders = Order::where('party_id', $visit->party_id)
            ->where('user_id', $fieldWorkerId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $partyTransactions = Transaction::where('party_id', $visit->party_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'visit' => $visit,
                'party' => $visit->party,
                'recent_orders' => $partyOrders,
                'recent_transactions' => $partyTransactions,
            ]);
        }

        return view('admin.field-workers.visit-details', compact('fieldWorker', 'visit', 'partyOrders', 'partyTransactions'));
    }

    /**
     * Show Recent Transactions
     */
    public function showTransactions($id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('view', $fieldWorker);

        // Get parties assigned to this field worker
        $assignedParties = PartyEmployeeMap::where('employee_id', $id)
            ->where('is_active', true)
            ->pluck('party_id');

        $transactions = Transaction::whereIn('party_id', $assignedParties)
            ->with(['party'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);

        $pendingAmount = Transaction::whereIn('party_id', $assignedParties)
            ->where('status', 'pending')
            ->sum('amount');

        return view('admin.field-workers.transactions', compact('fieldWorker', 'transactions', 'pendingAmount'));
    }

    /**
     * Show field worker details for editing (modal or page)
     */
    public function edit($id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('update', $fieldWorker);

        return view('admin.field-workers.edit', compact('fieldWorker'));
    }

    /**
     * Update field worker details
     */
    public function update(Request $request, $id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('update', $fieldWorker);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $fieldWorker->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Field worker updated successfully',
            'fieldWorker' => $fieldWorker,
        ]);
    }

    /**
     * Get parties assigned to field worker (for management)
     */
    public function getAssignedParties($id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('view', $fieldWorker);

        $parties = PartyEmployeeMap::where('employee_id', $id)
            ->where('is_active', true)
            ->with(['party'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'fieldWorker' => $fieldWorker,
            'parties' => $parties->map(function ($assignment) {
                return [
                    'id' => $assignment->party->id,
                    'party_code' => $assignment->party->party_code,
                    'name' => $assignment->party->name,
                    'city' => $assignment->party->city,
                    'category' => $assignment->party->category,
                    'assigned_at' => $assignment->assigned_at->format('M d, Y'),
                    'duration_days' => $assignment->assigned_at->diffInDays(now()),
                ];
            }),
        ]);
    }

    /**
     * Get field worker performance metrics (charts data)
     */
    public function getPerformanceMetrics($id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('view', $fieldWorker);

        $assignedParties = PartyEmployeeMap::where('employee_id', $id)
            ->where('is_active', true)
            ->pluck('party_id');

        // Last 6 months performance
        $months = [];
        $ordersCount = [];
        $visitsCounts = [];
        $revenueData = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M Y');

            $months[] = $monthLabel;

            $ordersCount[] = Order::where('user_id', $id)
                ->whereBetween('order_date', [$monthStart, $monthEnd])
                ->count();

            $visitsCounts[] = Visit::where('user_id', $id)
                ->whereBetween('check_in_time', [$monthStart, $monthEnd])
                ->count();

            $revenueData[] = Order::where('user_id', $id)
                ->whereBetween('order_date', [$monthStart, $monthEnd])
                ->sum('total_amount');
        }

        return response()->json([
            'success' => true,
            'months' => $months,
            'orders' => $ordersCount,
            'visits' => $visitsCounts,
            'revenue' => $revenueData,
        ]);
    }

    /**
     * Export field worker report
     */
    public function exportReport($id)
    {
        $fieldWorker = User::role('Field Worker')->findOrFail($id);
        $this->authorize('view', $fieldWorker);

        $stats = $this->getFieldWorkerStats($id);
        $assignedParties = PartyEmployeeMap::where('employee_id', $id)
            ->where('is_active', true)
            ->with(['party'])
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fieldworker_report_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($fieldWorker, $stats, $assignedParties) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ["\xEF\xBB\xBF"]); // BOM for UTF-8

            // Header
            fputcsv($file, ['FIELD WORKER REPORT']);
            fputcsv($file, [$fieldWorker->name]);
            fputcsv($file, []);

            // Stats
            fputcsv($file, ['PERFORMANCE METRICS']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Parties Assigned', $stats['total_parties']]);
            fputcsv($file, ['Total Orders', $stats['total_orders']]);
            fputcsv($file, ['Pending Orders', $stats['pending_orders']]);
            fputcsv($file, ['Total Visits', $stats['total_visits']]);
            fputcsv($file, ['Pending Payments', $stats['pending_payments']]); 
            fputcsv($file, ['Total Revenue', $stats['total_revenue']]);
            fputcsv($file, ['Average Order Value', round($stats['avg_order_value'], 2)]);
            fputcsv($file, []);

            // Assigned Parties
            fputcsv($file, ['ASSIGNED PARTIES']);
            fputcsv($file, ['Party Code', 'Party Name', 'City', 'Category', 'Assigned Since']);
            foreach ($assignedParties as $assignment) {
                fputcsv($file, [
                    $assignment->party->party_code,
                    $assignment->party->name,
                    $assignment->party->city,
                    $assignment->party->category,
                    $assignment->assigned_at->format('M d, Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
