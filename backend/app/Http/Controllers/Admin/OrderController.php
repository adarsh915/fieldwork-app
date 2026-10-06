<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('orders');

        $fwQuery = User::with('roles')->where('is_active', true);
        if ($scopedUserIds !== null) {
            $fwQuery->whereIn('id', $scopedUserIds);
        } else {
            $fwQuery->whereHas('roles', function($q) {
                $q->whereIn('name', ['Field Worker', 'field_worker', 'Employee']);
            });
        }
        $fieldWorkers = $fwQuery->orderBy('name')->get();

        $baseQuery = Order::query();

        if ($scopedUserIds !== null) {
            $baseQuery->whereIn('user_id', $scopedUserIds);
        }

        // Apply base filters (user_id, search)
        if ($request->filled('user_id')) {
            if ($scopedUserIds === null || in_array($request->user_id, $scopedUserIds)) {
                $baseQuery->where('user_id', $request->user_id);
            } else {
                $baseQuery->whereRaw('1=0');
            }
        }
        if ($request->filled('search')) {
            $baseQuery->where(function($q) use ($request) {
                $q->where('order_no', 'like', '%' . $request->search . '%')
                  ->orWhereHas('party', function($q2) use ($request) {
                      $q2->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }
        if ($request->filled('order_source')) {
            $baseQuery->where('order_source', $request->order_source);
        }
        if ($request->filled('timing')) {
            if ($request->timing === 'during_shift') {
                $baseQuery->where('is_after_day_end', false);
            } elseif ($request->timing === 'after_day_end') {
                $baseQuery->where('is_after_day_end', true);
            }
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
        $query = (clone $baseQuery)->with(['party', 'user.roles']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'records' => $orders->map(function ($order) {
                    return [
                        'id'                     => $order->id,
                        'order_no'               => $order->order_no,
                        'party_name'             => $order->party?->name ?? 'N/A',
                        'party_code'             => $order->party?->party_code ?? $order->party?->code ?? '',
                        'user_name'              => $order->user ? $order->user->name_with_role : 'Unknown',
                        'order_source'           => $order->order_source,
                        'is_after_day_end'       => (bool) $order->is_after_day_end,
                        'order_date_formatted'   => $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : \Carbon\Carbon::parse($order->created_at)->format('Y-m-d'),
                        'total_amount_formatted' => number_format((float) $order->total_amount, 2),
                        'status'                 => $order->status,
                        'show_url'               => route('admin.orders.show', $order->id),
                    ];
                }),
                'counts' => $counts,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                    'from'         => $orders->firstItem() ?? 0,
                    'to'           => $orders->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.orders.index', compact('orders', 'fieldWorkers', 'counts'));
    }

    public function show(Order $order)
    {
        $scopedUserIds = DataScope::visibleUserIds('orders');
        if ($scopedUserIds !== null && !in_array($order->user_id, $scopedUserIds, true)) {
            abort(403, 'Unauthorized access to this order.');
        }

        $order->load(['party', 'user', 'items.product', 'items.productUnit']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $scopedUserIds = DataScope::visibleUserIds('orders');
        if ($scopedUserIds !== null && !in_array($order->user_id, $scopedUserIds, true)) {
            abort(403, 'Unauthorized to update status for this order.');
        }

        $request->validate([
            'status' => 'required|in:pending,approved,dispatched,delivered,cancelled'
        ]);
        
        $order->update(['status' => $request->status]);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status'  => $order->status,
                'message' => 'Order status updated to ' . ucfirst($order->status) . ' successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }
}
