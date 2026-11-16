@extends('admin.layouts.app')

@section('title', 'Monthly Expense Summary')
@section('breadcrumb', 'ADMIN / EXPENSES / MONTHLY')
@section('page_title', 'Monthly Expense Summary')

@section('content')
@php
    $carbonMonth = \Carbon\Carbon::parse($monthInput);
    $thisMonth = \Carbon\Carbon::now()->format('Y-m');
    $lastMonth = \Carbon\Carbon::now()->subMonth()->format('Y-m');
    $twoMonthsAgo = \Carbon\Carbon::now()->subMonths(2)->format('Y-m');
    $prevMonth = $carbonMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $carbonMonth->copy()->addMonth()->format('Y-m');
    $monthLabel = $carbonMonth->format('F Y');
@endphp

<div class="space-y-5">
    {{-- ── Main Filter Panel ── --}}
    <div class="panel">
        <div class="panel-header space-y-4">
            {{-- Title & Back Action --}}
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h2 class="text-section-title">Monthly Expense Summary</h2>
                    <p class="text-caption mt-1">
                        Aggregated field employee expense claims, GPS travel allowances, and payable accounting balances.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ route('admin.expenses.index') }}" class="btn-secondary text-xs h-[38px] px-3.5 inline-flex items-center gap-1.5 shadow-2xs font-semibold">
                        <x-lucide-arrow-left class="w-4 h-4 text-ink-500" />
                        <span>Back to Expenses</span>
                    </a>
                </div>
            </div>

            {{-- Quick Month Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-surface-border">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-caption uppercase tracking-wider text-ink-500 font-bold mr-1.5 text-[11px]">Quick Month:</span>
                    <a href="{{ route('admin.expenses.reports.monthly', array_filter(['month' => $thisMonth, 'user_id' => $selectedUserId ?? ''])) }}"
                       class="px-3 py-1 text-xs rounded-full border transition-all {{ $monthInput === $thisMonth ? 'bg-brand-600 text-white font-bold shadow-xs border-brand-600' : 'bg-surface-muted text-ink-600 hover:bg-slate-200 border-surface-border font-medium' }}">
                        This Month
                    </a>
                    <a href="{{ route('admin.expenses.reports.monthly', array_filter(['month' => $lastMonth, 'user_id' => $selectedUserId ?? ''])) }}"
                       class="px-3 py-1 text-xs rounded-full border transition-all {{ $monthInput === $lastMonth ? 'bg-brand-600 text-white font-bold shadow-xs border-brand-600' : 'bg-surface-muted text-ink-600 hover:bg-slate-200 border-surface-border font-medium' }}">
                        Last Month
                    </a>
                    <a href="{{ route('admin.expenses.reports.monthly', array_filter(['month' => $twoMonthsAgo, 'user_id' => $selectedUserId ?? ''])) }}"
                       class="px-3 py-1 text-xs rounded-full border transition-all {{ $monthInput === $twoMonthsAgo ? 'bg-brand-600 text-white font-bold shadow-xs border-brand-600' : 'bg-surface-muted text-ink-600 hover:bg-slate-200 border-surface-border font-medium' }}">
                        2 Months Ago
                    </a>
                </div>
                <div class="text-xs text-ink-500 font-medium flex items-center gap-1.5">
                    <x-lucide-calendar class="w-3.5 h-3.5 text-brand-600" />
                    <span>Active Period: <strong class="text-ink-900 font-bold">{{ $monthLabel }}</strong></span>
                </div>
            </div>

            {{-- Row 1: Search & Reset Controls --}}
            <div class="pt-3 border-t border-surface-border flex flex-wrap gap-3 items-end">
                {{-- Live Search --}}
                <div class="relative flex-1 min-w-[200px]">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text"
                           id="dt-worker-search"
                           placeholder="Search field worker by name or ID..."
                           class="form-input text-xs pl-9 pr-8 h-[38px] w-full">
                    <button type="button"
                            id="dt-worker-search-clear"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-400 hover:text-ink-600 transition"
                            style="display: none;"
                            title="Clear search">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </div>

                {{-- Reset Button --}}
                <button type="button"
                        id="btn-reset-filters"
                        class="btn-secondary h-[38px] px-4 text-xs font-semibold inline-flex items-center justify-center gap-1.5 shrink-0"
                        title="Reset all filters">
                    <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-500" />
                    <span>Reset Filters</span>
                </button>
            </div>

            {{-- Row 2: Month & Field Worker Dropdown Grid --}}
            <form method="GET" action="{{ route('admin.expenses.reports.monthly') }}" id="month-filter-form" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                <div>
                    <label class="form-label">Select Month</label>
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('admin.expenses.reports.monthly', array_filter(['month' => $prevMonth, 'user_id' => $selectedUserId ?? ''])) }}"
                           class="btn-secondary h-[38px] px-2.5 text-xs inline-flex items-center justify-center font-bold shrink-0"
                           title="Previous Month ({{ \Carbon\Carbon::parse($prevMonth)->format('M Y') }})">
                            <x-lucide-chevron-left class="w-4 h-4 text-ink-600" />
                        </a>

                        <div class="relative flex-1">
                            <input type="month"
                                   name="month"
                                   id="month-input"
                                   value="{{ $monthInput }}"
                                   class="form-input text-xs h-[38px] w-full font-semibold text-ink-900 text-center"
                                   onchange="this.form.submit()">
                        </div>

                        <a href="{{ route('admin.expenses.reports.monthly', array_filter(['month' => $nextMonth, 'user_id' => $selectedUserId ?? ''])) }}"
                           class="btn-secondary h-[38px] px-2.5 text-xs inline-flex items-center justify-center font-bold shrink-0"
                           title="Next Month ({{ \Carbon\Carbon::parse($nextMonth)->format('M Y') }})">
                            <x-lucide-chevron-right class="w-4 h-4 text-ink-600" />
                        </a>

                        <button type="submit" class="btn-primary h-[38px] px-3.5 text-xs font-semibold inline-flex items-center gap-1.5 shrink-0">
                            <x-lucide-filter class="w-3.5 h-3.5" />
                            <span>Apply</span>
                        </button>
                    </div>
                </div>

                <div>
                    <x-filter-select
                        name="user_id"
                        id="worker-select-input"
                        label="Field Worker"
                        placeholder="All Field Workers"
                        :options="$workers->mapWithKeys(fn($w) => [$w->id => $w->name . ' (' . ($w->roles->first()?->name ?? 'Employee') . ')'])"
                        selected="{{ $selectedUserId ?? '' }}"
                        change="document.getElementById('month-filter-form').submit()"
                    />
                </div>
            </form>

            {{-- Accounting Rule Information Banner --}}
            <div class="text-xs text-ink-600 bg-blue-50/80 border border-blue-200 rounded-lg p-3 flex items-start gap-2.5">
                <x-lucide-info class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                <div class="leading-relaxed">
                    <strong class="text-blue-900 font-bold">Accounting Rule:</strong>
                    <span class="text-ink-800">
                        "Total Payable" represents final approved disbursements (GPS Travel + HR Approved manual claims).
                        Items marked as <span class="font-semibold text-blue-800 bg-blue-100/70 px-1.5 py-0.5 rounded border border-blue-200">Verified (Pending Final)</span> have passed Manager Level 1 review and await HR final approval before payout.
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Summary Stat Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div class="panel p-4 border-l-4 border-l-emerald-500 shadow-2xs">
            <p class="text-caption text-ink-500 uppercase tracking-wider font-bold text-[11px]">Total Payable</p>
            <h3 class="text-xl font-black text-ink-900 mt-1">₹{{ number_format($totals['allGrandTotal'], 2) }}</h3>
            <span class="text-[11px] text-emerald-600 font-medium inline-block mt-0.5">Travel + Approved Manual</span>
        </div>
        <div class="panel p-4 border-l-4 border-l-brand-500 shadow-2xs">
            <p class="text-caption text-ink-500 uppercase tracking-wider font-bold text-[11px]">Travel (Approved)</p>
            <h3 class="text-xl font-bold text-brand-700 mt-1">₹{{ number_format($totals['allTravel'], 2) }}</h3>
            <span class="text-[11px] text-ink-500 inline-block mt-0.5">System GPS & Fuel</span>
        </div>
        <div class="panel p-4 border-l-4 border-l-teal-500 shadow-2xs">
            <p class="text-caption text-ink-500 uppercase tracking-wider font-bold text-[11px]">Manual (Approved)</p>
            <h3 class="text-xl font-bold text-teal-700 mt-1">₹{{ number_format($totals['allManualApproved'], 2) }}</h3>
            <span class="text-[11px] text-ink-500 inline-block mt-0.5">Bills & Allowances</span>
        </div>
        <div class="panel p-4 border-l-4 border-l-blue-500 shadow-2xs">
            <p class="text-caption text-ink-500 uppercase tracking-wider font-bold text-[11px]">Verified (Pending Final)</p>
            <h3 class="text-xl font-bold text-blue-700 mt-1">₹{{ number_format($totals['allManualVerified'], 2) }}</h3>
            <span class="text-[11px] text-blue-600 inline-block mt-0.5">L1 Approved / Needs HR</span>
        </div>
        <div class="panel p-4 border-l-4 border-l-amber-500 shadow-2xs">
            <p class="text-caption text-ink-500 uppercase tracking-wider font-bold text-[11px]">Pending L1</p>
            <h3 class="text-xl font-bold text-amber-700 mt-1">₹{{ number_format($totals['allManualPending'], 2) }}</h3>
            <span class="text-[11px] text-amber-600 inline-block mt-0.5">Awaiting Manager Review</span>
        </div>
    </div>

    {{-- Server-Side DataTables Panel --}}
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table id="monthly-expense-table" class="data-table w-full">
                <thead>
                    <tr>
                        <th>Field Worker</th>
                        <th class="text-right">Travel (Approved)</th>
                        <th class="text-right">Manual (Approved)</th>
                        <th class="text-right font-bold text-emerald-700">Total Payable</th>
                        <th class="text-right text-blue-700">Verified (Pending Final)</th>
                        <th class="text-right text-ink-500">Pending L1</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated via Server-Side AJAX -->
                </tbody>
                <tfoot class="bg-surface-ground/70 font-bold border-t border-surface-border">
                    <tr>
                        <td class="py-3.5 px-4 text-ink-900 font-extrabold uppercase">Total (All Workers):</td>
                        <td class="text-right text-brand-700 font-bold">₹{{ number_format($totals['allTravel'], 2) }}</td>
                        <td class="text-right text-teal-700 font-bold">₹{{ number_format($totals['allManualApproved'], 2) }}</td>
                        <td class="text-right text-ink-900 text-lg font-black">₹{{ number_format($totals['allGrandTotal'], 2) }}</td>
                        <td class="text-right text-blue-700 font-bold">₹{{ number_format($totals['allManualVerified'], 2) }}</td>
                        <td class="text-right text-amber-700 font-bold">₹{{ number_format($totals['allManualPending'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function getDT() {
            return window.DataTable || (typeof DataTable !== 'undefined' ? DataTable : null);
        }

        function initMonthlyDT() {
            const tableEl = document.getElementById('monthly-expense-table');
            if (!tableEl) return;
            const DT = getDT();
            if (!DT) return;

            if (window.monthlyDTInstance) {
                window.monthlyDTInstance.destroy();
            }

            window.monthlyDTInstance = new DT('#monthly-expense-table', {
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.expenses.reports.monthly.data') }}",
                    data: function (d) {
                        d.month = document.getElementById('month-input').value;
                        const workerInput = document.getElementById('worker-select-input');
                        if (workerInput && workerInput.value) {
                            d.user_id = workerInput.value;
                        } else if ('{{ $selectedUserId ?? '' }}') {
                            d.user_id = '{{ $selectedUserId }}';
                        }
                    }
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[3, 'desc']], // Default: Grand Total descending
                columns: [
                    {
                        data: 'worker',
                        name: 'users.name',
                        render: function (data) {
                            if (!data) return '—';
                            let html = `<div class="font-bold text-ink-900">${data.name}</div>`;
                            if (data.employee_id) {
                                html += `<div class="text-[11px] text-ink-400 font-mono tracking-tight">${data.employee_id}</div>`;
                            }
                            return html;
                        }
                    },
                    {
                        data: 'total_travel',
                        name: 'total_travel',
                        className: 'text-right',
                        render: function (data) {
                            return `<span class="text-brand-700 font-semibold">${data ? data.formatted : '₹0.00'}</span>`;
                        }
                    },
                    {
                        data: 'total_manual_approved',
                        name: 'total_manual_approved',
                        className: 'text-right',
                        render: function (data) {
                            return `<span class="text-teal-700 font-semibold">${data ? data.formatted : '₹0.00'}</span>`;
                        }
                    },
                    {
                        data: 'grand_total',
                        name: 'grand_total',
                        className: 'text-right',
                        render: function (data) {
                            return `<span class="font-black text-ink-900 text-base">${data ? data.formatted : '₹0.00'}</span>`;
                        }
                    },
                    {
                        data: 'total_manual_verified',
                        name: 'total_manual_verified',
                        className: 'text-right',
                        render: function (data) {
                            if (data && data.raw > 0) {
                                return `<span class="px-2 py-0.5 rounded-md bg-blue-50 border border-blue-200 text-blue-700 font-medium text-xs">${data.formatted}</span>`;
                            }
                            return `<span class="text-ink-400 font-medium">—</span>`;
                        }
                    },
                    {
                        data: 'total_manual_pending',
                        name: 'total_manual_pending',
                        className: 'text-right',
                        render: function (data) {
                            if (data && data.raw > 0) {
                                return `<span class="text-amber-700 font-semibold text-xs">${data.formatted}</span>`;
                            }
                            return `<span class="text-ink-400 font-medium">—</span>`;
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap',
                        render: function (data) {
                            if (!data || !data.statement_url) return '';
                            return `<a href="${data.statement_url}" class="btn-primary text-xs px-3 py-1.5 inline-flex items-center gap-1.5 font-medium rounded-lg">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Statement
                            </a>`;
                        }
                    }
                ],
                // Clean bottom-only pagination: NO top controls, proper info and page buttons on bottom
                dom: 'rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-surface-border"<"text-xs text-ink-600"i><"dt-paging"p>>',
                layout: {
                    topStart: null,
                    topEnd: null,
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ field workers",
                    infoEmpty: "Showing 0 to 0 of 0 field workers",
                    infoFiltered: "(filtered from _MAX_ total)",
                    zeroRecords: "No matching field workers found.",
                    emptyTable: "No field workers found for this month."
                }
            });

            // Wire up external live search input
            const searchInput = document.getElementById('dt-worker-search');
            const clearBtn = document.getElementById('dt-worker-search-clear');
            let searchTimeout = null;

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const val = this.value;
                    if (clearBtn) clearBtn.style.display = val ? 'block' : 'none';
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        window.monthlyDTInstance.search(val).draw();
                    }, 300);
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    if (searchInput) {
                        searchInput.value = '';
                        clearBtn.style.display = 'none';
                        window.monthlyDTInstance.search('').draw();
                    }
                });
            }

            // Wire up Reset button
            const resetBtn = document.getElementById('btn-reset-filters');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    if (searchInput) {
                        searchInput.value = '';
                        if (clearBtn) clearBtn.style.display = 'none';
                    }
                    window.location.href = "{{ route('admin.expenses.reports.monthly') }}";
                });
            }
        }

        if (getDT()) {
            initMonthlyDT();
        } else {
            let tries = 0;
            const timer = setInterval(() => {
                tries++;
                if (getDT()) {
                    clearInterval(timer);
                    initMonthlyDT();
                } else if (tries > 50) {
                    clearInterval(timer);
                }
            }, 50);
        }
    });
</script>
@endpush