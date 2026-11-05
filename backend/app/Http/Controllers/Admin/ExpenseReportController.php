<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\User;
use App\Models\Visit;
use App\Services\DataScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        $scopedUserIds = DataScope::visibleUserIds('expenses');

        // Fetch users and their expenses for the selected month (scoped)
        $usersQuery = User::visibleToUser()->where('is_active', true);

        $users = $usersQuery->with(['expenses' => function($q) use ($month) {
            $q->whereMonth('date', $month->month)
              ->whereYear('date', $month->year);
        }, 'expenses.category'])->get();

        $summary = $users->map(function($user) {
            // System Travel: Only final approved system-generated expenses are payable
            $totalTravel = $user->expenses->where('is_system_generated', true)->where('status', 'approved')->sum('amount');
            
            // Manual Approved: Only final approved manual expenses are payable
            $totalManualApproved = $user->expenses->where('is_system_generated', false)->where('status', 'approved')->sum('amount');
            
            // Manual Verified (Level 1): Manager verified, awaiting HR final approval
            $totalManualVerified = $user->expenses->where('is_system_generated', false)->where('status', 'temp_approved')->sum('amount');
            
            // Manual Pending (Level 1): Awaiting manager verification
            $totalManualPending = $user->expenses->where('is_system_generated', false)->where('status', 'pending')->sum('amount');
            
            // System Pending: System-generated travel not yet final approved
            $totalSystemPending = $user->expenses->where('is_system_generated', true)->whereIn('status', ['pending', 'temp_approved'])->sum('amount');
            
            return [
                'user'                  => $user,
                'total_travel'          => $totalTravel,
                'total_manual_approved' => $totalManualApproved,
                'total_manual_verified' => $totalManualVerified,
                'total_manual_pending'  => $totalManualPending,
                'total_system_pending'  => $totalSystemPending,
                'total_pending_all'     => $totalManualVerified + $totalManualPending + $totalSystemPending,
                'grand_total'           => $totalTravel + $totalManualApproved, // Only final approved amounts are payable
            ];
        });

        return view('admin.expenses.reports.monthly', compact('summary', 'monthInput'));
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