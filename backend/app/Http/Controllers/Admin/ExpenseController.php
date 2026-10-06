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
        $query = ExpenseBill::with(['user.roles', 'category', 'items']);

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

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $expenses = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'records' => $expenses->map(function ($expense) {
                    return [
                        'id'                  => $expense->id,
                        'date_formatted'      => $expense->date ? $expense->date->format('d M Y') : '—',
                        'user_name'           => $expense->user ? $expense->user->name_with_role : 'Unknown',
                        'category_name'       => $expense->is_system_generated ? 'System Travel' : ($expense->category?->name ?? 'N/A'),
                        'is_system_generated' => (bool) $expense->is_system_generated,
                        'amount_formatted'    => number_format((float) $expense->amount, 2),
                        'is_after_day_end'    => (bool) $expense->is_after_day_end,
                        'bill_photo_url'      => $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : null,
                        'status'              => $expense->status,
                        'day_start_id'        => $expense->day_start_id,
                        'day_report_url'      => $expense->day_start_id ? route('admin.expenses.reports.daily', $expense->day_start_id) : null,
                        'update_status_url'   => route('admin.expenses.updateStatus', $expense->id),
                    ];
                }),
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'last_page'    => $expenses->lastPage(),
                    'per_page'     => $expenses->perPage(),
                    'total'        => $expenses->total(),
                    'from'         => $expenses->firstItem() ?? 0,
                    'to'           => $expenses->lastItem() ?? 0,
                ],
            ]);
        }

        // Scoped user dropdown — only show visible team members
        $users = User::with('roles')
            ->where('is_active', true)
            ->when($visibleIds !== null, fn($q) => $q->whereIn('id', $visibleIds))
            ->orderBy('name')
            ->get();

        return view('admin.expenses.index', compact('expenses', 'users'));
    }

    public function updateStatus(Request $request, ExpenseBill $expense)
    {
        $visibleIds = DataScope::visibleUserIds('expenses');
        if ($visibleIds !== null && !in_array($expense->user_id, $visibleIds, true)) {
            abort(403, 'Unauthorized to update status for this expense.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $expense->update([
            'status'      => $validated['status'],
            'approved_by' => auth()->id(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status'  => $validated['status'],
                'message' => 'Expense status updated to ' . ucfirst($validated['status']),
            ]);
        }

        return back()->with('success', 'Expense status updated successfully.');
    }
}
