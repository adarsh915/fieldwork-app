@extends('admin.layouts.app')

@section('title', 'Daily Field Operations Ledger')
@section('breadcrumb', 'ADMIN / REPORTS / DAILY LEDGER')
@section('page_title', 'Daily Field Operations Ledger')

@section('content')
<div class="space-y-6" x-data="dailyLedgerManager({
    initialPreset: '{{ $dateRange['preset'] ?? 'this_month' }}',
    initialUserId: '{{ $selectedUser ?? '' }}',
    initialFrom: '{{ $dateRange['from'] ?? '' }}',
    initialTo: '{{ $dateRange['to'] ?? '' }}',
    initialLabel: '{{ $dateRange['label'] ?? '' }}'
})">

    {{-- ── Main Container Panel ── --}}
    <div class="panel">

        {{-- ── Panel Header ── --}}
        <div class="panel-header space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h2 class="text-section-title">Daily Field Operations Ledger</h2>
                    <p class="text-caption mt-1">
                        Comprehensive day-by-day audit trail of field employee shifts, odometer tracking, client visits, and expenses.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.reports.export-csv', request()->query()) }}" 
                       class="btn-secondary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-2xs">
                        <x-lucide-download class="w-4 h-4 text-ink-500" />
                        <span>Export CSV</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" 
                       class="btn-secondary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-2xs">
                        <x-lucide-arrow-left class="w-4 h-4 text-ink-500" />
                        <span>Back to Matrix</span>
                    </a>
                </div>
            </div>

            {{-- ── Date Presets Toolbar ── --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-surface-border">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-caption uppercase tracking-wider text-ink-500 font-bold mr-1.5 text-[11px]">Quick Range:</span>
                    @php
                        $presets = [
                            'today'      => 'Today',
                            'yesterday'  => 'Yesterday',
                            'this_week'  => 'This Week',
                            'last_week'  => 'Last Week',
                            'this_month' => 'This Month',
                            'last_month' => 'Last Month',
                        ];
                    @endphp
                    @foreach($presets as $key => $name)
                        <button type="button" 
                                @click="setPreset('{{ $key }}')"
                                :class="preset === '{{ $key }}' ? 'bg-brand-600 text-white font-bold shadow-xs border-brand-600' : 'bg-surface-muted text-ink-600 hover:bg-slate-200 border-surface-border font-medium'"
                                class="px-3 py-1 text-xs rounded-full border transition-all">
                            {{ $name }}
                        </button>
                    @endforeach
                </div>
                <div class="text-xs text-ink-500 font-medium">
                    Active Range: <strong class="text-ink-900 font-bold" x-text="dateLabel"></strong>
                </div>
            </div>

            {{-- ── Quick Search & Reset Row ── --}}
            <div class="mt-4 flex flex-wrap gap-3 items-end">
                {{-- Search --}}
                <div class="relative flex-1 min-w-[200px]">
                    <x-lucide-search class="w-4 h-4 text-ink-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input type="text" 
                           x-model="search" 
                           @input.debounce.350ms="applyFilters()"
                           placeholder="Search employee, ID, KM, date..." 
                           class="form-input text-xs pl-9 pr-8 h-[38px] w-full" />
                    <button type="button" 
                            x-show="search" 
                            @click="search = ''; applyFilters()" 
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-400 hover:text-ink-600 transition"
                            style="display: none;"
                            title="Clear search">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </div>

                {{-- Reset Button --}}
                <button type="button" 
                        @click="resetFilters()" 
                        class="btn-secondary h-[38px] px-4 text-xs font-semibold inline-flex items-center justify-center gap-1.5 shrink-0"
                        title="Reset all filters">
                    <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-500" />
                    <span>Reset Filters</span>
                </button>
            </div>

            {{-- ── Filters Grid (4 Columns, exactly like Day Starts) ── --}}
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Date From --}}
                <div>
                    <label class="form-label">Date From</label>
                    <input type="date" 
                           x-model="dateFrom" 
                           @change="onCustomDateChange()" 
                           class="form-input text-xs h-[38px] w-full">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="form-label">Date To</label>
                    <input type="date" 
                           x-model="dateTo" 
                           @change="onCustomDateChange()" 
                           class="form-input text-xs h-[38px] w-full">
                </div>

                {{-- Employee Filter --}}
                <x-filter-select
                    label="Employee"
                    placeholder="All Employees ({{ $employeesList->count() }})"
                    :options="$employeesList->mapWithKeys(fn($emp) => [$emp->id => $emp->name . ' (' . ($emp->role_name ?? ($emp->roles->first()?->name ?? 'Field Worker')) . ')'])"
                    model="userId"
                    change="applyFilters()"
                />

                {{-- Status Filter --}}
                <x-filter-select
                    label="Shift Status"
                    placeholder="All Statuses"
                    :options="[
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'auto_closed' => 'Auto Closed'
                    ]"
                    model="status"
                    change="applyFilters()"
                />
            </div>
        </div>

        {{-- ── DataTable Section ── --}}
        <div class="table-wrap min-h-[420px]">
            <table id="daily-ledger-table" class="data-table w-full">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">Date</th>
                        <th>Employee</th>
                        <th class="text-center whitespace-nowrap">Opening KM</th>
                        <th class="text-center whitespace-nowrap">Closing KM</th>
                        <th class="text-center whitespace-nowrap">Distance</th>
                        <th class="text-center whitespace-nowrap">Visits Done</th>
                        <th class="text-right whitespace-nowrap">Expenses Claimed</th>
                        <th class="text-center whitespace-nowrap">Shift Status</th>
                        <th class="text-right whitespace-nowrap w-24">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- DataTables serverSide populates this --}}
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function dailyLedgerManager(config) {
    return {
        search: '',
        preset: config.initialPreset || 'this_month',
        userId: config.initialUserId || '',
        status: '',
        dateFrom: config.initialFrom || '',
        dateTo: config.initialTo || '',
        dateLabel: config.initialLabel || 'This Month',

        setPreset(p) {
            this.preset = p;
            this.dateFrom = '';
            this.dateTo = '';
            const labels = {
                'today': 'Today',
                'yesterday': 'Yesterday',
                'this_week': 'This Week',
                'last_week': 'Last Week',
                'this_month': 'This Month',
                'last_month': 'Last Month'
            };
            this.dateLabel = labels[p] || p;
            this.applyFilters();
        },

        onCustomDateChange() {
            if (this.dateFrom || this.dateTo) {
                this.preset = '';
                this.dateLabel = (this.dateFrom || 'Start') + ' to ' + (this.dateTo || 'End');
            }
            this.applyFilters();
        },

        applyFilters() {
            if (window.ledgerTable) {
                window.ledgerTable.draw();
            }
        },

        resetFilters() {
            this.search = '';
            this.preset = 'this_month';
            this.userId = '';
            this.status = '';
            this.dateFrom = '';
            this.dateTo = '';
            this.dateLabel = 'This Month';
            if (window.ledgerTable) {
                window.ledgerTable.draw();
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('daily-ledger-table');
    if (!tableEl) return;

    window.ledgerTable = new DataTable('#daily-ledger-table', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.reports.daily-ledger.data') }}',
            data: function (d) {
                const manager = window.Alpine ? Alpine.$data(tableEl.closest('[x-data]')) : null;
                if (manager) {
                    d.search = manager.search || '';
                    d.preset = manager.preset || '';
                    d.user_id = manager.userId || '';
                    d.status = manager.status || '';
                    d.date_from = manager.dateFrom || '';
                    d.date_to = manager.dateTo || '';
                }
            }
        },
        columns: [
            // Date
            {
                data: 'date',
                className: 'whitespace-nowrap',
                render: function (data) {
                    return `
                        <div class="font-bold text-ink-900 text-xs">${data.formatted}</div>
                        <div class="text-[11px] text-ink-400 font-medium">${data.day}</div>
                    `;
                }
            },
            // Employee
            {
                data: 'employee',
                render: function (data) {
                    return `
                        <div class="font-bold text-ink-900 text-sm">${data.name}</div>
                        <div class="text-caption text-ink-400 text-xs flex items-center gap-1.5 mt-0.5">
                            <span>${data.role}</span>
                            ${data.employee_id ? `<span class="text-ink-300">•</span><span>${data.employee_id}</span>` : ''}
                        </div>
                    `;
                }
            },
            // Opening KM
            {
                data: 'opening_km',
                className: 'text-center font-medium text-ink-700 text-xs',
                render: function (data) {
                    return data !== '—' ? `<span class="font-mono">${data}</span>` : `<span class="text-ink-400">—</span>`;
                }
            },
            // Closing KM
            {
                data: 'closing_km',
                className: 'text-center font-medium text-ink-700 text-xs',
                render: function (data) {
                    return data !== '—' ? `<span class="font-mono">${data}</span>` : `<span class="text-ink-400">—</span>`;
                }
            },
            // Distance
            {
                data: 'distance',
                className: 'text-center whitespace-nowrap',
                render: function (data) {
                    if (!data || data.raw <= 0) {
                        return `<span class="text-ink-400 text-xs">—</span>`;
                    }
                    return `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">${data.formatted}</span>`;
                }
            },
            // Visits Done
            {
                data: 'visits_count',
                className: 'text-center whitespace-nowrap',
                render: function (count) {
                    if (count > 0) {
                        return `<span class="badge badge-secondary font-semibold text-xs">${count} visit${count !== 1 ? 's' : ''}</span>`;
                    }
                    return `<span class="text-ink-400 text-xs font-normal">0 visits</span>`;
                }
            },
            // Expenses Claimed
            {
                data: 'expenses_sum',
                className: 'text-right whitespace-nowrap font-bold text-xs',
                render: function (data) {
                    if (!data || data.raw <= 0) {
                        return `<span class="text-ink-400 font-normal">₹0.00</span>`;
                    }
                    return `<span class="text-ink-900">${data.formatted}</span>`;
                }
            },
            // Shift Status
            {
                data: 'status',
                className: 'text-center whitespace-nowrap',
                render: function (status) {
                    let badgeClass = 'badge-neutral';
                    if (status.code === 'completed') badgeClass = 'badge-success';
                    else if (status.code === 'in_progress') badgeClass = 'badge-primary';
                    else if (status.code === 'auto_closed') badgeClass = 'badge-warning';

                    return `<span class="badge ${badgeClass} text-[11px] font-semibold">${status.label}</span>`;
                }
            },
            // Actions (View Button)
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-right whitespace-nowrap w-24',
                render: function (actions) {
                    return `
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="${actions.show_url}" 
                               class="btn-secondary px-2.5 py-1 text-xs inline-flex items-center gap-1.5 font-semibold text-ink-700 hover:text-brand-600 hover:bg-brand-50 hover:border-brand-300 transition-colors shadow-2xs group"
                               title="View Tour Day Details">
                                <svg class="w-3.5 h-3.5 text-ink-500 group-hover:text-brand-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <span>View</span>
                            </a>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>',
        language: {
            info: "Showing _START_ to _END_ of _TOTAL_ ledger entries",
            infoEmpty: "Showing 0 records",
            infoFiltered: "(filtered from _MAX_ total)",
            emptyTable: "No shift records found for this period.",
            paginate: {
                previous: "‹ Prev",
                next: "Next ›"
            }
        },
        drawCallback: function () {
            if (window.Alpine) {
                window.Alpine.initTree(document.getElementById('daily-ledger-table'));
            }
        }
    });
});
</script>
@endpush
