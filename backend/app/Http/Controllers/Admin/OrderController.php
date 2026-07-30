<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $fieldWorkers = User::whereHas('roles', function($q) {
            $q->where('name', 'Field Worker');
        })->get();

        $baseQuery = Order::query();

        // Apply base filters (user_id, search)
        if ($request->filled('user_id')) {
            $baseQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('search')) {
            $baseQuery->where(function($q) use ($request) {
                $q->where('order_no', 'like', '%' . $request->search . '%')
                  ->orWhereHas('party', function($q2) use ($request) {
                      $q2->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Status counts for cards (respecting user and search, ignoring status)
        $statusCounts = (clone $baseQuery)->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $counts = [
            'all' => array_sum($statusCounts),
            'pending' => $statusCounts['pending'] ?? 0,
            'approved' => $statusCounts['approved'] ?? 0,
            'dispatched' => $statusCounts['dispatched'] ?? 0,
            'delivered' => $statusCounts['delivered'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
        ];

        // Now apply status filter for the actual list
        $query = (clone $baseQuery)->with(['party', 'user']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders', 'fieldWorkers', 'counts'));
    }

    public function show(Order $order)
    {
        $order->load(['party', 'user', 'items.product', 'items.productUnit']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,dispatched,delivered,cancelled'
        ]);
        
        $order->update(['status' => $request->status]);
        
        return redirect()->back()->with('success', 'Order status updated successfully.');
    }
}
