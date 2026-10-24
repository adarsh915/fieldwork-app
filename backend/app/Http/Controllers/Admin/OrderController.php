<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\DataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

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

        return view('admin.orders.index', compact('fieldWorkers', 'counts'));
    }

    public function data(Request $request)
    {
        $scopedUserIds = DataScope::visibleUserIds('orders');

        $baseQuery = Order::query();

        if ($scopedUserIds !== null) {
            $baseQuery->whereIn('user_id', $scopedUserIds);
        }

        if ($request->filled('user_id')) {
            if ($scopedUserIds === null || in_array($request->user_id, $scopedUserIds)) {
                $baseQuery->where('user_id', $request->user_id);
            } else {
                $baseQuery->whereRaw('1=0');
            }
        }

        $search = $request->input('search.value');
        if (empty($search) && is_string($request->input('search'))) {
            $search = $request->input('search');
        }
        if (!empty($search) && is_string($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhereHas('party', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('party_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
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

        // Status counts respecting current filters (excluding status filter)
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

        // Apply status filter for table rows
        $query = (clone $baseQuery)->with(['party', 'user.roles'])->select('orders.*');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('order_no', function ($order) {
                return $order->order_no;
            })
            ->addColumn('party', function ($order) {
                return [
                    'name' => $order->party?->name ?? 'N/A',
                    'code' => $order->party?->party_code ?? $order->party?->code ?? '',
                    'city' => $order->party?->city ?? '',
                ];
            })
            ->addColumn('user', function ($order) {
                return [
                    'name' => $order->user ? $order->user->name_with_role : ($order->user?->name ?? 'Unknown'),
                    'role' => $order->user?->role_name ?? ($order->user?->roles->first()?->name ?? 'Field Worker'),
                ];
            })
            ->addColumn('source_timing', function ($order) {
                return [
                    'source' => $order->order_source,
                    'is_after_day_end' => (bool) $order->is_after_day_end,
                ];
            })
            ->addColumn('date', function ($order) {
                return [
                    'formatted' => $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : \Carbon\Carbon::parse($order->created_at)->format('d M Y'),
                ];
            })
            ->addColumn('amount', function ($order) {
                return number_format((float) $order->total_amount, 2);
            })
            ->addColumn('status', function ($order) {
                return $order->status;
            })
            ->addColumn('actions', function ($order) {
                return [
                    'id' => $order->id,
                    'order_no' => $order->order_no,
                    'status' => $order->status,
                    'show_url' => route('admin.orders.show', $order->id),
                    'update_status_url' => route('admin.orders.update-status', $order->id),
                ];
            })
            ->with('counts', $counts)
            ->rawColumns(['actions'])
            ->make(true);
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
