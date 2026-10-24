<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayStart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ActivityNotificationService;
use App\Services\DataScope;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Get order module configuration and policies.
     */
    public function config(SettingsService $settings)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'allow_phone_orders' => (bool) $settings->get('allow_phone_orders', true),
            ],
        ]);
    }

    /**
     * Get list of orders (supports optional pagination & search)
     */
    public function index(Request $request)
    {
        $query = Order::query()
            ->with(['items.product', 'party'])
            ->where('user_id', $request->user()->id);

        if ($request->has('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('order_no', 'LIKE', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        if ($request->has('page') || $request->has('per_page')) {
            $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
            $paginated = $query->paginate($perPage);

            return response()->json([
                'success'      => true,
                'data'         => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'has_more'     => $paginated->hasMorePages(),
            ]);
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $orders,
            'total'   => $orders->count(),
        ]);
    }

    /**
     * Get a single order detail
     */
    public function show($id)
    {
        $order = Order::with(['items.product', 'party'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /**
     * Submit a new order
     */
    public function store(Request $request, SettingsService $settings)
    {
        $user = $request->user();
        if (!$user->can('orders.create') && !$user->hasRole(['Super Admin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to place orders.',
            ], 403);
        }

        $request->validate([
            'visit_id' => 'nullable|exists:visits,id',
            'party_id' => 'required|exists:customers,id',
            'order_source' => 'nullable|string|in:visit,phone',
            'delivery_destination' => 'nullable|string',
            'preferred_transport' => 'nullable|string',
            'vehicle_no' => 'nullable|string',
            'dispatch_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'schema' => 'nullable|string',
            'order_type' => 'nullable|string',
            'payment_mode' => 'nullable|string',
            'order_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_unit_id' => 'required|exists:product_units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);

        $isPhoneOrder = empty($request->visit_id) || $request->order_source === 'phone';
        if ($isPhoneOrder) {
            $allowPhoneOrders = (bool) $settings->get('allow_phone_orders', true);
            if (!$allowPhoneOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone / Direct orders are currently disabled by policy. A physical store visit is required.',
                ], 422);
            }
        }

        $orderSource = $request->order_source ?? ($request->visit_id ? 'visit' : 'phone');

        // Check if user currently has an active shift
        $todayDayStart = DayStart::where('user_id', $request->user()->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $isAfterDayEnd = false;
        if (!$todayDayStart || !empty($todayDayStart->closing_km) || $todayDayStart->status === 'completed') {
            $isAfterDayEnd = true;
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $price = isset($item['price']) ? (float)$item['price'] : 0;
                $totalAmount += ($item['quantity'] * $price);
            }

            $orderDate = $request->order_date ? Carbon::parse($request->order_date) : Carbon::today();

            $order = Order::create([
                'order_no' => 'ORD-' . date('YmdHis') . '-' . rand(100, 999),
                'user_id' => $request->user()->id,
                'visit_id' => $request->visit_id,
                'party_id' => $request->party_id,
                'order_source' => $orderSource,
                'is_after_day_end' => $isAfterDayEnd,
                'delivery_destination' => $request->delivery_destination,
                'preferred_transport' => $request->preferred_transport,
                'vehicle_no' => $request->vehicle_no,
                'dispatch_date' => $request->dispatch_date,
                'remarks' => $request->remarks,
                'schema' => $request->schema,
                'order_type' => $request->order_type ?? 'Visit Report',
                'payment_mode' => $request->payment_mode ?? 'Report',
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'order_date' => $orderDate,
            ]);

            foreach ($request->items as $item) {
                $price = isset($item['price']) ? (float)$item['price'] : 0;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'qty' => $item['quantity'],
                    'rate' => $price,
                    'total' => $item['quantity'] * $price,
                ]);
            }

            DB::commit();

            ActivityNotificationService::notifyOrderPlaced($order);

            return response()->json([
                'success' => true,
                'message' => 'Order submitted successfully',
                'data' => $order->load('items'),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order submission failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit order. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

