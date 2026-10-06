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
        <div id="report-filter" x-data="{ customRange: {{ $dateRange['preset'] === 'custom' ? 'true' : 'false' }} }">

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
                    <button type="button"
                            data-preset="{{ $key }}"
                            class="preset-pill px-3 py-1 text-xs font-semibold rounded-full border transition-all
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

            {{-- Custom Range & Employee Filter --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div x-show="customRange" class="md:col-span-2 grid grid-cols-2 gap-2" style="{{ $dateRange['preset'] !== 'custom' ? 'display: none;' : '' }}">
                    <div>
                        <label class="form-label text-xs">From Date</label>
                        <input type="date" id="filter-from" value="{{ $dateRange['from'] }}" class="form-input text-xs py-1.5" />
                    </div>
                    <div>
                        <label class="form-label text-xs">To Date</label>
                        <input type="date" id="filter-to" value="{{ $dateRange['to'] }}" class="form-input text-xs py-1.5" />
                    </div>
                </div>

                <div class="{{ $dateRange['preset'] === 'custom' ? 'md:col-span-1' : 'md:col-span-3' }}">
                    <label class="form-label text-xs">Employee / Field Worker</label>
                    <select id="filter-user" class="form-input text-xs py-1.5">
                        <option value="">All Employees ({{ $employeesList->count() }})</option>
                        @foreach($employeesList as $emp)
                            <option value="{{ $emp->id }}" {{ $selectedUser == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->role_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" id="apply-filter" class="btn-primary text-xs py-2 px-4 flex-1">
                        Apply Filter
                    </button>
                    <button type="button" id="reset-filter" class="btn-secondary text-xs py-2 px-3">
                        Reset
                    </button>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>Active Period: <strong class="text-slate-900 font-semibold" id="period-label">{{ $dateRange['label'] }}</strong></span>
                <span>Found <strong id="employee-count">{{ $matrix->count() }}</strong> employees</span>
            </div>
        </div>
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
            <p class="text-2xl font-black text-slate-900 mt-2" id="kpi-sales-total">₹{{ number_format($totals['total_order_amount'], 2) }}</p>
            <div class="mt-2 pt-2 border-t border-indigo-100/60 flex items-center justify-between text-xs text-slate-600">
                <span><strong id="kpi-orders-total">{{ $totals['total_orders'] }}</strong> Total Orders</span>
                <span class="text-indigo-600 font-semibold" id="kpi-orders-breakdown">{{ $totals['total_visit_orders'] }} Visit / {{ $totals['total_phone_orders'] }} Phone</span>
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
            <p class="text-2xl font-black text-slate-900 mt-2"><span id="kpi-km-total">{{ number_format($totals['total_km'], 1) }}</span> <span class="text-sm font-bold text-slate-500">KM</span></p>
            <div class="mt-2 pt-2 border-t border-emerald-100/60 flex items-center justify-between text-xs text-slate-600">
                <span><strong id="kpi-days-total">{{ $totals['total_days_started'] }}</strong> Shifts Started</span>
                <span class="text-emerald-700 font-semibold" id="kpi-visits-total">{{ $totals['total_visits'] }} Visits Done</span>
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
            <p class="text-2xl font-black text-slate-900 mt-2" id="kpi-collections-total">₹{{ number_format($totals['total_collections'], 2) }}</p>
            <div class="mt-2 pt-2 border-t border-amber-100/60 flex items-center justify-between text-xs text-slate-600">
                <span>Confirmed: <strong class="text-emerald-600" id="kpi-collections-confirmed">₹{{ number_format($totals['confirmed_collections'], 2) }}</strong></span>
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
            <p class="text-2xl font-black text-slate-900 mt-2" id="kpi-expenses-total">₹{{ number_format($totals['total_expenses'], 2) }}</p>
            <div class="mt-2 pt-2 border-t border-rose-100/60 flex items-center justify-between text-xs text-slate-600">
                <span>Approved: <strong id="kpi-expenses-approved">₹{{ number_format($totals['approved_expenses'], 2) }}</strong></span>
                <span class="text-rose-600 font-semibold" id="kpi-leaves-total">{{ $totals['total_leave_days'] }} Leave Days</span>
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

        <div class="table-wrap overflow-x-auto" id="matrix-table-wrap">
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
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700" id="matrix-tbody">
                    @forelse($matrix as $row)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            
                            {{-- Employee info --}}
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $row['name'] }} ({{ $row['role'] }})</div>
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
                <tfoot id="matrix-tfoot" style="{{ $matrix->count() > 0 ? '' : 'display: none;' }}">
                    <tr class="bg-slate-100/80 border-t-2 border-slate-300 font-bold text-xs text-slate-900">
                        <td class="py-3.5 px-4" id="tfoot-employees-label">TOTALS ({{ $matrix->count() }} Employees)</td>
                        <td class="py-3.5 px-3 text-center" id="tfoot-days-km">{{ $totals['total_days_started'] }} d / {{ number_format($totals['total_km'], 1) }} KM</td>
                        <td class="py-3.5 px-3 text-center" id="tfoot-visits">{{ $totals['total_visits'] }}</td>
                        <td class="py-3.5 px-3 text-center" id="tfoot-orders">{{ $totals['total_orders'] }} ({{ $totals['total_visit_orders'] }}V / {{ $totals['total_phone_orders'] }}P)</td>
                        <td class="py-3.5 px-4 text-right font-black text-sm text-brand-700" id="tfoot-sales">₹{{ number_format($totals['total_order_amount'], 2) }}</td>
                        <td class="py-3.5 px-4 text-right text-emerald-700" id="tfoot-collections">₹{{ number_format($totals['total_collections'], 2) }}</td>
                        <td class="py-3.5 px-3 text-center text-rose-600" id="tfoot-leaves">{{ $totals['total_leave_days'] }} d</td>
                        <td class="py-3.5 px-4 text-right" id="tfoot-expenses">₹{{ number_format($totals['total_expenses'], 2) }}</td>
                        <td class="py-3.5 px-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const matrixJsonUrl = '{{ route('admin.reports.matrix-json') }}';
    let currentPreset  = '{{ $dateRange['preset'] }}';

    function formatNumber(num, decimals = 2) {
        return Number(num || 0).toLocaleString('en-IN', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    function getParams() {
        const params = new URLSearchParams();
        if (currentPreset === 'custom') {
            params.set('preset', 'custom');
            const from = document.getElementById('filter-from')?.value;
            const to   = document.getElementById('filter-to')?.value;
            if (from) params.set('from', from);
            if (to)   params.set('to',   to);
        } else {
            params.set('preset', currentPreset);
        }
        const uid = document.getElementById('filter-user')?.value;
        if (uid) params.set('user_id', uid);
        return params;
    }

    function setLoading(on) {
        const tableWrap = document.getElementById('matrix-table-wrap');
        if (!tableWrap) return;
        tableWrap.style.opacity = on ? '0.4' : '1';
        tableWrap.style.pointerEvents = on ? 'none' : '';
    }

    function fetchMatrix() {
        setLoading(true);
        fetch(`${matrixJsonUrl}?${getParams()}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('period-label').textContent  = data.dateRange?.label ?? '';
                document.getElementById('employee-count').textContent = data.matrix?.length ?? 0;
                
                // Update 4 Summary KPI Cards
                if (data.totals) {
                    const t = data.totals;
                    if (document.getElementById('kpi-sales-total'))
                        document.getElementById('kpi-sales-total').textContent = '₹' + formatNumber(t.total_order_amount);
                    if (document.getElementById('kpi-orders-total'))
                        document.getElementById('kpi-orders-total').textContent = t.total_orders ?? 0;
                    if (document.getElementById('kpi-orders-breakdown'))
                        document.getElementById('kpi-orders-breakdown').textContent = `${t.total_visit_orders ?? 0} Visit / ${t.total_phone_orders ?? 0} Phone`;
                    
                    if (document.getElementById('kpi-km-total'))
                        document.getElementById('kpi-km-total').textContent = formatNumber(t.total_km, 1);
                    if (document.getElementById('kpi-days-total'))
                        document.getElementById('kpi-days-total').textContent = t.total_days_started ?? 0;
                    if (document.getElementById('kpi-visits-total'))
                        document.getElementById('kpi-visits-total').textContent = `${t.total_visits ?? 0} Visits Done`;

                    if (document.getElementById('kpi-collections-total'))
                        document.getElementById('kpi-collections-total').textContent = '₹' + formatNumber(t.total_collections);
                    if (document.getElementById('kpi-collections-confirmed'))
                        document.getElementById('kpi-collections-confirmed').textContent = '₹' + formatNumber(t.confirmed_collections);

                    if (document.getElementById('kpi-expenses-total'))
                        document.getElementById('kpi-expenses-total').textContent = '₹' + formatNumber(t.total_expenses);
                    if (document.getElementById('kpi-expenses-approved'))
                        document.getElementById('kpi-expenses-approved').textContent = '₹' + formatNumber(t.approved_expenses);
                    if (document.getElementById('kpi-leaves-total'))
                        document.getElementById('kpi-leaves-total').textContent = `${t.total_leave_days ?? 0} Leave Days`;
                }

                renderTable(data.matrix, data.totals, data.dateRange);
            })
            .catch(() => alert('Failed to load report. Please try again.'))
            .finally(() => setLoading(false));
    }

    function renderTable(rows, totals, dateRange) {
        const tbody  = document.getElementById('matrix-tbody');
        const tfoot  = document.getElementById('matrix-tfoot');
        if (!tbody) return;

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr>
                <td colspan="9" class="text-center py-12 text-slate-400">
                    <p class="text-sm font-semibold text-slate-600">No performance records found for this period.</p>
                    <p class="text-xs text-slate-400 mt-1">Try expanding your date range filter.</p>
                </td>
            </tr>`;
            if (tfoot) tfoot.style.display = 'none';
            return;
        }

        const preset = dateRange?.preset ?? currentPreset;
        const from = dateRange?.from ?? '';
        const to = dateRange?.to ?? '';

        tbody.innerHTML = rows.map(r => {
            const autoClosedHtml = (r.auto_closed_count > 0)
                ? `<span class="inline-block mt-0.5 text-[9px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-1 rounded" title="Auto closed day starts">⚠️ ${r.auto_closed_count} auto-closed</span>`
                : '';

            const phoneNightHtml = (r.phone_night_orders > 0)
                ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Phone orders after day end / off-shift">🌙 ${r.phone_night_orders}</span>`
                : '';

            const phoneAmountHtml = (r.total_phone_orders > 0)
                ? `<div class="text-[10px] text-slate-400 mt-0.5">Phone: ₹${formatNumber(r.phone_orders_amount)}</div>`
                : '';

            const leaveHtml = (r.leave_days > 0)
                ? `<span class="badge badge-warning font-bold text-xs">${r.leave_days} d</span>`
                : `<span class="text-slate-400 text-xs">0</span>`;

            return `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="py-3.5 px-4">
                    <div class="font-bold text-slate-900 text-sm">${r.name} (${r.role ?? 'Field Worker'})</div>
                </td>
                <td class="py-3.5 px-3 text-center">
                    <div class="font-bold text-slate-900">${r.days_started ?? 0} <span class="text-[10px] text-slate-400 font-normal">days</span></div>
                    <div class="text-[11px] text-emerald-700 font-medium mt-0.5">${formatNumber(r.total_km, 1)} KM</div>
                    ${autoClosedHtml}
                </td>
                <td class="py-3.5 px-3 text-center">
                    <div class="font-bold text-slate-900">${r.total_visits ?? 0}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">(${formatNumber(r.avg_visits_per_day, 1)} / day)</div>
                </td>
                <td class="py-3.5 px-3 text-center">
                    <div class="font-bold text-slate-900 text-sm">${r.total_orders ?? 0}</div>
                    <div class="flex items-center justify-center gap-1 mt-1">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="In-person visit orders">📍 ${r.visit_orders_count ?? 0}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" title="Phone orders during shift">📞 ${r.phone_shift_orders ?? 0}</span>
                        ${phoneNightHtml}
                    </div>
                </td>
                <td class="py-3.5 px-4 text-right">
                    <div class="font-black text-slate-900 text-sm">₹${formatNumber(r.total_order_amount)}</div>
                    ${phoneAmountHtml}
                </td>
                <td class="py-3.5 px-4 text-right">
                    <div class="font-bold text-emerald-700">₹${formatNumber(r.total_collections)}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Conf: ₹${formatNumber(r.confirmed_collections)}</div>
                </td>
                <td class="py-3.5 px-3 text-center">
                    ${leaveHtml}
                </td>
                <td class="py-3.5 px-4 text-right">
                    <div class="font-bold text-slate-900">₹${formatNumber(r.claimed_expenses)}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Apprv: ₹${formatNumber(r.approved_expenses)}</div>
                </td>
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                    <a href="/admin/reports/scorecard/${r.user_id}?preset=${preset}&from=${from}&to=${to}" 
                       class="btn-secondary px-2.5 py-1 text-xs inline-flex items-center gap-1 font-semibold text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200">
                        360° View →
                    </a>
                </td>
            </tr>`;
        }).join('');

        if (tfoot && totals) {
            tfoot.style.display = '';
            tfoot.innerHTML = `
            <tr class="bg-slate-100/80 border-t-2 border-slate-300 font-bold text-xs text-slate-900">
                <td class="py-3.5 px-4">TOTALS (${rows.length} Employees)</td>
                <td class="py-3.5 px-3 text-center">${totals.total_days_started ?? 0} d / ${formatNumber(totals.total_km, 1)} KM</td>
                <td class="py-3.5 px-3 text-center">${totals.total_visits ?? 0}</td>
                <td class="py-3.5 px-3 text-center">${totals.total_orders ?? 0} (${totals.total_visit_orders ?? 0}V / ${totals.total_phone_orders ?? 0}P)</td>
                <td class="py-3.5 px-4 text-right font-black text-sm text-brand-700">₹${formatNumber(totals.total_order_amount)}</td>
                <td class="py-3.5 px-4 text-right text-emerald-700">₹${formatNumber(totals.total_collections)}</td>
                <td class="py-3.5 px-3 text-center text-rose-600">${totals.total_leave_days ?? 0} d</td>
                <td class="py-3.5 px-4 text-right">₹${formatNumber(totals.total_expenses)}</td>
                <td class="py-3.5 px-4"></td>
            </tr>`;
        }
    }

    // ── Event Bindings ─────────────────────────────────────────────────────────
    document.querySelectorAll('.preset-pill').forEach(btn => {
        btn.addEventListener('click', function () {
            currentPreset = this.dataset.preset;
            document.querySelectorAll('.preset-pill').forEach(b => {
                b.classList.remove('bg-brand-50', 'border-brand-300', 'text-brand-700');
                b.classList.add('bg-white', 'border-slate-200', 'text-slate-600');
            });
            this.classList.add('bg-brand-50', 'border-brand-300', 'text-brand-700');
            this.classList.remove('bg-white', 'border-slate-200', 'text-slate-600');
            fetchMatrix();
        });
    });

    document.getElementById('filter-user')?.addEventListener('change', fetchMatrix);
    document.getElementById('apply-filter')?.addEventListener('click', () => {
        currentPreset = 'custom';
        fetchMatrix();
    });
    document.getElementById('reset-filter')?.addEventListener('click', () => {
        currentPreset = 'this_month';
        const userSelect = document.getElementById('filter-user');
        if (userSelect) userSelect.value = '';
        fetchMatrix();
    });
})();
</script>
@endpush
@endsection
