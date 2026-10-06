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
        $scopedUserIds = DataScope::visibleUserIds('expenses');

        if (!$request->filled('month')) {
            $latestDate = ExpenseBill::max('date');
            $monthInput = $latestDate ? Carbon::parse($latestDate)->format('Y-m') : Carbon::now()->format('Y-m');
        } else {
            $monthInput = $request->input('month');
        }
        $month = Carbon::parse($monthInput);

        // Fetch users and their expenses for the selected month (scoped)
        $usersQuery = User::where('is_active', true);
        if ($scopedUserIds !== null) {
            $usersQuery->whereIn('id', $scopedUserIds);
        } else {
            $usersQuery->whereHas('roles', function($q) {
                $q->whereIn('name', ['Field Worker', 'field_worker', 'Employee']);
            });
        }

        $users = $usersQuery->with(['expenses' => function($q) use ($month) {
            $q->whereMonth('date', $month->month)
              ->whereYear('date', $month->year);
        }, 'expenses.category'])->get();

        $summary = $users->map(function($user) {
            $totalTravel = $user->expenses->where('is_system_generated', true)->sum('amount');
            $totalManualApproved = $user->expenses->where('is_system_generated', false)->where('status', 'approved')->sum('amount');
            $totalManualPending = $user->expenses->where('is_system_generated', false)->where('status', 'pending')->sum('amount');
            
            return [
                'user' => $user,
                'total_travel' => $totalTravel,
                'total_manual_approved' => $totalManualApproved,
                'total_manual_pending' => $totalManualPending,
                'grand_total' => $totalTravel + $totalManualApproved
            ];
        });

        return view('admin.expenses.reports.monthly', compact('summary', 'monthInput'));
    }

    public function dailyReceipt(Request $request, $day_start_id)
    {
        $dayStart = DayStart::with([
            'expenses' => function($q) {
                $q->where(function($query) {
                    $query->where('status', 'approved')
                          ->orWhere('is_system_generated', true);
                });
            },
            'expenses.category',
            'expenses.items',
            'user'
        ])->findOrFail($day_start_id);

        $scopedUserIds = DataScope::visibleUserIds('expenses');
        if ($scopedUserIds !== null && !in_array($dayStart->user_id, $scopedUserIds)) {
            abort(403, 'Unauthorized access to this expense receipt.');
        }

        $visits = Visit::with('party')
            ->where('user_id', $dayStart->user_id)
            ->whereDate('check_in_time', Carbon::parse($dayStart->date)->toDateString())
            ->orderBy('check_in_time', 'asc')
            ->get();

        $expenses = ExpenseBill::with(['category', 'items'])->where('day_start_id', $day_start_id)->get();

        return view('admin.expenses.reports.daily', compact('dayStart', 'visits', 'expenses'));
    }

    public function workerStatement(Request $request, $user_id)
    {
        $scopedUserIds = DataScope::visibleUserIds('expenses');
        if ($scopedUserIds !== null && !in_array((int) $user_id, $scopedUserIds, true)) {
            abort(403, 'Unauthorized access to this worker statement.');
        }

        $monthInput = $request->input('month', Carbon::now()->format('Y-m'));
        $month = Carbon::parse($monthInput);
        
        $user = User::findOrFail($user_id);

        $expenses = ExpenseBill::with('category')
            ->where('user_id', $user_id)
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('is_system_generated', true);
            })
            ->orderBy('date', 'asc')
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