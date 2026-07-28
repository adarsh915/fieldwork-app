<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'customers' => Customer::count(),
                'transactions' => Transaction::count(),
                'pending_transactions' => Transaction::where('status', 'pending')->count(),
                'transaction_value' => Transaction::sum('amount'),
                'roles' => Role::count(),
            ],
            'recentUsers' => User::query()->with('roles')->latest()->limit(5)->get(),
            'recentTransactions' => Transaction::query()->with(['user', 'customer'])->latest()->limit(5)->get(),
        ]);
    }
}
