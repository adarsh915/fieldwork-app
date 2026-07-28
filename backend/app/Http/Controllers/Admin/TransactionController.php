<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function index()
    {
        $search = request('q');
        $status = request('status');
        $type = request('type');

        $baseQuery = Transaction::query();

        return view('admin.transactions.index', [
            'transactions' => Transaction::query()
                ->with(['user', 'customer'])
                ->when($search, function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('notes', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('mobile', 'like', "%{$search}%");
                            })
                            ->orWhereHas('user', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($type, fn ($query) => $query->where('type', $type))
                ->orderBy('id', 'desc')
                ->paginate(20)
                ->withQueryString(),
            'statuses' => (clone $baseQuery)->select('status')->distinct()->orderBy('status')->pluck('status'),
            'types' => (clone $baseQuery)->select('type')->distinct()->orderBy('type')->pluck('type'),
            'search' => $search,
            'selectedStatus' => $status,
            'selectedType' => $type,
        ]);
    }
}
