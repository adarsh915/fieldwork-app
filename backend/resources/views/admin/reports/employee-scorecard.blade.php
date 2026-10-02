@extends('admin.layouts.app')

@section('title', 'Employee 360° Scorecard - ' . $scorecard['user']->name)
@section('breadcrumb', 'Admin / Reports / ' . $scorecard['user']->name)
@section('page_title', 'Employee 360° Scorecard')

@section('content')
<div class="space-y-6">

    {{-- ── Top Profile Header ── --}}
    <div class="panel p-6 bg-white border border-slate-200 shadow-sm rounded-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-600 to-indigo-700 text-white font-black text-2xl flex items-center justify-center shadow-md">
                    {{ strtoupper(substr($scorecard['user']->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-black text-slate-900">{{ $scorecard['user']->name }}</h1>
                        <span class="badge badge-secondary py-0.5 px-2 text-xs font-bold">{{ $scorecard['user']->employee_id ?? 'EMP-' . $scorecard['user']->id }}</span>
                        <span class="badge badge-primary py-0.5 px-2 text-xs">{{ $scorecard['user']->roles->first()?->name ?? 'Field Worker' }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-4">
                        <span><strong class="text-slate-700">Reporting Manager:</strong> {{ $scorecard['user']->reportingManager?->name ?? 'None' }}</span>
                        <span><strong class="text-slate-700">Email:</strong> {{ $scorecard['user']->email }}</span>
                        <span><strong class="text-slate-700">Phone:</strong> {{ $scorecard['user']->phone ?? '—' }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.index', ['preset' => $dateRange['preset'], 'from' => $dateRange['from'], 'to' => $dateRange['to']]) }}" 
                   class="btn-secondary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-sm">
                    <x-lucide-arrow-left class="w-4 h-4" /> Back to Matrix
                </a>
            </div>
        </div>
    </div>

    {{-- ── Period Filter ── --}}
    <div class="panel p-4 bg-white border border-slate-200 shadow-sm rounded-xl">
        <form method="GET" action="{{ route('admin.reports.scorecard', $scorecard['user']->id) }}" class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-2">Period:</span>
                @php
                    $presets = [
                        'today'      => 'Today',
                        'this_week'  => 'This Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_year'  => 'This Year',
                    ];
                @endphp
                @foreach($presets as $key => $name)
                    <button type="submit" name="preset" value="{{ $key }}"
                            class="px-3 py-1 text-xs font-semibold rounded-full border transition-all 
                            {{ $dateRange['preset'] === $key ? 'bg-brand-50 border-brand-300 text-brand-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        {{ $name }}
                    </button>
                @endforeach
            </div>
            <div class="text-xs text-slate-500">
                Active Range: <strong class="text-slate-900 font-semibold">{{ $dateRange['label'] }}</strong>
            </div>
        </form>
    </div>

    {{-- ── Employee KPI Summary Cards ── --}}
    @php $kpi = $scorecard['kpi']; @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="panel p-5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Sales Value</span>
            <p class="text-2xl font-black text-slate-900 mt-2">₹{{ number_format($kpi['total_order_amount'] ?? 0, 2) }}</p>
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                <span><strong>{{ $kpi['total_orders'] ?? 0 }}</strong> Orders Placed</span>
                <span class="text-brand-600 font-semibold">{{ $kpi['visit_orders_count'] ?? 0 }} Visit / {{ $kpi['total_phone_orders'] ?? 0 }} Phone</span>
            </div>
        </div>

        <div class="panel p-5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Field Movement & Travel</span>
            <p class="text-2xl font-black text-emerald-700 mt-2">{{ number_format($kpi['total_km'] ?? 0, 1) }} <span class="text-sm text-slate-500 font-bold">KM</span></p>
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                <span><strong>{{ $kpi['days_started'] ?? 0 }}</strong> Days Worked</span>
                <span class="text-emerald-700 font-semibold">{{ $kpi['total_visits'] ?? 0 }} Store Visits</span>
            </div>
        </div>

        <div class="panel p-5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Collections Done</span>
            <p class="text-2xl font-black text-slate-900 mt-2">₹{{ number_format($kpi['total_collections'] ?? 0, 2) }}</p>
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                <span>Confirmed: <strong class="text-emerald-600">₹{{ number_format($kpi['confirmed_collections'] ?? 0, 2) }}</strong></span>
            </div>
        </div>

        <div class="panel p-5 bg-white border border-slate-200 rounded-xl shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Expenses & Leaves</span>
            <p class="text-2xl font-black text-slate-900 mt-2">₹{{ number_format($kpi['claimed_expenses'] ?? 0, 2) }}</p>
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                <span>Approved: <strong>₹{{ number_format($kpi['approved_expenses'] ?? 0, 2) }}</strong></span>
                <span class="text-rose-600 font-semibold">{{ $kpi['leave_days'] ?? 0 }} Leave Days</span>
            </div>
        </div>

    </div>

    {{-- ── 12-Month Sales History Breakdown ── --}}
    @if($scorecard['monthly_trend']->count() > 0)
    <div class="panel p-6 bg-white border border-slate-200 shadow-sm rounded-xl">
        <h3 class="font-bold text-slate-900 text-base mb-4 flex items-center gap-2">
            <x-lucide-bar-chart-2 class="w-5 h-5 text-brand-600" />
            12-Month Sales & Orders Trend
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($scorecard['monthly_trend'] as $month)
                <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-brand-50/40 transition-colors">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $month->month_label }}</span>
                    <p class="text-base font-black text-slate-900 mt-1">₹{{ number_format($month->total_amount, 0) }}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $month->orders_count }} orders ({{ $month->phone_orders_count }} phone)</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Detailed Records Grid (Orders, Visits, Day Starts) ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Section 1: Recent Orders --}}
        <div class="panel bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <x-lucide-shopping-cart class="w-5 h-5 text-indigo-600" />
                    Orders Booked ({{ $scorecard['orders']->count() }})
                </h3>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] text-slate-400 font-bold uppercase">
                            <th class="py-2.5 px-3">Order No / Date</th>
                            <th class="py-2.5 px-3">Party</th>
                            <th class="py-2.5 px-3">Source</th>
                            <th class="py-2.5 px-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($scorecard['orders'] as $order)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900">{{ $order->order_no }}</div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 font-medium text-slate-800">
                                    {{ $order->party->name ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($order->order_source === 'phone')
                                        @if($order->is_after_day_end)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                🌙 Phone (Night)
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                📞 Phone (Shift)
                                            </span>
                                        @endif
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            📍 Visit Order
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-black text-slate-900">
                                    ₹{{ number_format($order->total_amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-6 text-slate-400">No orders in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 2: Store Visits Conducted --}}
        <div class="panel bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <x-lucide-map-pin class="w-5 h-5 text-emerald-600" />
                    Physical Store Visits ({{ $scorecard['visits']->count() }})
                </h3>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] text-slate-400 font-bold uppercase">
                            <th class="py-2.5 px-3">Party (Customer)</th>
                            <th class="py-2.5 px-3">Check-In Time</th>
                            <th class="py-2.5 px-3">Check-Out</th>
                            <th class="py-2.5 px-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($scorecard['visits'] as $visit)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900">{{ $visit->party->name ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $visit->party->city ?? '' }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600">
                                    {{ $visit->check_in_time ? \Carbon\Carbon::parse($visit->check_in_time)->format('d M, h:i A') : '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-600">
                                    {{ $visit->check_out_time ? \Carbon\Carbon::parse($visit->check_out_time)->format('h:i A') : '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    @if($visit->status === 'completed')
                                        <span class="badge badge-success text-[10px]">Completed</span>
                                    @else
                                        <span class="badge badge-warning text-[10px]">In Progress</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-6 text-slate-400">No store visits in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ── Section 3: Day Starts & Travel Log ── --}}
    <div class="panel bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                <x-lucide-navigation class="w-5 h-5 text-brand-600" />
                Shift Attendance & Travel Records ({{ $scorecard['day_starts']->count() }})
            </h3>
        </div>
        <div class="table-wrap overflow-x-auto">
            <table class="data-table w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[11px] text-slate-400 font-bold uppercase">
                        <th class="py-2.5 px-4">Date</th>
                        <th class="py-2.5 px-3 text-center">Opening KM</th>
                        <th class="py-2.5 px-3 text-center">Closing KM</th>
                        <th class="py-2.5 px-3 text-center">Total Distance</th>
                        <th class="py-2.5 px-3 text-center">Shift Status</th>
                        <th class="py-2.5 px-4 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($scorecard['day_starts'] as $ds)
                        <tr class="hover:bg-slate-50/60">
                            <td class="py-2.5 px-4 font-bold text-slate-900">
                                {{ \Carbon\Carbon::parse($ds->date)->format('D, d M Y') }}
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-600">
                                {{ $ds->opening_km ?? '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-center text-slate-600">
                                {{ $ds->closing_km ?? '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-bold text-emerald-700">
                                @if($ds->closing_km && $ds->opening_km)
                                    {{ max(0, $ds->closing_km - $ds->opening_km) }} KM
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if($ds->auto_closed)
                                    <span class="badge badge-warning text-[10px]">Auto Closed</span>
                                @elseif($ds->closing_km)
                                    <span class="badge badge-success text-[10px]">Completed</span>
                                @else
                                    <span class="badge badge-primary text-[10px]">Active</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-right">
                                <a href="{{ route('admin.day-starts.show', $ds->id) }}" class="btn-secondary px-2 py-1 text-xs">
                                    View Day
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-6 text-slate-400">No shift records in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
