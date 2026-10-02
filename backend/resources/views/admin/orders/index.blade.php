@extends('admin.layouts.app')

@section('title', 'Orders')
@section('breadcrumb', 'Admin / Orders')
@section('page_title', 'Orders')

@section('content')

<!-- Compact Filter Form & Status Pills -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6 p-4">
    <!-- Filter Form (Single Row) -->
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap md:flex-nowrap gap-3 items-end mb-4">
        <div class="w-full md:w-1/5">
            <select name="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5" onchange="this.form.submit()">
                <option value="">All Field Workers</option>
                @foreach($fieldWorkers as $worker)
                    <option value="{{ $worker->id }}" {{ request('user_id') == $worker->id ? 'selected' : '' }}>
                        {{ $worker->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-1/5">
            <select name="order_source" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5" onchange="this.form.submit()">
                <option value="">All Sources</option>
                <option value="visit" {{ request('order_source') == 'visit' ? 'selected' : '' }}>📍 Physical Visit</option>
                <option value="phone" {{ request('order_source') == 'phone' ? 'selected' : '' }}>📞 Phone / Direct</option>
            </select>
        </div>
        <div class="w-full md:w-1/5">
            <select name="timing" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5" onchange="this.form.submit()">
                <option value="">All Timings</option>
                <option value="during_shift" {{ request('timing') == 'during_shift' ? 'selected' : '' }}>During Shift</option>
                <option value="after_day_end" {{ request('timing') == 'after_day_end' ? 'selected' : '' }}>After Day End</option>
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Order No or Party..." class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md hover:bg-indigo-700 transition font-medium text-sm">
                Filter
            </button>
            <a href="{{ route('admin.orders.index') }}" class="bg-gray-100 text-gray-700 px-4 py-1.5 rounded-md hover:bg-gray-200 transition font-medium text-sm text-center">
                Reset
            </a>
        </div>
        
        <!-- Keep status hidden input so we don't lose it on form submit -->
        <input type="hidden" name="status" value="{{ request('status') }}">
    </form>

    <!-- Status Pills (Compact) -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => null])) }}" 
           class="px-3 py-1 text-xs font-semibold rounded-full border transition-colors 
           {{ !request('status') ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            All <span class="ml-1 bg-gray-100 px-1.5 py-0.5 rounded-full {{ !request('status') ? 'bg-indigo-100' : '' }}">{{ $counts['all'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'pending'])) }}" 
           class="px-3 py-1 text-xs font-semibold rounded-full border transition-colors 
           {{ request('status') == 'pending' ? 'bg-yellow-50 border-yellow-200 text-yellow-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            Pending <span class="ml-1 bg-gray-100 px-1.5 py-0.5 rounded-full {{ request('status') == 'pending' ? 'bg-yellow-100' : '' }}">{{ $counts['pending'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'approved'])) }}" 
           class="px-3 py-1 text-xs font-semibold rounded-full border transition-colors 
           {{ request('status') == 'approved' ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            Approved <span class="ml-1 bg-gray-100 px-1.5 py-0.5 rounded-full {{ request('status') == 'approved' ? 'bg-blue-100' : '' }}">{{ $counts['approved'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'dispatched'])) }}" 
           class="px-3 py-1 text-xs font-semibold rounded-full border transition-colors 
           {{ request('status') == 'dispatched' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            Dispatched <span class="ml-1 bg-gray-100 px-1.5 py-0.5 rounded-full {{ request('status') == 'dispatched' ? 'bg-green-100' : '' }}">{{ $counts['dispatched'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'delivered'])) }}" 
           class="px-3 py-1 text-xs font-semibold rounded-full border transition-colors 
           {{ request('status') == 'delivered' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            Delivered <span class="ml-1 bg-gray-100 px-1.5 py-0.5 rounded-full {{ request('status') == 'delivered' ? 'bg-emerald-100' : '' }}">{{ $counts['delivered'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'cancelled'])) }}" 
           class="px-3 py-1 text-xs font-semibold rounded-full border transition-colors 
           {{ request('status') == 'cancelled' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            Cancelled <span class="ml-1 bg-gray-100 px-1.5 py-0.5 rounded-full {{ request('status') == 'cancelled' ? 'bg-red-100' : '' }}">{{ $counts['cancelled'] ?? 0 }}</span>
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-y border-gray-200">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order No</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Party (Customer)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Source / Timing</th>
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
                                    {{ $order->party->name }} <span class="text-xs text-gray-400">({{ $order->party->party_code ?? $order->party->code ?? '' }})</span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $order->user ? $order->user->name : 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($order->order_source === 'phone')
                                    @if($order->is_after_day_end)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200" title="Direct Phone Order placed after day end / off-shift">
                                            🌙 Phone (Off-Shift)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200" title="Direct Phone Order placed during active shift">
                                            📞 Phone (Shift)
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200" title="Physical Store Visit Order">
                                        📍 Visit Order
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : \Carbon\Carbon::parse($order->created_at)->format('Y-m-d') }}
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
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
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
