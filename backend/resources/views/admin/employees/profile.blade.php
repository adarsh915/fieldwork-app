@extends('admin.layouts.app')

@section('title', 'Fieldworker Profile - ' . $employee->name)
@section('breadcrumb', 'ADMIN / EMPLOYEES / PROFILE')
@section('page_title', 'Fieldworker Profile')

@section('content')

{{-- Employee Header Card --}}
<div class="relative bg-white border border-surface-border rounded-xl shadow-sm overflow-hidden mb-6">
    {{-- Gradient banner --}}
    <div class="h-28 bg-gradient-to-r from-brand-600 via-brand-500 to-indigo-500"></div>

    <div class="px-6 pb-6">
        {{-- Top row: avatar + action buttons --}}
        <div class="flex items-end justify-between -mt-14 mb-4">
            {{-- Avatar --}}
            <div class="shrink-0">
                @if ($employee->profile_image_url)
                    <img src="{{ $employee->profile_image_url }}" alt="{{ $employee->name }}"
                         class="w-24 h-24 rounded-full object-cover shadow-lg ring-4 ring-white">
                @else
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-brand-600 to-brand-700 flex items-center justify-center text-white text-3xl font-bold shadow-lg ring-4 ring-white">
                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2">
                <x-button href="{{ route('admin.employees.edit', $employee->id) }}" variant="secondary">
                    <x-lucide-pencil class="w-4 h-4" />
                    Edit
                </x-button>
                <x-button href="{{ route('admin.employees.index') }}" variant="secondary">
                    <x-lucide-arrow-left class="w-4 h-4" />
                    Back
                </x-button>
            </div>
        </div>

        {{-- Name + Info --}}
        <h2 class="text-page-title mb-1">{{ $employee->name }} ({{ $employee->role_name }})</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 mt-2">
            <p class="text-body flex items-center gap-2">
                <x-lucide-mail class="w-4 h-4 text-ink-400 shrink-0" />
                {{ $employee->email }}
            </p>
            @if($employee->phone)
                <p class="text-body flex items-center gap-2">
                    <x-lucide-phone class="w-4 h-4 text-ink-400 shrink-0" />
                    {{ $employee->phone }}
                </p>
            @endif
            @if($employee->department || $employee->position)
                <p class="text-body flex items-center gap-2">
                    <x-lucide-briefcase class="w-4 h-4 text-ink-400 shrink-0" />
                    {{ $employee->position ?? 'Position' }}{{ $employee->department ? ' • ' . $employee->department : '' }}
                </p>
            @endif
        </div>

        {{-- Badges --}}
        <div class="flex flex-wrap gap-2 mt-4">
            @foreach($employee->roles as $role)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-brand-50 text-brand-700 border border-brand-200">
                    <x-lucide-shield class="w-3 h-3" />
                    {{ $role->name }}
                </span>
            @endforeach
            @if($employee->is_active)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-success-bg text-success-text">
                    <x-lucide-check-circle class="w-3 h-3" />
                    Active
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-danger-bg text-danger-text">
                    <x-lucide-x-circle class="w-3 h-3" />
                    Inactive
                </span>
            @endif
        </div>
    </div>
</div>

{{-- Key Metrics Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    {{-- Total Parties --}}
    <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 rounded-lg bg-brand-50 flex items-center justify-center">
                <x-lucide-users class="w-6 h-6 text-brand-600" />
            </div>
        </div>
        <p class="text-caption text-ink-600 mb-1">Total Parties</p>
        <p class="text-3xl font-bold text-ink-900">{{ $stats['total_parties'] }}</p>
        <p class="text-xs text-success-text mt-1">{{ $stats['active_parties'] }} active</p>
    </div>

    {{-- Total Orders --}}
    <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center">
                <x-lucide-shopping-cart class="w-6 h-6 text-green-600" />
            </div>
        </div>
        <p class="text-caption text-ink-600 mb-1">Total Orders</p>
        <p class="text-3xl font-bold text-ink-900">{{ $stats['total_orders'] }}</p>
        <p class="text-xs text-ink-400 mt-1">All time</p>
    </div>

    {{-- Total Visits --}}
    <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center">
                <x-lucide-map-pin class="w-6 h-6 text-blue-600" />
            </div>
        </div>
        <p class="text-caption text-ink-600 mb-1">Total Visits</p>
        <p class="text-3xl font-bold text-ink-900">{{ $stats['total_visits'] }}</p>
        <p class="text-xs text-ink-400 mt-1">Completed</p>
    </div>

    {{-- Total Revenue --}}
    <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 rounded-lg bg-purple-50 flex items-center justify-center">
                <x-lucide-trending-up class="w-6 h-6 text-purple-600" />
            </div>
        </div>
        <p class="text-caption text-ink-600 mb-1">Total Revenue</p>
        <p class="text-3xl font-bold text-ink-900">₹{{ number_format($stats['total_revenue'], 0) }}</p>
        <p class="text-xs text-success-text mt-1">+12% vs last month</p>
    </div>

    {{-- Pending Payments --}}
    <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 rounded-lg bg-orange-50 flex items-center justify-center">
                <x-lucide-clock class="w-6 h-6 text-orange-600" />
            </div>
        </div>
        <p class="text-caption text-ink-600 mb-1">Pending Payments</p>
        <p class="text-3xl font-bold text-orange-600">₹{{ number_format($stats['pending_payments'], 0) }}</p>
        <p class="text-xs text-ink-400 mt-1">To collect</p>
    </div>

    {{-- Avg Order Value --}}
    <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
            <div class="w-12 h-12 rounded-lg bg-indigo-50 flex items-center justify-center">
                <x-lucide-receipt class="w-6 h-6 text-indigo-600" />
            </div>
        </div>
        <p class="text-caption text-ink-600 mb-1">Avg Order Value</p>
        <p class="text-3xl font-bold text-ink-900">₹{{ $stats['total_orders'] > 0 ? number_format($stats['total_revenue'] / $stats['total_orders'], 0) : 0 }}</p>
        <p class="text-xs text-ink-400 mt-1">Per order</p>
    </div>
</div>

{{-- Main Content Grid --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Left Column (2/3 width) --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- Tabs Navigation --}}
        <div x-data="{ activeTab: 'performance' }" class="bg-white border border-surface-border rounded-xl shadow-sm overflow-hidden">
            {{-- Tab Headers --}}
            <div class="border-b border-surface-border bg-surface-muted px-6 py-3">
                <div class="flex gap-1">
                    <button 
                        @click="activeTab = 'performance'" 
                        :class="activeTab === 'performance' ? 'bg-white border-surface-border shadow-sm' : 'hover:bg-white/50'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    >
                        <span :class="activeTab === 'performance' ? 'text-brand-700' : 'text-ink-600'">Performance</span>
                    </button>
                    <button 
                        @click="activeTab = 'orders'" 
                        :class="activeTab === 'orders' ? 'bg-white border-surface-border shadow-sm' : 'hover:bg-white/50'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    >
                        <span :class="activeTab === 'orders' ? 'text-brand-700' : 'text-ink-600'">Orders</span>
                    </button>
                    <button 
                        @click="activeTab = 'parties'" 
                        :class="activeTab === 'parties' ? 'bg-white border-surface-border shadow-sm' : 'hover:bg-white/50'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    >
                        <span :class="activeTab === 'parties' ? 'text-brand-700' : 'text-ink-600'">Top Parties</span>
                    </button>
                    <button 
                        @click="activeTab = 'products'" 
                        :class="activeTab === 'products' ? 'bg-white border-surface-border shadow-sm' : 'hover:bg-white/50'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    >
                        <span :class="activeTab === 'products' ? 'text-brand-700' : 'text-ink-600'">Products</span>
                    </button>
                    <button 
                        @click="activeTab = 'tracking'" 
                        :class="activeTab === 'tracking' ? 'bg-white border-surface-border shadow-sm' : 'hover:bg-white/50'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                    >
                        <span :class="activeTab === 'tracking' ? 'text-brand-700' : 'text-ink-600'">Daily Tracking</span>
                    </button>
                </div>
            </div>

            {{-- Tab Contents --}}
            <div class="p-6">
                {{-- Performance Tab --}}
                <div x-show="activeTab === 'performance'" x-transition>
                    <h3 class="text-section-title mb-4">Monthly Performance (Last 6 Months)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-surface-border">
                                    <th class="text-left py-3 px-4 text-table-header">Month</th>
                                    <th class="text-right py-3 px-4 text-table-header">Orders</th>
                                    <th class="text-right py-3 px-4 text-table-header">Visits</th>
                                    <th class="text-right py-3 px-4 text-table-header">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border">
                                @foreach($monthlyData as $data)
                                    <tr class="hover:bg-surface-muted transition-colors">
                                        <td class="py-3 px-4 text-body-emphasis">{{ $data['month'] }}</td>
                                        <td class="text-right py-3 px-4 text-body">
                                            <span class="inline-flex items-center justify-center w-16 px-2 py-1 bg-green-50 text-green-700 rounded text-sm font-medium">
                                                {{ $data['orders'] }}
                                            </span>
                                        </td>
                                        <td class="text-right py-3 px-4 text-body">
                                            <span class="inline-flex items-center justify-center w-16 px-2 py-1 bg-blue-50 text-blue-700 rounded text-sm font-medium">
                                                {{ $data['visits'] }}
                                            </span>
                                        </td>
                                        <td class="text-right py-3 px-4 text-body-emphasis tabular-nums">₹{{ number_format($data['revenue'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Orders Tab --}}
                <div x-show="activeTab === 'orders'" x-transition>
                    <h3 class="text-section-title mb-4">Recent Orders</h3>
                    <div class="space-y-3">
                        @forelse($recentOrders as $order)
                            <div class="flex items-center justify-between p-4 bg-surface-muted rounded-lg hover:bg-surface-border transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center text-brand-700 font-semibold">
                                        #{{ $order->id }}
                                    </div>
                                    <div>
                                        <p class="text-body-emphasis">{{ $order->party->name ?? 'Unknown Party' }}</p>
                                        <p class="text-caption">{{ $order->created_at->format('M d, Y • h:i A') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-body-emphasis font-semibold">₹{{ number_format($order->total_amount, 2) }}</p>
                                    @if($order->status === 'completed')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success-text">
                                            <x-lucide-check class="w-3 h-3" /> Completed
                                        </span>
                                    @elseif($order->status === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-warning-bg text-warning-text">
                                            <x-lucide-clock class="w-3 h-3" /> Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-danger-bg text-danger-text">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <x-lucide-inbox class="w-12 h-12 text-ink-400 mx-auto mb-3" />
                                <p class="text-body text-ink-600">No orders yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Top Parties Tab --}}
                <div x-show="activeTab === 'parties'" x-transition>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-section-title">Top Performing Parties</h3>
                        <x-button href="{{ route('admin.employees.parties', $employee->id) }}" variant="secondary" class="text-xs">
                            <x-lucide-external-link class="w-3 h-3" />
                            View All
                        </x-button>
                    </div>
                    <div class="space-y-3">
                        @forelse($topParties as $index => $party)
                            <div class="flex items-center gap-4 p-4 bg-surface-muted rounded-lg hover:bg-surface-border transition-colors">
                                <div class="w-12 h-12 rounded-full {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-100 text-gray-700' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-brand-50 text-brand-700')) }} flex items-center justify-center font-bold text-lg shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-body-emphasis truncate">{{ $party->name }}</p>
                                    <p class="text-caption">{{ $party->city ?? 'No city' }} • {{ $party->party_type ?? 'N/A' }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-body-emphasis font-semibold">₹{{ number_format($party->total_revenue, 2) }}</p>
                                    <p class="text-caption">{{ $party->orders_count }} orders</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <x-lucide-users class="w-12 h-12 text-ink-400 mx-auto mb-3" />
                                <p class="text-body text-ink-600">No parties assigned yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Products Tab --}}
                <div x-show="activeTab === 'products'" x-transition>
                    <h3 class="text-section-title mb-4">Top Products Sold</h3>
                    <div class="space-y-3">
                        @forelse($productSales as $index => $sale)
                            <div class="flex items-center gap-4 p-4 bg-surface-muted rounded-lg hover:bg-surface-border transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                                    <x-lucide-package class="w-5 h-5 text-purple-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-body-emphasis truncate">{{ $sale['product']->name ?? 'Unknown' }}</p>
                                    <p class="text-caption">SKU: {{ $sale['product']->code ?? 'N/A' }} • {{ $sale['orders_count'] }} orders</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-body-emphasis font-semibold">₹{{ number_format($sale['revenue'], 2) }}</p>
                                    <p class="text-caption">{{ $sale['quantity'] }} units</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <x-lucide-package class="w-12 h-12 text-ink-400 mx-auto mb-3" />
                                <p class="text-body text-ink-600">No product sales yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Daily Tracking Tab --}}
                <div x-show="activeTab === 'tracking'" x-transition>
                    <h3 class="text-section-title mb-4">Daily Tracking (Last 30 Days)</h3>
                    <div class="space-y-4">
                        @forelse($dailyTracking as $day)
                            @php
                                $dateKey = $day->date->format('Y-m-d');
                                $dayVisits = $visitsByDayStart[$day->id] ?? collect();
                                $dayExpenses = $expensesByDate[$dateKey] ?? collect();
                                $totalKm = ($day->closing_km && $day->opening_km) ? $day->closing_km - $day->opening_km : null;
                            @endphp
                            <div x-data="{ open: false }" class="border border-surface-border rounded-xl overflow-hidden">
                                {{-- Day Header (clickable) --}}
                                <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 bg-surface-muted hover:bg-white transition-colors text-left">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-lg bg-brand-50 flex flex-col items-center justify-center shrink-0">
                                            <span class="text-xs font-bold text-brand-700 uppercase">{{ $day->date->format('M') }}</span>
                                            <span class="text-lg font-black text-brand-600 -mt-1">{{ $day->date->format('d') }}</span>
                                        </div>
                                        <div>
                                            <p class="text-body-emphasis">{{ $day->date->format('l, d M Y') }}</p>
                                            <p class="text-caption">
                                                {{ $day->tourPurpose->name ?? 'N/A' }} • {{ $day->tourType->name ?? 'N/A' }}
                                                @if($totalKm) • <span class="font-semibold text-brand-700">{{ number_format($totalKm, 1) }} km</span> @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="badge {{ $day->status === 'approved' ? 'badge-success' : ($day->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">
                                            {{ ucfirst($day->status) }}
                                        </span>
                                        <x-lucide-chevron-down class="w-4 h-4 text-ink-400 transition-transform" ::class="open ? 'rotate-180' : ''" />
                                    </div>
                                </button>

                                {{-- Day Details (collapsible) --}}
                                <div x-show="open" x-transition.duration.200ms class="border-t border-surface-border">
                                    <div class="p-5 space-y-5">

                                        {{-- Vehicle & KM Info --}}
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                            <div>
                                                <p class="text-caption mb-0.5">Vehicle</p>
                                                <p class="text-body-emphasis">{{ $day->vehicleType->name ?? 'N/A' }}</p>
                                                @if($day->vehicle_no)
                                                    <p class="text-caption">{{ $day->vehicle_no }}</p>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-caption mb-0.5">Opening KM</p>
                                                <p class="text-body-emphasis">{{ $day->opening_km ? number_format($day->opening_km, 1) : '—' }}</p>
                                                @if($day->opening_km_photo)
                                                    <a href="{{ asset('storage/' . $day->opening_km_photo) }}" target="_blank" class="text-xs text-brand-600 hover:underline flex items-center gap-1 mt-0.5">
                                                        <x-lucide-image class="w-3 h-3" /> View Photo
                                                    </a>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-caption mb-0.5">Closing KM</p>
                                                <p class="text-body-emphasis">{{ $day->closing_km ? number_format($day->closing_km, 1) : '—' }}</p>
                                                @if($day->closing_km_photo)
                                                    <a href="{{ asset('storage/' . $day->closing_km_photo) }}" target="_blank" class="text-xs text-brand-600 hover:underline flex items-center gap-1 mt-0.5">
                                                        <x-lucide-image class="w-3 h-3" /> View Photo
                                                    </a>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-caption mb-0.5">Time</p>
                                                <p class="text-body-emphasis">
                                                    {{ $day->start_time ? \Carbon\Carbon::parse($day->start_time)->format('h:i A') : '—' }}
                                                    →
                                                    {{ $day->end_time ? \Carbon\Carbon::parse($day->end_time)->format('h:i A') : '—' }}
                                                </p>
                                                @if($day->start_time && $day->end_time)
                                                    <p class="text-caption">{{ \Carbon\Carbon::parse($day->start_time)->diff(\Carbon\Carbon::parse($day->end_time))->format('%hh %im') }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Planned Places --}}
                                        @if($day->places->count())
                                            <div>
                                                <p class="text-caption font-semibold uppercase tracking-wide mb-2">Planned Places</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($day->places as $place)
                                                        <span class="badge badge-neutral">{{ $place->place_name }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Visits --}}
                                        @if($dayVisits->count())
                                            <div>
                                                <p class="text-caption font-semibold uppercase tracking-wide mb-2">Visits ({{ $dayVisits->count() }})</p>
                                                <div class="space-y-2">
                                                    @foreach($dayVisits as $visit)
                                                        <div class="flex items-center justify-between p-3 bg-surface-muted rounded-lg">
                                                            <div class="flex items-center gap-3">
                                                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                                                                    <x-lucide-map-pin class="w-4 h-4 text-blue-600" />
                                                                </div>
                                                                <div>
                                                                    <p class="text-body-emphasis">{{ $visit->party->name ?? 'Unknown' }}</p>
                                                                    <p class="text-caption">
                                                                        {{ $visit->purpose ?? 'No purpose' }}
                                                                        @if($visit->check_in_time) • {{ \Carbon\Carbon::parse($visit->check_in_time)->format('h:i A') }} @endif
                                                                        @if($visit->check_out_time) → {{ \Carbon\Carbon::parse($visit->check_out_time)->format('h:i A') }} @endif
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            @if($visit->status)
                                                                <span class="badge {{ $visit->status === 'completed' ? 'badge-success' : 'badge-warning' }}">
                                                                    {{ ucfirst($visit->status) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Expenses --}}
                                        @if($dayExpenses->count())
                                            <div>
                                                <p class="text-caption font-semibold uppercase tracking-wide mb-2">
                                                    Expenses ({{ $dayExpenses->count() }}) — Total: ₹{{ number_format($dayExpenses->sum('amount'), 2) }}
                                                </p>
                                                <div class="space-y-2">
                                                    @foreach($dayExpenses as $expense)
                                                        <div class="flex items-center justify-between p-3 bg-surface-muted rounded-lg">
                                                            <div class="flex items-center gap-3">
                                                                <div class="w-8 h-8 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                                                                    <x-lucide-receipt class="w-4 h-4 text-orange-600" />
                                                                </div>
                                                                <div>
                                                                    <p class="text-body-emphasis font-semibold">₹{{ number_format($expense->amount, 2) }}</p>
                                                                    <p class="text-caption">{{ $expense->date->format('d M Y') }}</p>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                @if($expense->bill_photo)
                                                                    <a href="{{ asset('storage/' . $expense->bill_photo) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">
                                                                        <x-lucide-file-image class="w-3 h-3" /> Proof
                                                                    </a>
                                                                @endif
                                                                <span class="badge {{ $expense->status === 'approved' ? 'badge-success' : ($expense->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">
                                                                    {{ ucfirst($expense->status) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Remarks --}}
                                        @if($day->remarks)
                                            <div class="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                                <p class="text-caption font-semibold text-yellow-800 mb-1">Remarks</p>
                                                <p class="text-body text-yellow-900">{{ $day->remarks }}</p>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <x-lucide-route class="w-12 h-12 text-ink-400 mx-auto mb-3" />
                                <p class="text-body text-ink-600">No daily tracking records in the last 30 days</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column (1/3 width) --}}
    <div class="space-y-6">
        
        {{-- Upcoming Visits --}}
        <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center">
                    <x-lucide-calendar-clock class="w-4 h-4 text-brand-600" />
                </div>
                <h3 class="text-section-title">Upcoming Visits</h3>
            </div>
            <div class="space-y-3">
                @forelse($upcomingVisits as $visit)
                    <div class="p-3 border border-surface-border rounded-lg hover:border-brand-200 hover:bg-brand-50/30 transition-all">
                        <div class="flex items-start justify-between mb-2">
                            <p class="text-body-emphasis font-medium">{{ $visit->party->name ?? 'Unknown' }}</p>
                            <span class="text-xs font-semibold text-brand-700 bg-brand-100 px-2 py-1 rounded-full">
                                {{ $visit->check_in_time ? \Carbon\Carbon::parse($visit->check_in_time)->format('M d') : 'TBD' }}
                            </span>
                        </div>
                        <p class="text-caption text-ink-600 mb-2">{{ $visit->purpose ?? 'No purpose specified' }}</p>
                        @if($visit->check_in_time)
                            <p class="text-xs text-ink-400 flex items-center gap-1">
                                <x-lucide-clock class="w-3 h-3" />
                                {{ \Carbon\Carbon::parse($visit->check_in_time)->diffForHumans() }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-8">
                        <x-lucide-calendar-x class="w-8 h-8 text-ink-400 mx-auto mb-2" />
                        <p class="text-caption text-ink-600">No upcoming visits scheduled</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Visits --}}
        <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                    <x-lucide-map-pin class="w-4 h-4 text-blue-600" />
                </div>
                <h3 class="text-section-title">Recent Visits</h3>
            </div>
            <div class="space-y-3">
                @forelse($recentVisits->take(5) as $visit)
                    <div class="p-3 border border-surface-border rounded-lg">
                        <p class="text-body-emphasis font-medium mb-1">{{ $visit->party->name ?? 'Unknown' }}</p>
                        <p class="text-caption text-ink-600 mb-2">{{ $visit->purpose ?? 'No purpose' }}</p>
                        <div class="flex items-center justify-between">
                            @if($visit->check_in_time)
                                <p class="text-xs text-ink-400 flex items-center gap-1">
                                    <x-lucide-calendar class="w-3 h-3" />
                                    {{ \Carbon\Carbon::parse($visit->check_in_time)->format('M d, Y') }}
                                </p>
                            @endif
                            @if($visit->status)
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $visit->status === 'completed' ? 'bg-success-bg text-success-text' : 'bg-warning-bg text-warning-text' }}">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <x-lucide-map-pin-off class="w-8 h-8 text-ink-400 mx-auto mb-2" />
                        <p class="text-caption text-ink-600">No recent visits</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="bg-white border border-surface-border rounded-xl shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                    <x-lucide-wallet class="w-4 h-4 text-green-600" />
                </div>
                <h3 class="text-section-title">Recent Transactions</h3>
            </div>
            <div class="space-y-3">
                @forelse($recentTransactions->take(5) as $transaction)
                    <div class="flex items-start justify-between p-3 border border-surface-border rounded-lg">
                        <div class="flex-1 min-w-0 mr-3">
                            <p class="text-body-emphasis font-medium truncate">{{ $transaction->customer->name ?? 'Unknown' }}</p>
                            <p class="text-caption text-ink-600">{{ ucfirst($transaction->type ?? 'N/A') }} • {{ $transaction->created_at->format('M d') }}</p>
                            @if($transaction->status === 'pending')
                                <span class="inline-block mt-1 text-xs text-warning-text bg-warning-bg px-2 py-0.5 rounded-full">Pending</span>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-body-emphasis font-semibold {{ $transaction->type === 'credit' ? 'text-success-text' : 'text-danger-text' }}">
                                {{ $transaction->type === 'credit' ? '+' : '-' }}₹{{ number_format($transaction->amount, 2) }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <x-lucide-wallet class="w-8 h-8 text-ink-400 mx-auto mb-2" />
                        <p class="text-caption text-ink-600">No recent transactions</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection
