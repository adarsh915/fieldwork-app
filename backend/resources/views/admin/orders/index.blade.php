@extends('admin.layouts.app')

@section('title', 'Orders')
@section('breadcrumb', 'Admin / Orders')
@section('page_title', 'Orders')

@section('content')
<div class="panel" x-data="{
    userId: '{{ request('user_id') }}',
    orderSource: '{{ request('order_source') }}',
    timing: '{{ request('timing') }}',
    search: '{{ request('search') }}',
    status: '{{ request('status') }}',
    loading: false,
    records: {{ Js::from($orders->map(fn($order) => [
        'id'                     => $order->id,
        'order_no'               => $order->order_no,
        'party_name'             => $order->party?->name ?? 'N/A',
        'party_code'             => $order->party?->party_code ?? $order->party?->code ?? '',
        'user_name'              => $order->user?->name ?? 'Unknown',
        'order_source'           => $order->order_source,
        'is_after_day_end'       => (bool) $order->is_after_day_end,
        'order_date_formatted'   => $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : \Carbon\Carbon::parse($order->created_at)->format('d M Y'),
        'total_amount_formatted' => number_format((float) $order->total_amount, 2),
        'status'                 => $order->status,
        'show_url'               => route('admin.orders.show', $order->id),
    ])) }},
    counts: {{ Js::from($counts) }},
    pagination: {
        current_page: {{ $orders->currentPage() }},
        last_page: {{ $orders->lastPage() }},
        per_page: {{ $orders->perPage() }},
        total: {{ $orders->total() }},
        from: {{ $orders->firstItem() ?? 0 }},
        to: {{ $orders->lastItem() ?? 0 }}
    },

    async fetchRecords(page = 1) {
        this.loading = true;
        try {
            const params = new URLSearchParams();
            if (this.userId) params.append('user_id', this.userId);
            if (this.orderSource) params.append('order_source', this.orderSource);
            if (this.timing) params.append('timing', this.timing);
            if (this.search) params.append('search', this.search);
            if (this.status) params.append('status', this.status);
            params.append('page', page);

            const res = await fetch(`{{ route('admin.orders.index') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.success) {
                this.records = data.records;
                this.counts = data.counts;
                this.pagination = data.pagination;
            }
        } catch (e) {
            console.error('Failed to load orders', e);
        } finally {
            this.loading = false;
        }
    },

    setStatus(newStatus) {
        this.status = newStatus;
        this.fetchRecords(1);
    },

    resetFilters() {
        this.userId = '';
        this.orderSource = '';
        this.timing = '';
        this.search = '';
        this.status = '';
        this.fetchRecords(1);
    }
}">
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
               :class="status === 'dispatched' ? 'bg-emerald-50 border-emerald-300 text-emerald-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                Dispatched <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'dispatched' ? 'bg-emerald-200 text-emerald-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.dispatched ?? 0"></span>
            </button>
            <button type="button" @click="setStatus('delivered')" 
               class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
               :class="status === 'delivered' ? 'bg-teal-50 border-teal-300 text-teal-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                Delivered <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'delivered' ? 'bg-teal-200 text-teal-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.delivered ?? 0"></span>
            </button>
            <button type="button" @click="setStatus('cancelled')" 
               class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5" 
               :class="status === 'cancelled' ? 'bg-rose-50 border-rose-300 text-rose-800 shadow-xs' : 'bg-white border-surface-border text-ink-600 hover:bg-surface-muted'">
                Cancelled <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="status === 'cancelled' ? 'bg-rose-200 text-rose-900 font-bold' : 'bg-surface-muted text-ink-500'" x-text="counts.cancelled ?? 0"></span>
            </button>
        </div>

        {{-- Filters Form --}}
        <form @submit.prevent="fetchRecords(1)" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="form-label">Field Worker</label>
                <select x-model="userId" @change="fetchRecords(1)" class="form-input">
                    <option value="">All Field Workers</option>
                    @foreach($fieldWorkers as $worker)
                        <option value="{{ $worker->id }}">
                            {{ $worker->name }} ({{ $worker->role_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Order Source</label>
                <select x-model="orderSource" @change="fetchRecords(1)" class="form-input">
                    <option value="">All Sources</option>
                    <option value="visit">📍 Physical Visit</option>
                    <option value="phone">📞 Phone / Direct</option>
                </select>
            </div>
            <div>
                <label class="form-label">Shift Timing</label>
                <select x-model="timing" @change="fetchRecords(1)" class="form-input">
                    <option value="">All Timings</option>
                    <option value="during_shift">During Active Shift</option>
                    <option value="after_day_end">After Day End (Off-Shift)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Search</label>
                <input type="text" x-model="search" @keydown.enter.prevent="fetchRecords(1)" placeholder="Order no or party name..." class="form-input">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" :disabled="loading" class="btn-primary flex-1 py-2 text-sm font-semibold">
                    <span x-show="!loading" class="flex items-center gap-1.5">
                        <x-lucide-filter class="w-4 h-4" />
                        Filter
                    </span>
                    <span x-show="loading" class="flex items-center gap-1.5">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Filtering...
                    </span>
                </button>
                <button type="button" @click="resetFilters" class="btn-secondary px-3 py-2 text-sm">
                    Reset
                </button>
            </div>
        </form>
    </div>

    {{-- DataTable Wrap --}}
    <div class="table-wrap relative min-h-[300px]">
        {{-- Loading Overlay --}}
        <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center z-10" x-transition>
            <div class="flex items-center gap-2 text-brand-600 font-semibold text-sm">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating orders...
            </div>
        </div>

        <table class="data-table">
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
                <template x-for="order in records" :key="order.id">
                    <tr class="hover:bg-surface-muted/60 transition-colors">
                        <td class="font-mono text-xs font-bold text-brand-700" x-text="order.order_no"></td>
                        <td>
                            <div class="font-semibold text-ink-900" x-text="order.party_name"></div>
                            <template x-if="order.party_code">
                                <div class="text-xs text-ink-400 font-mono" x-text="order.party_code"></div>
                            </template>
                        </td>
                        <td class="text-body" x-text="order.user_name"></td>
                        <td>
                            <template x-if="order.order_source === 'phone'">
                                <div>
                                    <template x-if="order.is_after_day_end">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200" title="Direct Phone Order placed after day end / off-shift">
                                            🌙 Phone (Off-Shift)
                                        </span>
                                    </template>
                                    <template x-if="!order.is_after_day_end">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-800 border border-blue-200" title="Direct Phone Order placed during active shift">
                                            📞 Phone (Shift)
                                        </span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="order.order_source !== 'phone'">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200" title="Physical Store Visit Order">
                                    📍 Visit Order
                                </span>
                            </template>
                        </td>
                        <td class="text-caption" x-text="order.order_date_formatted"></td>
                        <td class="font-bold text-ink-900" x-text="'₹' + order.total_amount_formatted"></td>
                        <td>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                :class="{
                                    'bg-amber-100 text-amber-800': order.status === 'pending',
                                    'bg-blue-100 text-blue-800': order.status === 'approved',
                                    'bg-emerald-100 text-emerald-800': order.status === 'dispatched' || order.status === 'delivered',
                                    'bg-rose-100 text-rose-800': order.status === 'cancelled',
                                    'bg-surface-muted text-ink-600': !['pending','approved','dispatched','delivered','cancelled'].includes(order.status)
                                }"
                                x-text="order.status.charAt(0).toUpperCase() + order.status.slice(1)">
                            </span>
                        </td>
                        <td class="text-right">
                            <a :href="order.show_url" class="btn-secondary text-xs px-2.5 py-1.5 inline-flex items-center gap-1.5">
                                <x-lucide-eye class="w-3.5 h-3.5" />
                                View
                            </a>
                        </td>
                    </tr>
                </template>

                <tr x-show="!loading && records.length === 0">
                    <td colspan="8" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2 text-ink-400">
                            <x-lucide-inbox class="w-10 h-10" />
                            <p class="text-sm font-medium">No orders found.</p>
                            <p class="text-xs text-ink-400">Orders placed by field workers will appear here.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    {{-- AJAX Pagination --}}
    <div x-show="pagination.last_page > 1" class="border-t border-surface-border px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-ink-500">
            Showing <span class="font-semibold text-ink-800" x-text="pagination.from"></span> to <span class="font-semibold text-ink-800" x-text="pagination.to"></span> of <span class="font-semibold text-ink-800" x-text="pagination.total"></span> orders
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button"
                @click="fetchRecords(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1 || loading"
                class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                Previous
            </button>
            <span class="text-xs text-ink-600 px-2 font-medium">
                Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
            </span>
            <button type="button"
                @click="fetchRecords(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page || loading"
                class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>
    </div>
</div>
@endsection
