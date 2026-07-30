<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\User;
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
        $visits = \App\Models\Visit::with('party')->where('day_start_id', $day_start_id)->get();

        // Fetch expenses tied to this day
        $expenses = ExpenseBill::with('category')->where('day_start_id', $day_start_id)->get();

        return view('admin.expenses.reports.daily', compact('dayStart', 'visits', 'expenses'));
    }
}
