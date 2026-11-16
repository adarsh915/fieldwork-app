<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\User;
use App\Models\Visit;
use App\Services\DataScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExpenseReportController extends Controller
{
    public function monthlySummary(Request $request)
    {
        if (!$request->filled('month')) {
            $latestDate = ExpenseBill::max('date');
            $monthInput = $latestDate ? Carbon::parse($latestDate)->format('Y-m') : Carbon::now()->format('Y-m');
        } else {
            $monthInput = $request->input('month');
        }
        $month = Carbon::parse($monthInput);
        $selectedUserId = $request->input('user_id');

        $scopedUserIds = DataScope::visibleUserIds('expenses');

        // Calculate aggregated monthly totals for the overview cards / totals
        $expensesQuery = ExpenseBill::query()
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year);

        if ($scopedUserIds !== null) {
            $expensesQuery->whereIn('user_id', $scopedUserIds);
        }

        if ($selectedUserId) {
            $expensesQuery->where('user_id', $selectedUserId);
        }

        $allTravel = (float) (clone $expensesQuery)->where('is_system_generated', true)->where('status', 'approved')->sum('amount');
        $allManualApproved = (float) (clone $expensesQuery)->where('is_system_generated', false)->where('status', 'approved')->sum('amount');
        $allManualVerified = (float) (clone $expensesQuery)->where('is_system_generated', false)->where('status', 'temp_approved')->sum('amount');
        $allManualPending = (float) (clone $expensesQuery)->where('is_system_generated', false)->where('status', 'pending')->sum('amount');
        $allGrandTotal = $allTravel + $allManualApproved;

        $totals = [
            'allTravel' => $allTravel,
            'allManualApproved' => $allManualApproved,
            'allGrandTotal' => $allGrandTotal,
            'allManualVerified' => $allManualVerified,
            'allManualPending' => $allManualPending,
        ];

        $workersQuery = User::visibleToUser()
            ->where('is_active', true)
            ->select('id', 'name', 'employee_id')
            ->with('roles:id,name');

        if ($scopedUserIds !== null) {
            $workersQuery->whereIn('id', $scopedUserIds);
        }
        $workers = $workersQuery->orderBy('name')->get();

        return view('admin.expenses.reports.monthly', compact('monthInput', 'totals', 'workers', 'selectedUserId'));
    }

    /**
     * AJAX endpoint for Monthly Expense Summary DataTable (Server-Side).
     */
    public function monthlySummaryData(Request $request): JsonResponse
    {
        $monthInput = $request->input('month');
        if (!$monthInput) {
            $latestDate = ExpenseBill::max('date');
            $monthInput = $latestDate ? Carbon::parse($latestDate)->format('Y-m') : Carbon::now()->format('Y-m');
        }
        $month = Carbon::parse($monthInput);

        $scopedUserIds = DataScope::visibleUserIds('expenses');

        $travelSub = ExpenseBill::selectRaw('COALESCE(SUM(amount), 0)')
            ->whereColumn('expense_bills.user_id', 'users.id')
            ->whereMonth('expense_bills.date', $month->month)
            ->whereYear('expense_bills.date', $month->year)
            ->where('expense_bills.is_system_generated', true)
            ->where('expense_bills.status', 'approved');

        $manualApprovedSub = ExpenseBill::selectRaw('COALESCE(SUM(amount), 0)')
            ->whereColumn('expense_bills.user_id', 'users.id')
            ->whereMonth('expense_bills.date', $month->month)
            ->whereYear('expense_bills.date', $month->year)
            ->where('expense_bills.is_system_generated', false)
            ->where('expense_bills.status', 'approved');

        $manualVerifiedSub = ExpenseBill::selectRaw('COALESCE(SUM(amount), 0)')
            ->whereColumn('expense_bills.user_id', 'users.id')
            ->whereMonth('expense_bills.date', $month->month)
            ->whereYear('expense_bills.date', $month->year)
            ->where('expense_bills.is_system_generated', false)
            ->where('expense_bills.status', 'temp_approved');

        $manualPendingSub = ExpenseBill::selectRaw('COALESCE(SUM(amount), 0)')
            ->whereColumn('expense_bills.user_id', 'users.id')
            ->whereMonth('expense_bills.date', $month->month)
            ->whereYear('expense_bills.date', $month->year)
            ->where('expense_bills.is_system_generated', false)
            ->where('expense_bills.status', 'pending');

        $query = User::visibleToUser()
            ->where('is_active', true)
            ->select('users.id', 'users.name', 'users.employee_id')
            ->selectSub($travelSub, 'total_travel')
            ->selectSub($manualApprovedSub, 'total_manual_approved')
            ->selectRaw('((' . $travelSub->toSql() . ') + (' . $manualApprovedSub->toSql() . ')) as grand_total', array_merge($travelSub->getBindings(), $manualApprovedSub->getBindings()))
            ->selectSub($manualVerifiedSub, 'total_manual_verified')
            ->selectSub($manualPendingSub, 'total_manual_pending');

        if ($scopedUserIds !== null) {
            $query->whereIn('users.id', $scopedUserIds);
        }

        $selectedUserId = $request->input('user_id');
        if ($selectedUserId) {
            $query->where('users.id', $selectedUserId);
        }

        $search = $request->input('search.value');
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }
        if (!empty($search) && is_string($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.employee_id', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->editColumn('worker', function ($user) {
                return [
                    'name' => $user->name,
                    'employee_id' => $user->employee_id && $user->employee_id !== '—' ? $user->employee_id : null,
                ];
            })
            ->editColumn('total_travel', function ($user) {
                $val = (float) $user->total_travel;
                return [
                    'formatted' => '₹' . number_format($val, 2),
                    'raw' => $val,
                ];
            })
            ->editColumn('total_manual_approved', function ($user) {
                $val = (float) $user->total_manual_approved;
                return [
                    'formatted' => '₹' . number_format($val, 2),
                    'raw' => $val,
                ];
            })
            ->editColumn('grand_total', function ($user) {
                $val = (float) $user->grand_total;
                return [
                    'formatted' => '₹' . number_format($val, 2),
                    'raw' => $val,
                ];
            })
            ->editColumn('total_manual_verified', function ($user) {
                $val = (float) $user->total_manual_verified;
                return [
                    'formatted' => $val > 0 ? '₹' . number_format($val, 2) : '—',
                    'raw' => $val,
                ];
            })
            ->editColumn('total_manual_pending', function ($user) {
                $val = (float) $user->total_manual_pending;
                return [
                    'formatted' => $val > 0 ? '₹' . number_format($val, 2) : '—',
                    'raw' => $val,
                ];
            })
            ->addColumn('actions', function ($user) use ($monthInput) {
                return [
                    'statement_url' => route('admin.expenses.reports.statement', ['user_id' => $user->id, 'month' => $monthInput]),
                ];
            })
            ->orderColumn('worker', 'users.name $1')
            ->make(true);
    }

    public function dailyReceipt(Request $request, $day_start_id)
    {
        $dayStart = DayStart::with([
            'expenses' => function($q) {
                // STRICT: Only final approved expenses (both manual and system-generated)
                $q->where('status', 'approved');
            },
            'expenses.category',
            'expenses.items',
            'user'
        ])->findOrFail($day_start_id);

        if (!DataScope::canAction('expenses', $dayStart->user_id)) {
            abort(403, 'Unauthorized access to this expense receipt.');
        }

        $visits = Visit::with('party')
            ->where('user_id', $dayStart->user_id)
            ->whereDate('check_in_time', Carbon::parse($dayStart->date)->toDateString())
            ->orderBy('check_in_time', 'desc')
            ->get();

        $expenses = ExpenseBill::with(['category', 'items'])->where('day_start_id', $day_start_id)->get();

        return view('admin.expenses.reports.daily', compact('dayStart', 'visits', 'expenses'));
    }

    public function workerStatement(Request $request, $user_id)
    {
        if (!DataScope::canAction('expenses', (int) $user_id)) {
            abort(403, 'Unauthorized access to this worker statement.');
        }

        $monthInput = $request->input('month', Carbon::now()->format('Y-m'));
        $month = Carbon::parse($monthInput);
        
        $user = User::findOrFail($user_id);

        // STRICT FINAL APPROVAL ONLY: Statement includes ONLY final approved expenses
        // This ensures the statement represents the actual payable amount after full approval workflow
        // Both manual and system-generated expenses must be status = 'approved' to appear in statement
        $expenses = ExpenseBill::with('category')
            ->where('user_id', $user_id)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->where('status', 'approved')  // Only final approved expenses (both manual and system-generated)
            ->orderBy('date', 'desc')
            ->get();

        $groupedByDate = $expenses->groupBy(function($expense) {
            return Carbon::parse($expense->date)->format('Y-m-d');
        });

        $statementData = $groupedByDate->map(function($dayExpenses, $dateString) {
            $foodTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Food' && !$e->is_system_generated)->sum('amount');
            $hotelTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Hotel' && !$e->is_system_generated)->sum('amount');
            $travelTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Travel' && !$e->is_system_generated)->sum('amount');
            $miscTotal = $dayExpenses->filter(fn($e) => optional($e->category)->name === 'Misc' && !$e->is_system_generated)->sum('amount');
            
            $otherManualTotal = $dayExpenses->filter(fn($e) => !$e->is_system_generated && !in_array(optional($e->category)->name, ['Food', 'Hotel', 'Travel', 'Misc']))->sum('amount');
            $systemTravelTotal = $dayExpenses->filter(fn($e) => $e->is_system_generated)->sum('amount');

            return [
                'date_formatted' => Carbon::parse($dateString)->format('d M Y'),
                'food' => $foodTotal,
                'hotel' => $hotelTotal,
                'travel' => $travelTotal,
                'misc' => $miscTotal,
                'other' => $otherManualTotal,
                'system_travel' => $systemTravelTotal,
                'day_total' => $foodTotal + $hotelTotal + $travelTotal + $miscTotal + $otherManualTotal + $systemTravelTotal,
            ];
        })->values();

        $totals = [
            'food' => $statementData->sum('food'),
            'hotel' => $statementData->sum('hotel'),
            'travel' => $statementData->sum('travel'),
            'misc' => $statementData->sum('misc'),
            'other' => $statementData->sum('other'),
            'system_travel' => $statementData->sum('system_travel'),
            'grand_total' => $statementData->sum('day_total'),
        ];

        return view('admin.expenses.reports.statement', compact('user', 'month', 'monthInput', 'statementData', 'totals'));
    }
}