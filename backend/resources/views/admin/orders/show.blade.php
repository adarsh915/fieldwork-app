@extends('admin.layouts.app')

@section('title', 'Order Details')
@section('breadcrumb', 'Admin / Orders / ' . $order->order_no)
@section('page_title', 'Order Details: ' . $order->order_no)

@section('content')
<div class="mb-6 flex justify-between items-center">
    <a href="{{ route('admin.orders.index') }}" class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back to Orders
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Order Info Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 md:col-span-2">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Order Information</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Party (Customer)</span>
                <span class="block text-sm text-gray-900 mt-1 font-medium">{{ $order->party ? $order->party->name : 'N/A' }}</span>
                @if($order->party)
                    <span class="block text-xs text-gray-500">{{ $order->party->party_code ?? $order->party->code ?? '' }} | {{ $order->party->city }}</span>
                @endif
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Created By (Field Worker)</span>
                <span class="block text-sm text-gray-900 mt-1 font-medium">{{ $order->user ? $order->user->name . ' (' . $order->user->role_name . ')' : 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Order Type</span>
                <span class="block text-sm text-gray-900 mt-1">{{ $order->order_type }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Payment Mode</span>
                <span class="block text-sm text-gray-900 mt-1">{{ $order->payment_mode }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Schema (Discount/Offer)</span>
                <span class="block text-sm text-gray-900 mt-1">{{ $order->schema ?: 'None' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Source & Shift Timing</span>
                <div class="mt-1">
                    @if($order->order_source === 'phone')
                        @if($order->is_after_day_end)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                🌙 Phone Order (Off-Shift / Night)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                                📞 Phone Order (During Shift)
                            </span>
                        @endif
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            📍 In-Person Visit Order
                        </span>
                    @endif
                </div>
            </div>
            <div x-data="{
                status: '{{ $order->status }}',
                saving: false,
                saved: false,
                async saveStatus() {
                    this.saving = true;
                    this.saved = false;
                    try {
                        const res = await fetch('{{ route('admin.orders.update-status', $order->id) }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ status: this.status })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.saved = true;
                            setTimeout(() => this.saved = false, 3000);
                        } else {
                            alert(data.message || 'Failed to update order status.');
                        }
                    } catch (e) {
                        alert('Error updating order status.');
                    } finally {
                        this.saving = false;
                    }
                }
            }">
                <span class="block text-xs font-semibold text-gray-500 uppercase">Status</span>
                <div class="mt-1 flex items-center gap-2">
                    <select x-model="status" @change="saveStatus" :disabled="saving" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 pl-2.5 pr-8">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="dispatched">Dispatched</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button type="button" @click="saveStatus" :disabled="saving" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs font-bold hover:bg-indigo-700 transition flex items-center gap-1.5 shadow-sm">
                        <span x-show="!saving">Save</span>
                        <span x-show="saving" class="flex items-center gap-1">
                            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                    <span x-show="saved" x-transition class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                        ✓ Saved!
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Logistics Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Logistics</h3>
        <div class="space-y-4">
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Delivery Destination</span>
                <span class="block text-sm text-gray-900 mt-1">{{ $order->delivery_destination ?: 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Preferred Transport</span>
                <span class="block text-sm text-gray-900 mt-1">{{ $order->preferred_transport ?: 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Vehicle No</span>
                <span class="block text-sm text-gray-900 mt-1">{{ $order->vehicle_no ?: 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Requested Dispatch Date</span>
                <span class="block text-sm text-gray-900 mt-1">{{ $order->dispatch_date ? $order->dispatch_date->format('Y-m-d') : 'N/A' }}</span>
            </div>
        </div>
    </div>
</div>

@if($order->remarks)
<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-2">Remarks</h3>
    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $order->remarks }}</p>
</div>
@endif

<!-- Order Items Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-800">Order Items</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-y border-gray-200">
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product Name</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit / Variant</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Quantity</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Rate</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $item->product ? $item->product->name : 'Unknown Product' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $item->productUnit ? $item->productUnit->unit_name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">
                            {{ $item->qty }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 text-right">
                            ₹{{ number_format($item->rate, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-800 text-right">
                            ₹{{ number_format($item->total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-sm font-bold text-gray-700 text-right uppercase">
                        Grand Total:
                    </td>
                    <td class="px-6 py-4 text-lg font-black text-indigo-600 text-right">
                        ₹{{ number_format($order->total_amount, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
