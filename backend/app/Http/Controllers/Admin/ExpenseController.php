<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBill;
use App\Models\User;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseBill::with(['user', 'category', 'items']);

        // Data scoping: restrict to visible team members
        $visibleIds = DataScope::visibleUserIds('expenses');
        if ($visibleIds !== null) {
            $query->whereIn('user_id', $visibleIds);
        }

        if ($request->filled('user_id')) {
            if ($visibleIds === null || in_array($request->user_id, $visibleIds)) {
                $query->where('user_id', $request->user_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('timing')) {
            if ($request->timing === 'after_day_end') {
                $query->where('is_after_day_end', true);
            } elseif ($request->timing === 'during_shift') {
                $query->where('is_after_day_end', false);
            }
        }

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->whereMonth('date', $month->month)
                  ->whereYear('date', $month->year);
        }

        $expenses = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Scoped user dropdown — only show visible team members
        $users = $visibleIds === null
            ? User::where('is_active', true)->orderBy('name')->get()
            : User::whereIn('id', $visibleIds)->where('is_active', true)->orderBy('name')->get();

        return view('admin.expenses.index', compact('expenses', 'users'));
    }

    public function updateStatus(Request $request, ExpenseBill $expense)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $expense->update([
            'status'      => $validated['status'],
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Expense status updated successfully.');
    }
}
