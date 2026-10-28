@extends('admin.layouts.app')

@section('title', 'Orders')
@section('breadcrumb', 'Admin / Orders')
@section('page_title', 'Orders')

@section('content')

    @if(session('status') || session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
            <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
            <p class="text-sm font-medium text-success-text">{{ session('status') ?? session('success') }}</p>
        </div>
    @endif

    <div class="panel" x-data="{
        status: '',
        search: '',
        userId: '',
        orderSource: '',
        timing: '',
        counts: {{ Js::from($counts) }},
        filtersVisible: false,

        setStatus(newStatus) {
            this.status = newStatus;
            window.ordersTable.draw();
        },

        resetFilters() {
            this.search = '';
            this.userId = '';
            this.orderSource = '';
            this.timing = '';
            this.status = '';
            window.ordersTable.search('').draw();
        }
    }" id="order-filters-container">
        {{-- Header --}}
        <div class="panel-header">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-section-title">Order Management</h2>
                    <p class="text-caption mt-1">Track, filter, and review orders placed across physical visits and phone calls.</p>
                </div>
            </div>

            {{-- Status Pills / Tabs --}}
            <div class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-surface-border">
                <button type="button" @click="setStatus('')" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
                   :class="!status ? 'bg-brand-50 border-brand-300 text-brand-700 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                    All <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="!status ? 'bg-brand-200/70 text-brand-800 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.all ?? 0"></span>
                </button>
                <button type="button" @click="setStatus('pending')" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
                   :class="status === 'pending' ? 'bg-amber-50 border-amber-300 text-amber-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                    Pending <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'pending' ? 'bg-amber-200 text-amber-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.pending ?? 0"></span>
                </button>
                <button type="button" @click="setStatus('approved')" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
                   :class="status === 'approved' ? 'bg-blue-50 border-blue-300 text-blue-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                    Approved <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'approved' ? 'bg-blue-200 text-blue-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.approved ?? 0"></span>
                </button>
                <button type="button" @click="setStatus('dispatched')" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
                   :class="status === 'dispatched' ? 'bg-indigo-50 border-indigo-300 text-indigo-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                    Dispatched <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'dispatched' ? 'bg-indigo-200 text-indigo-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.dispatched ?? 0"></span>
                </button>
                <button type="button" @click="setStatus('delivered')" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
                   :class="status === 'delivered' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                    Delivered <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'delivered' ? 'bg-emerald-200 text-emerald-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.delivered ?? 0"></span>
                </button>
                <button type="button" @click="setStatus('cancelled')" 
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
                   :class="status === 'cancelled' ? 'bg-rose-50 border-rose-300 text-rose-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                    Cancelled <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'cancelled' ? 'bg-rose-200 text-rose-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.cancelled ?? 0"></span>
                </button>
            </div>

            {{-- Quick Search & Filter Controls --}}
            <div class="mt-4">
                <div class="flex flex-wrap gap-3 items-end">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[220px]">
                        <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                        <input x-model="search" @input.debounce.400ms="window.ordersTable.search(search).draw()"
                            class="form-input pl-9" placeholder="Search order no, customer name, employee...">
                    </div>

                    {{-- Toggle Filters Button --}}
                    <x-button type="button" variant="secondary" @click="filtersVisible = !filtersVisible">
                        <x-lucide-filter class="w-4 h-4" />
                        <span x-text="filtersVisible ? 'Hide Filters' : 'Show Filters'"></span>
                    </x-button>

                    {{-- Clear All --}}
                    <x-button type="button" variant="secondary"
                        @click="resetFilters()"
                        x-show="search || userId || orderSource || timing || status" style="display: none;">
                        <x-lucide-x class="w-4 h-4" />
                        Clear
                    </x-button>
                </div>

                {{-- Advanced Filters --}}
                <div x-show="filtersVisible" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3" style="display: none;">
                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Field Worker</label>
                        <select x-model="userId" @change="window.ordersTable.draw()" class="form-input">
                            <option value="">All Field Workers</option>
                            @foreach($fieldWorkers as $worker)
                                <option value="{{ $worker->id }}">
                                    {{ $worker->name }} ({{ $worker->role_name ?? ($worker->roles->first()?->name ?? 'Field Worker') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Order Source</label>
                        <select x-model="orderSource" @change="window.ordersTable.draw()" class="form-input">
                            <option value="">All Sources</option>
                            <option value="visit">📍 Physical Visit</option>
                            <option value="phone">📞 Phone / Direct</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Shift Timing</label>
                        <select x-model="timing" @change="window.ordersTable.draw()" class="form-input">
                            <option value="">All Timings</option>
                            <option value="during_shift">During Active Shift</option>
                            <option value="after_day_end">After Day End (Off-Shift)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- DataTable Wrap --}}
        <div class="table-wrap min-h-[350px]">
            <table id="orders-table" class="data-table">
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Party (Customer)</th>
                        <th>Created By</th>
                        <th>Source / Timing</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- DataTables will populate this --}}
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Orders DataTable
            window.ordersTable = new DataTable('#orders-table', {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.orders.data') }}',
                    data: function (d) {
                        const container = document.getElementById('order-filters-container');
                        const filters = window.Alpine ? Alpine.$data(container) : null;
                        if (filters) {
                            d.search = filters.search || '';
                            d.user_id = filters.userId || '';
                            d.order_source = filters.orderSource || '';
                            d.timing = filters.timing || '';
                            d.status = filters.status || '';
                        }
                    },
                    dataSrc: function (json) {
                        // Dynamically update status counts if returned
                        if (json.counts && window.Alpine) {
                            const container = document.getElementById('order-filters-container');
                            const alpineData = Alpine.$data(container);
                            if (alpineData) {
                                alpineData.counts = json.counts;
                            }
                        }
                        return json.data;
                    }
                },
                columns: [
                    {
                        data: 'order_no',
                        render: function (data) {
                            return `<span class="font-mono text-xs font-bold text-brand-700">${data}</span>`;
                        }
                    },
                    {
                        data: 'party',
                        render: function (data) {
                            return `
                                <div class="font-semibold text-ink-900">${data.name}</div>
                                <div class="text-xs text-ink-400 font-mono">${data.code ? data.code : (data.city ? data.city : '—')}</div>
                            `;
                        }
                    },
                    {
                        data: 'user',
                        render: function (data) {
                            return `
                                <div class="text-body font-medium">${data.name}</div>
                                <div class="text-caption">${data.role}</div>
                            `;
                        }
                    },
                    {
                        data: 'source_timing',
                        render: function (data) {
                            if (data.source === 'phone') {
                                if (data.is_after_day_end) {
                                    return `
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200" title="Direct Phone Order placed after day end / off-shift">
                                            🌙 Phone (Off-Shift)
                                        </span>
                                    `;
                                }
                                return `
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-800 border border-blue-200" title="Direct Phone Order placed during active shift">
                                        📞 Phone (Shift)
                                    </span>
                                `;
                            }
                            return `
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200" title="Physical Store Visit Order">
                                    📍 Visit Order
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'date',
                        render: function (data) {
                            return `<div class="text-caption">${data.formatted}</div>`;
                        }
                    },
                    {
                        data: 'amount',
                        render: function (data) {
                            return `<div class="font-bold text-ink-900">₹${data}</div>`;
                        }
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            let badgeClass = 'bg-surface-muted text-ink-600';
                            let label = (data || 'pending').charAt(0).toUpperCase() + (data || 'pending').slice(1);
                            
                            if (data === 'pending') {
                                badgeClass = 'bg-amber-100 text-amber-800';
                            } else if (data === 'approved') {
                                badgeClass = 'bg-blue-100 text-blue-800';
                            } else if (data === 'dispatched') {
                                badgeClass = 'bg-indigo-100 text-indigo-800';
                            } else if (data === 'delivered') {
                                badgeClass = 'bg-emerald-100 text-emerald-800';
                            } else if (data === 'cancelled') {
                                badgeClass = 'bg-rose-100 text-rose-800';
                            }

                            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${badgeClass}">${label}</span>`;
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap',
                        render: function (data) {
                            return `
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" @click.stop class="p-1.5 rounded-lg hover:bg-surface-muted transition-colors text-ink-600 focus:outline-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-transition 
                                         class="absolute right-0 mt-1 min-w-[220px] bg-white border border-surface-border rounded-xl shadow-xl z-50 py-1.5 top-full" 
                                         style="min-width: 220px; width: max-content; display: none;">
                                        {{-- View Details --}}
                                        <a href="${data.show_url}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink-900 hover:bg-surface-muted whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-ink-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>View Order Details</span>
                                        </a>

                                        ${data.can_manage ? `
                                        {{-- Change Status Section --}}
                                        <div class="my-1.5 border-t border-surface-border"></div>
                                        <div class="px-3.5 py-1 text-[10px] font-bold text-ink-400 uppercase tracking-wider">Change Status</div>

                                        ${data.status !== 'approved' ? `
                                        <button type="button" onclick="updateOrderStatus('${data.update_status_url}', 'approved', '${data.order_no}')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-blue-700 hover:bg-blue-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Mark as Approved</span>
                                        </button>
                                        ` : ''}

                                        ${data.status !== 'dispatched' && data.status !== 'delivered' && data.status !== 'cancelled' ? `
                                        <button type="button" onclick="updateOrderStatus('${data.update_status_url}', 'dispatched', '${data.order_no}')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-indigo-700 hover:bg-indigo-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                            <span>Mark as Dispatched</span>
                                        </button>
                                        ` : ''}

                                        ${data.status !== 'delivered' && data.status !== 'cancelled' ? `
                                        <button type="button" onclick="updateOrderStatus('${data.update_status_url}', 'delivered', '${data.order_no}')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-700 hover:bg-emerald-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Mark as Delivered</span>
                                        </button>
                                        ` : ''}

                                        ${data.status !== 'pending' ? `
                                        <button type="button" onclick="updateOrderStatus('${data.update_status_url}', 'pending', '${data.order_no}')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-amber-700 hover:bg-amber-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Reset to Pending</span>
                                        </button>
                                        ` : ''}

                                        ${data.status !== 'cancelled' ? `
                                        <div class="my-1.5 border-t border-surface-border"></div>
                                        <button type="button" onclick="updateOrderStatus('${data.update_status_url}', 'cancelled', '${data.order_no}')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-700 hover:bg-rose-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Cancel Order</span>
                                        </button>
                                        ` : ''}
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 20,
                lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
                dom: 'rt<"flex items-center justify-between px-6 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>',
                drawCallback: function () {
                    if (window.Alpine) {
                        window.Alpine.initTree(document.getElementById('orders-table'));
                    }
                }
            });

            // Update Order Status via AJAX
            window.updateOrderStatus = async function (url, newStatus, orderNo) {
                const label = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                if (!confirm(`Are you sure you want to mark order #${orderNo} as "${label}"?`)) return;

                try {
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ status: newStatus })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        window.toast(data.message || `Order #${orderNo} marked as ${label}.`, 'success');
                        if (window.ordersTable) {
                            window.ordersTable.ajax.reload(null, false);
                        }
                    } else {
                        window.toast(data.message || 'Failed to update order status.', 'error');
                    }
                } catch (e) {
                    window.toast('Error updating order status.', 'error');
                }
            };
        });
    </script>
@endpush
