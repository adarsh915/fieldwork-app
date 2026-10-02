@extends('admin.layouts.app')

@section('title', 'Employee Performance & Analytics Report')
@section('breadcrumb', 'Admin / Reports / Performance Matrix')
@section('page_title', 'Employee Performance & Analytics Report')

@section('content')
<div class="space-y-6">

    {{-- ── Top Navigation & Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-ink-900">Employee 360° Performance Matrix</h1>
            <p class="text-sm text-ink-500 mt-0.5">
                Consolidated performance metrics, field travel, customer visits, phone orders, collections & expenses.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.reports.daily-ledger', request()->query()) }}" 
               class="btn-secondary text-xs py-2 px-3 inline-flex items-center gap-1.5 shadow-sm">
                <x-lucide-calendar class="w-4 h-4 text-ink-600" /> Daily Ledger
            </a>
            <a href="{{ route('admin.reports.fieldworker-parties') }}" 
               class="btn-secondary text-xs py-2 px-3 inline-flex items-center gap-1.5 shadow-sm">
                <x-lucide-users class="w-4 h-4 text-ink-600" /> Party Mapping
            </a>
            <a href="{{ route('admin.reports.export-csv', request()->query()) }}" 
               class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-sm bg-emerald-600 hover:bg-emerald-700 border-emerald-600">
                <x-lucide-download class="w-4 h-4" /> Export CSV
            </a>
        </div>
    </div>

    {{-- ── Date Presets & Filter Panel ── --}}
    <div class="panel p-5 bg-white border border-slate-200 shadow-sm rounded-xl">
        <form method="GET" action="{{ route('admin.reports.index') }}" x-data="{ customRange: '{{ $dateRange['preset'] === 'custom' ? 'true' : 'false' }}' === 'true' }">
            
            {{-- Quick Date Preset Pills --}}
            <div class="flex flex-wrap items-center gap-1.5 pb-4 mb-4 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-2">Range:</span>
                @php
                    $presets = [
                        'today'      => 'Today',
                        'yesterday'  => 'Yesterday',
                        'this_week'  => 'This Week',
                        'last_week'  => 'Last Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_year'  => 'This Year',
                    ];
                @endphp
                @foreach($presets as $key => $name)
                    <button type="submit" name="preset" value="{{ $key }}"
                            class="px-3 py-1 text-xs font-semibold rounded-full border transition-all 
                            {{ $dateRange['preset'] === $key ? 'bg-brand-50 border-brand-300 text-brand-700 shadow-xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        {{ $name }}
                    </button>
                @endforeach
                <button type="button" @click="customRange = !customRange"
                        class="px-3 py-1 text-xs font-semibold rounded-full border transition-all 
                        {{ $dateRange['preset'] === 'custom' ? 'bg-brand-50 border-brand-300 text-brand-700 shadow-xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    <x-lucide-calendar class="w-3 h-3 inline mr-0.5" /> Custom Range
                </button>
            </div>

            {{-- Custom Range & Employee Filter Form --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div x-show="customRange" class="md:col-span-2 grid grid-cols-2 gap-2" style="{{ $dateRange['preset'] !== 'custom' ? 'display: none;' : '' }}">
                    <div>
                        <label class="form-label text-xs">From Date</label>
                        <input type="date" name="from" value="{{ $dateRange['from'] }}" class="form-input text-xs py-1.5" />
                    </div>
                    <div>
                        <label class="form-label text-xs">To Date</label>
                        <input type="date" name="to" value="{{ $dateRange['to'] }}" class="form-input text-xs py-1.5" />
                    </div>
                </div>

                <div class="{{ $dateRange['preset'] === 'custom' ? 'md:col-span-1' : 'md:col-span-3' }}">
                    <label class="form-label text-xs">Employee / Field Worker</label>
                    <select name="user_id" class="form-input text-xs py-1.5" onchange="this.form.submit()">
                        <option value="">All Employees ({{ $employeesList->count() }})</option>
                        @foreach($employeesList as $emp)
                            <option value="{{ $emp->id }}" {{ $selectedUser == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->employee_id ?? 'EMP-' . $emp->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <input type="hidden" name="preset" value="{{ $dateRange['preset'] === 'custom' ? 'custom' : $dateRange['preset'] }}" x-bind:value="customRange ? 'custom' : '{{ $dateRange['preset'] }}'" />
                    <button type="submit" class="btn-primary text-xs py-2 px-4 flex-1">
                        Apply Filter
                    </button>
                    <a href="{{ route('admin.reports.index') }}" class="btn-secondary text-xs py-2 px-3">
                        Reset
                    </a>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>Active Period: <strong class="text-slate-900 font-semibold">{{ $dateRange['label'] }}</strong></span>
                <span>Found <strong>{{ $matrix->count() }}</strong> employees</span>
            </div>
        </form>
    </div>

    {{-- ── 4 Summary KPI Stat Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Card 1: Total Sales & Orders --}}
        <div class="panel p-5 bg-gradient-to-br from-indigo-50/70 to-white border border-indigo-100/80 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Total Sales Value</span>
                <span class="p-2 rounded-lg bg-indigo-100 text-indigo-700">
                    <x-lucide-shopping-bag class="w-5 h-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">₹{{ number_format($totals['total_order_amount'], 2) }}</p>
            <div class="mt-2 pt-2 border-t border-indigo-100/60 flex items-center justify-between text-xs text-slate-600">
                <span><strong>{{ $totals['total_orders'] }}</strong> Total Orders</span>
                <span class="text-indigo-600 font-semibold">{{ $totals['total_visit_orders'] }} Visit / {{ $totals['total_phone_orders'] }} Phone</span>
            </div>
        </div>

        {{-- Card 2: Field Attendance & Travel --}}
        <div class="panel p-5 bg-gradient-to-br from-emerald-50/70 to-white border border-emerald-100/80 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Field Movement</span>
                <span class="p-2 rounded-lg bg-emerald-100 text-emerald-700">
                    <x-lucide-navigation class="w-5 h-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">{{ number_format($totals['total_km'], 1) }} <span class="text-sm font-bold text-slate-500">KM</span></p>
            <div class="mt-2 pt-2 border-t border-emerald-100/60 flex items-center justify-between text-xs text-slate-600">
                <span><strong>{{ $totals['total_days_started'] }}</strong> Shifts Started</span>
                <span class="text-emerald-700 font-semibold">{{ $totals['total_visits'] }} Visits Done</span>
            </div>
        </div>

        {{-- Card 3: Payments Collected --}}
        <div class="panel p-5 bg-gradient-to-br from-amber-50/70 to-white border border-amber-100/80 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Total Collections</span>
                <span class="p-2 rounded-lg bg-amber-100 text-amber-700">
                    <x-lucide-receipt class="w-5 h-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">₹{{ number_format($totals['total_collections'], 2) }}</p>
            <div class="mt-2 pt-2 border-t border-amber-100/60 flex items-center justify-between text-xs text-slate-600">
                <span>Confirmed: <strong class="text-emerald-600">₹{{ number_format($totals['confirmed_collections'], 2) }}</strong></span>
            </div>
        </div>

        {{-- Card 4: Leaves & Claimed Expenses --}}
        <div class="panel p-5 bg-gradient-to-br from-rose-50/70 to-white border border-rose-100/80 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Expenses & Leaves</span>
                <span class="p-2 rounded-lg bg-rose-100 text-rose-700">
                    <x-lucide-clock class="w-5 h-5" />
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-2">₹{{ number_format($totals['total_expenses'], 2) }}</p>
            <div class="mt-2 pt-2 border-t border-rose-100/60 flex items-center justify-between text-xs text-slate-600">
                <span>Approved: <strong>₹{{ number_format($totals['approved_expenses'], 2) }}</strong></span>
                <span class="text-rose-600 font-semibold">{{ $totals['total_leave_days'] }} Leave Days</span>
            </div>
        </div>

    </div>

    {{-- ── Comprehensive Employee 360° Matrix Table ── --}}
    <div class="panel overflow-hidden border border-slate-200 shadow-sm rounded-xl bg-white">
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Employee 360° Performance Breakdown</h3>
                <p class="text-xs text-slate-500 mt-0.5">Click on any employee to drill down into their complete scorecard and audit logs.</p>
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Visit Order</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Phone (Shift)</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Phone (Night)</span>
            </div>
        </div>

        <div class="table-wrap overflow-x-auto">
            <table class="data-table w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-3 text-center">Days / KM</th>
                        <th class="py-3 px-3 text-center">Visits</th>
                        <th class="py-3 px-3 text-center">Orders Breakdown</th>
                        <th class="py-3 px-4 text-right">Sales Value</th>
                        <th class="py-3 px-4 text-right">Collections</th>
                        <th class="py-3 px-3 text-center">Leaves</th>
                        <th class="py-3 px-4 text-right">Expenses</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($matrix as $row)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            
                            {{-- Employee info --}}
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $row['name'] }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5">
                                    <span class="badge badge-secondary py-0 px-1 text-[10px]">{{ $row['employee_code'] }}</span>
                                    <span>{{ $row['role'] }}</span>
                                </div>
                            </td>

                            {{-- Days Started & KM --}}
                            <td class="py-3.5 px-3 text-center">
                                <div class="font-bold text-slate-900">{{ $row['days_started'] }} <span class="text-[10px] text-slate-400 font-normal">days</span></div>
                                <div class="text-[11px] text-emerald-700 font-medium mt-0.5">{{ number_format($row['total_km'], 1) }} KM</div>
                                @if($row['auto_closed_count'] > 0)
                                    <span class="inline-block mt-0.5 text-[9px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-1 rounded" title="Auto closed day starts">
                                        ⚠️ {{ $row['auto_closed_count'] }} auto-closed
                                    </span>
                                @endif
                            </td>

                            {{-- Physical Visits --}}
                            <td class="py-3.5 px-3 text-center">
                                <div class="font-bold text-slate-900">{{ $row['total_visits'] }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">({{ $row['avg_visits_per_day'] }} / day)</div>
                            </td>

                            {{-- Orders Breakup (Visit vs Phone Shift vs Phone Night) --}}
                            <td class="py-3.5 px-3 text-center">
                                <div class="font-bold text-slate-900 text-sm">{{ $row['total_orders'] }}</div>
                                <div class="flex items-center justify-center gap-1 mt-1">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="In-person visit orders">
                                        📍 {{ $row['visit_orders_count'] }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" title="Phone orders during shift">
                                        📞 {{ $row['phone_shift_orders'] }}
                                    </span>
                                    @if($row['phone_night_orders'] > 0)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Phone orders after day end / off-shift">
                                            🌙 {{ $row['phone_night_orders'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Total Sales --}}
                            <td class="py-3.5 px-4 text-right">
                                <div class="font-black text-slate-900 text-sm">₹{{ number_format($row['total_order_amount'], 2) }}</div>
                                @if($row['total_phone_orders'] > 0)
                                    <div class="text-[10px] text-slate-400 mt-0.5">Phone: ₹{{ number_format($row['phone_orders_amount'], 2) }}</div>
                                @endif
                            </td>

                            {{-- Collections --}}
                            <td class="py-3.5 px-4 text-right">
                                <div class="font-bold text-emerald-700">₹{{ number_format($row['total_collections'], 2) }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Conf: ₹{{ number_format($row['confirmed_collections'], 2) }}</div>
                            </td>

                            {{-- Leaves --}}
                            <td class="py-3.5 px-3 text-center">
                                @if($row['leave_days'] > 0)
                                    <span class="badge badge-warning font-bold text-xs">{{ $row['leave_days'] }} d</span>
                                @else
                                    <span class="text-slate-400 text-xs">0</span>
                                @endif
                            </td>

                            {{-- Expenses --}}
                            <td class="py-3.5 px-4 text-right">
                                <div class="font-bold text-slate-900">₹{{ number_format($row['claimed_expenses'], 2) }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Apprv: ₹{{ number_format($row['approved_expenses'], 2) }}</div>
                            </td>

                            {{-- Action Drill Down --}}
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.reports.scorecard', ['id' => $row['user_id'], 'preset' => $dateRange['preset'], 'from' => $dateRange['from'], 'to' => $dateRange['to']]) }}" 
                                   class="btn-secondary px-2.5 py-1 text-xs inline-flex items-center gap-1 font-semibold text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200">
                                    <x-lucide-trending-up class="w-3.5 h-3.5" /> 360° View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-slate-400">
                                <x-lucide-inbox class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                                <p class="text-sm font-semibold text-slate-600">No performance records found for this period.</p>
                                <p class="text-xs text-slate-400 mt-1">Try expanding your date range filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($matrix->count() > 0)
                <tfoot>
                    <tr class="bg-slate-100/80 border-t-2 border-slate-300 font-bold text-xs text-slate-900">
                        <td class="py-3.5 px-4">TOTALS ({{ $matrix->count() }} Employees)</td>
                        <td class="py-3.5 px-3 text-center">{{ $totals['total_days_started'] }} d / {{ number_format($totals['total_km'], 1) }} KM</td>
                        <td class="py-3.5 px-3 text-center">{{ $totals['total_visits'] }}</td>
                        <td class="py-3.5 px-3 text-center">{{ $totals['total_orders'] }} ({{ $totals['total_visit_orders'] }}V / {{ $totals['total_phone_orders'] }}P)</td>
                        <td class="py-3.5 px-4 text-right font-black text-sm text-brand-700">₹{{ number_format($totals['total_order_amount'], 2) }}</td>
                        <td class="py-3.5 px-4 text-right text-emerald-700">₹{{ number_format($totals['total_collections'], 2) }}</td>
                        <td class="py-3.5 px-3 text-center text-rose-600">{{ $totals['total_leave_days'] }} d</td>
                        <td class="py-3.5 px-4 text-right">₹{{ number_format($totals['total_expenses'], 2) }}</td>
                        <td class="py-3.5 px-4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
