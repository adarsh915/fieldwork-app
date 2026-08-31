<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBill;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseBill::with(['user', 'category', 'items']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->whereMonth('date', $month->month)
                  ->whereYear('date', $month->year);
        }

        $expenses = $query->orderBy('date', 'desc')->paginate(15);
        $users = User::role('Field Worker')->get();

        return view('admin.expenses.index', compact('expenses', 'users'));
    }

    public function updateStatus(Request $request, ExpenseBill $expense)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $expense->update([
            'status' => $validated['status'],
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Expense status updated successfully.');
    }
}
