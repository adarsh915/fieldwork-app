<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseReportController extends Controller
{
    public function monthlySummary(Request $request)
    {
        $monthInput = $request->input('month', Carbon::now()->format('Y-m'));
        $month = Carbon::parse($monthInput);

        // Fetch users and their expenses for the selected month
        $users = User::role('Field Worker')->with(['expenses' => function($q) use ($month) {
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
            'user', 
            'vehicleType', 
            'parties.party', // From DayStartParty model (if used)
        ])->findOrFail($day_start_id);

        // Fetch Visits directly if day_start_id is logged on Visit
        $visits = Visit::with('party')->where('day_start_id', $day_start_id)->get();

        // Fetch expenses tied to this day
        $expenses = ExpenseBill::with(['category', 'items'])->where('day_start_id', $day_start_id)->get();

        return view('admin.expenses.reports.daily', compact('dayStart', 'visits', 'expenses'));
    }

    public function workerStatement(Request $request, $user_id)
    {
        $monthInput = $request->input('month', Carbon::now()->format('Y-m'));
        $month = Carbon::parse($monthInput);
        
        $user = User::findOrFail($user_id);

        $dayStarts = DayStart::with(['expenses' => function($q) {
            $q->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('is_system_generated', true);
            });
        }, 'expenses.category'])
        ->where('user_id', $user_id)
        ->whereMonth('date', $month->month)
        ->whereYear('date', $month->year)
        ->orderBy('date', 'asc')
        ->get();

        $statementData = $dayStarts->map(function($dayStart) {
            $foodTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Food' && !$e->is_system_generated)->sum('amount');
            $hotelTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Hotel' && !$e->is_system_generated)->sum('amount');
            $travelTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Travel' && !$e->is_system_generated)->sum('amount');
            $miscTotal = $dayStart->expenses->filter(fn($e) => optional($e->category)->name === 'Misc' && !$e->is_system_generated)->sum('amount');
            
            // For any category that might not match the defaults above
            $otherManualTotal = $dayStart->expenses->filter(fn($e) => !$e->is_system_generated && !in_array(optional($e->category)->name, ['Food', 'Hotel', 'Travel', 'Misc']))->sum('amount');
            
            $systemTravelTotal = $dayStart->expenses->filter(fn($e) => $e->is_system_generated)->sum('amount');

            return [
                'dayStart' => $dayStart,
                'food' => $foodTotal,
                'hotel' => $hotelTotal,
                'travel' => $travelTotal,
                'misc' => $miscTotal,
                'other' => $otherManualTotal,
                'system_travel' => $systemTravelTotal,
                'day_total' => $foodTotal + $hotelTotal + $travelTotal + $miscTotal + $otherManualTotal + $systemTravelTotal,
            ];
        });

        // Filter out days with 0 expenses if desired, but showing all days is better for a complete statement.
        
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
