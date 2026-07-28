<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $search = request('q');

        return view('admin.customers.index', [
            'customers' => Customer::query()
                ->when($search, function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('id', 'desc')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }
}
