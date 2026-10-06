@extends('admin.layouts.app')

@section('title', 'Metrics - ' . $fieldWorker->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('admin.field-workers.index') }}" class="p-2 bg-slate-200 rounded-lg hover:bg-slate-300 transition">
                    <x-lucide-arrow-left class="w-6 h-6 text-slate-700" />
                </a>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-slate-900">{{ $fieldWorker->name }} ({{ $fieldWorker->role_name }})</h1>
                </div>
                <span class="px-4 py-2 rounded-full font-bold @class([
                    'bg-green-100 text-green-700' => $fieldWorker->is_active,
                    'bg-slate-100 text-slate-700' => !$fieldWorker->is_active,
                ])">
                    {{ $fieldWorker->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-blue-500">
                    <p class="text-sm text-slate-600 mb-2">Total Parties</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $stats['total_parties'] }}</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-green-500">
                    <p class="text-sm text-slate-600 mb-2">Total Orders</p>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['total_orders'] }}</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-purple-500">
                    <p class="text-sm text-slate-600 mb-2">Total Visits</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $stats['total_visits'] }}</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-orange-500">
                    <p class="text-sm text-slate-600 mb-2">Pending Orders</p>
                    <p class="text-3xl font-bold text-orange-600">{{ $stats['pending_orders'] }}</p>
                </div>
            </div>

            <!-- Revenue Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-lg p-6 shadow-md border border-slate-200">
                    <p class="text-sm text-slate-600 mb-2">Total Revenue</p>
                    <p class="text-2xl font-bold text-slate-900">₹ {{ number_format($stats['total_revenue'], 0) }}</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md border border-slate-200">
                    <p class="text-sm text-slate-600 mb-2">Avg Order Value</p>
                    <p class="text-2xl font-bold text-slate-900">₹ {{ number_format($stats['avg_order_value'], 0) }}</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md border border-slate-200">
                    <p class="text-sm text-slate-600 mb-2">Pending Payments</p>
                    <p class="text-2xl font-bold text-red-600">₹ {{ number_format($stats['pending_payments'], 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Assigned Parties -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                <x-lucide-building-2 class="w-6 h-6 text-slate-600" />
                Assigned Parties
            </h2>

            @if($activeAssignments->count() > 0)
                <div class="bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Party Code</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Party Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">City</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Category</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Assigned Date</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Duration</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($activeAssignments as $assignment)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $assignment->party->party_code ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-900">{{ $assignment->party->name }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ $assignment->party->city ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold @class([
                                            'bg-red-100 text-red-700' => $assignment->party->category === 'A',
                                            'bg-yellow-100 text-yellow-700' => $assignment->party->category === 'B',
                                            'bg-green-100 text-green-700' => $assignment->party->category === 'C',
                                        ])">
                                            {{ $assignment->party->category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ $assignment->assigned_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $assignment->assigned_at->diffInDays(now()) }} days</td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="{{ route('admin.parties.show', $assignment->party->id) }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $activeAssignments->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
                    <p class="text-slate-600">No parties assigned</p>
                </div>
            @endif
        </div>

        <!-- Recent Orders -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                <x-lucide-shopping-cart class="w-6 h-6 text-slate-600" />
                Recent Orders
            </h2>

            @if($orders->count() > 0)
                <div class="bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Order No</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Party</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Date</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Status</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($orders as $order)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $order->order_no }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-900">{{ $order->party->name }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ $order->order_date->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold @class([
                                            'bg-yellow-100 text-yellow-700' => $order->status === 'pending',
                                            'bg-blue-100 text-blue-700' => $order->status === 'approved',
                                            'bg-green-100 text-green-700' => $order->status === 'dispatched',
                                        ])">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900 text-right">₹ {{ number_format($order->total_amount, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
                    <p class="text-slate-600">No orders found</p>
                </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <a href="{{ route('admin.field-workers.visits', $fieldWorker->id) }}" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold flex items-center gap-2">
                <x-lucide-calendar class="w-4 h-4" />
                View Visits
            </a>
            <a href="{{ route('admin.field-workers.transactions', $fieldWorker->id) }}" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-semibold flex items-center gap-2">
                <x-lucide-credit-card class="w-4 h-4" />
                View Transactions
            </a>
            <a href="{{ route('admin.field-workers.export', $fieldWorker->id) }}" class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition font-semibold flex items-center gap-2">
                <x-lucide-download class="w-4 h-4" />
                Export Report
            </a>
            <a href="{{ route('admin.field-workers.index') }}" class="px-6 py-2 bg-slate-300 text-slate-700 rounded-lg hover:bg-slate-400 transition font-semibold">
                Back
            </a>
        </div>
    </div>
</div>
@endsection
