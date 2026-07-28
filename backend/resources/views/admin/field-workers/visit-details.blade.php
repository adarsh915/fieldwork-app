@extends('admin.layouts.app')

@section('title', 'Visit Details - ' . $fieldWorker->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-4xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('admin.field-workers.visits', $fieldWorker->id) }}" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-4">
                <x-lucide-arrow-left class="w-4 h-4" />
                Back to Visits
            </a>
            <h1 class="text-3xl font-bold text-slate-900">Visit Details</h1>
            <p class="text-slate-600 mt-2">{{ $fieldWorker->name }} ({{ $fieldWorker->employee_id }})</p>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Visit Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Party Info -->
                <div class="bg-white rounded-lg shadow-md border border-slate-200 p-6">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <x-lucide-building-2 class="w-6 h-6 text-blue-600" />
                        Party Information
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-slate-600 mb-1">Party Name</p>
                            <p class="text-lg font-semibold text-slate-900">{{ $visit->party->name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-600 mb-1">Party Code</p>
                                <p class="font-semibold text-slate-900">{{ $visit->party->party_code ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 mb-1">City</p>
                                <p class="font-semibold text-slate-900">{{ $visit->party->city ?? '—' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-slate-600 mb-1">Address</p>
                            <p class="text-slate-900">{{ $visit->party->address ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Visit Timeline -->
                <div class="bg-white rounded-lg shadow-md border border-slate-200 p-6">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <x-lucide-calendar class="w-6 h-6 text-purple-600" />
                        Visit Timeline
                    </h2>
                    <div class="space-y-4">
                        <div class="bg-purple-50 p-4 rounded border border-purple-200">
                            <p class="text-xs text-purple-600 mb-2 font-semibold">CHECK-IN</p>
                            <p class="text-lg font-bold text-purple-900">{{ $visit->check_in_time?->format('M d, Y h:i A') ?? '—' }}</p>
                            @if($visit->check_in_lat && $visit->check_in_lng)
                                <p class="text-xs text-purple-700 mt-2">
                                    Location: {{ $visit->check_in_lat }}, {{ $visit->check_in_lng }}
                                </p>
                            @endif
                        </div>

                        <div class="bg-green-50 p-4 rounded border border-green-200">
                            <p class="text-xs text-green-600 mb-2 font-semibold">CHECK-OUT</p>
                            @if($visit->check_out_time)
                                <p class="text-lg font-bold text-green-900">{{ $visit->check_out_time->format('M d, Y h:i A') }}</p>
                                <p class="text-sm text-green-700 mt-2">
                                    Duration: {{ $visit->check_in_time->diffInMinutes($visit->check_out_time) }} minutes
                                </p>
                                @if($visit->check_out_lat && $visit->check_out_lng)
                                    <p class="text-xs text-green-700 mt-2">
                                        Location: {{ $visit->check_out_lat }}, {{ $visit->check_out_lng }}
                                    </p>
                                @endif
                            @else
                                <p class="text-lg font-bold text-green-900">Not checked out yet</p>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200">
                            <div>
                                <p class="text-sm text-slate-600 mb-1">Purpose</p>
                                <p class="font-semibold text-slate-900">{{ $visit->purpose ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-600 mb-1">Status</p>
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold @class([
                                    'bg-green-100 text-green-700' => $visit->status === 'completed',
                                    'bg-yellow-100 text-yellow-700' => $visit->status === 'pending',
                                ])">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if($visit->notes)
                    <div class="bg-white rounded-lg shadow-md border border-slate-200 p-6">
                        <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <x-lucide-file-text class="w-6 h-6 text-slate-600" />
                            Notes
                        </h2>
                        <p class="text-slate-900">{{ $visit->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Right Column - Related Data -->
            <div class="space-y-6">
                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-md border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                        <x-lucide-shopping-cart class="w-5 h-5 text-green-600" />
                        Recent Orders
                    </h3>
                    @if($partyOrders->count() > 0)
                        <div class="space-y-2">
                            @foreach($partyOrders as $order)
                                <div class="p-3 bg-slate-50 rounded border border-slate-200">
                                    <p class="font-semibold text-sm text-slate-900">{{ $order->order_no }}</p>
                                    <p class="text-xs text-slate-600">₹ {{ number_format($order->total_amount, 0) }}</p>
                                    <p class="text-xs text-slate-600">{{ $order->order_date?->format('M d, Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-600">No recent orders</p>
                    @endif
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-lg shadow-md border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                        <x-lucide-credit-card class="w-5 h-5 text-orange-600" />
                        Recent Transactions
                    </h3>
                    @if($partyTransactions->count() > 0)
                        <div class="space-y-2">
                            @foreach($partyTransactions as $transaction)
                                <div class="p-3 bg-slate-50 rounded border border-slate-200">
                                    <p class="font-semibold text-sm text-slate-900">
                                        {{ ucfirst($transaction->transaction_type) }}
                                    </p>
                                    <p class="text-xs text-slate-600">₹ {{ number_format($transaction->amount, 0) }}</p>
                                    <span class="inline-block text-xs px-2 py-1 rounded mt-1 @class([
                                        'bg-green-100 text-green-700' => $transaction->status === 'completed',
                                        'bg-yellow-100 text-yellow-700' => $transaction->status === 'pending',
                                    ])">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-600">No recent transactions</p>
                    @endif
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-900 mb-3">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('admin.parties.show', $visit->party->id) }}" class="block text-center px-3 py-2 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition text-sm font-semibold">
                            View Party Profile
                        </a>
                        <a href="{{ route('admin.field-workers.metrics', $fieldWorker->id) }}" class="block text-center px-3 py-2 bg-slate-100 text-slate-600 rounded hover:bg-slate-200 transition text-sm font-semibold">
                            Back to Metrics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
