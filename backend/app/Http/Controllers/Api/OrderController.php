<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Get list of orders
     */
    public function index(Request $request)
    {
        // Only fetch orders created by the logged-in field worker
        $query = Order::query()
            ->with('items.product')
            ->where('user_id', $request->user()->id);

        if ($request->has('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        // Return latest orders first
        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
    /**
     * Submit a new order
     */
    public function store(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'party_id' => 'required|exists:customers,id',
            'delivery_destination' => 'nullable|string',
            'preferred_transport' => 'nullable|string',
            'vehicle_no' => 'nullable|string',
            'dispatch_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'schema' => 'nullable|string',
            'order_type' => 'required|string',
            'payment_mode' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_unit_id' => 'required|exists:product_units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += ($item['quantity'] * $item['price']);
            }

            $order = Order::create([
                'order_no' => 'ORD-' . date('YmdHis') . '-' . rand(100,999),
                'user_id' => $request->user()->id,
                'visit_id' => $request->visit_id,
                'party_id' => $request->party_id,
                'delivery_destination' => $request->delivery_destination,
                'preferred_transport' => $request->preferred_transport,
                'vehicle_no' => $request->vehicle_no,
                'dispatch_date' => $request->dispatch_date,
                'remarks' => $request->remarks,
                'schema' => $request->schema,
                'order_type' => $request->order_type,
                'payment_mode' => $request->payment_mode,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'order_date' => \Carbon\Carbon::today(),
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'qty' => $item['quantity'],
                    'rate' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }

            DB::commit();

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
