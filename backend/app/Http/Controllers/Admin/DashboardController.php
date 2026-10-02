<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DataScope;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $scopedUserIds = DataScope::visibleUserIds('parties');

        $usersQuery = User::query();
        if ($scopedUserIds !== null) {
            $usersQuery->whereIn('id', $scopedUserIds);
        }

        $customersQuery = Customer::query();
        if ($scopedUserIds !== null) {
            $customersQuery->where(function ($q) use ($scopedUserIds) {
                $q->whereHas('assignments', function ($sq) use ($scopedUserIds) {
                    $sq->whereIn('employee_id', $scopedUserIds)->where('is_active', true);
                })->orWhereIn('user_id', $scopedUserIds)
                  ->orWhereIn('submitted_by', $scopedUserIds);
            });
        }

        $txQuery = Transaction::query();
        $scopedTxUserIds = DataScope::visibleUserIds('transactions');
        if ($scopedTxUserIds !== null) {
            $txQuery->whereIn('user_id', $scopedTxUserIds);
        }

        return view('admin.dashboard', [
            'stats' => [
                'users' => $usersQuery->count(),
                'customers' => $customersQuery->count(),
                'transactions' => (clone $txQuery)->count(),
                'pending_transactions' => (clone $txQuery)->where('status', 'pending')->count(),
                'transaction_value' => (clone $txQuery)->sum('amount'),
                'roles' => Role::count(),
            ],
            'recentUsers' => (clone $usersQuery)->with('roles')->latest()->limit(5)->get(),
            'recentTransactions' => (clone $txQuery)->with(['user', 'customer'])->latest()->limit(5)->get(),
        ]);
    }
}
