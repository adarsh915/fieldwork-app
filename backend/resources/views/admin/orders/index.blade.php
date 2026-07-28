@extends('admin.layouts.app')

@section('title', 'Orders')
@section('breadcrumb', 'Admin / Orders')
@section('page_title', 'Orders')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-y border-gray-200">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order No</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Party (Customer)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $order->order_no }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($order->party)
                                    {{ $order->party->name }} <span class="text-xs text-gray-400">({{ $order->party->code }})</span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $order->user ? $order->user->name : 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $order->order_date ? $order->order_date : $order->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-800">
                                ₹{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium 
                                    @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($order->status == 'approved') bg-blue-100 text-blue-800
                                    @elseif($order->status == 'dispatched') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif
                                ">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-md transition-colors">
                                    <i class="fas fa-eye mr-1.5"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                                <p class="text-base font-medium text-gray-600">No orders found</p>
                                <p class="text-sm text-gray-400 mt-1">Orders placed by field workers will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
