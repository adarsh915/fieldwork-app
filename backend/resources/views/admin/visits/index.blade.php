@extends('admin.layouts.app')

@section('title', 'Visits')
@section('breadcrumb', 'ADMIN / VISITS')
@section('page_title', 'Field Worker Visits')

@section('content')
<div class="panel" x-data="{
    lightboxOpen: false,
    lightboxImage: '',
    dateFrom: '{{ request('date_from') }}',
    dateTo: '{{ request('date_to') }}',
    employeeId: '{{ request('employee_id') }}',
    status: '{{ request('status') }}',
    search: '{{ request('search') }}',
    loading: false,
    records: {{ Js::from($visits->map(function($visit) {
        $distance = '—';
        if ($visit->check_in_lat && $visit->check_in_lng && $visit->party?->latitude && $visit->party?->longitude) {
            $earthRadius = 6371000;
            $latDelta = deg2rad((float)$visit->party->latitude - (float)$visit->check_in_lat);
            $lonDelta = deg2rad((float)$visit->party->longitude - (float)$visit->check_in_lng);
            $a = sin($latDelta / 2) * sin($latDelta / 2) + cos(deg2rad((float)$visit->check_in_lat)) * cos(deg2rad((float)$visit->party->latitude)) * sin($lonDelta / 2) * sin($lonDelta / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $m = round($earthRadius * $c);
            $distance = $m < 1000 ? "{$m} m" : round($m / 1000, 2) . " km";
        }

        return [
            'id'             => $visit->id,
            'party_name'     => $visit->party?->name ?? '—',
            'party_code'     => $visit->party?->party_code ?? $visit->party?->code ?? '',
            'party_lat'      => $visit->party?->latitude,
            'party_lng'      => $visit->party?->longitude,
            'user_name'      => $visit->user?->name ?? 'Unknown',
            'check_in_time'  => $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
            'check_in_lat'   => $visit->check_in_lat,
            'check_in_lng'   => $visit->check_in_lng,
            'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
            'check_out_lat'  => $visit->check_out_lat,
            'check_out_lng'  => $visit->check_out_lng,
            'distance'       => $distance,
            'status'         => $visit->status,
            'purpose'        => $visit->purpose ?? '',
            'notes'          => $visit->notes ?? '',
            'check_in_photo' => $visit->check_in_photo_url,
        ];
    })) }},
    pagination: {
        current_page: {{ $visits->currentPage() }},
        last_page: {{ $visits->lastPage() }},
        per_page: {{ $visits->perPage() }},
        total: {{ $visits->total() }},
        from: {{ $visits->firstItem() ?? 0 }},
        to: {{ $visits->lastItem() ?? 0 }}
    },

    async fetchRecords(page = 1) {
        this.loading = true;
        try {
            const params = new URLSearchParams();
            if (this.dateFrom) params.append('date_from', this.dateFrom);
            if (this.dateTo) params.append('date_to', this.dateTo);
            if (this.employeeId) params.append('employee_id', this.employeeId);
            if (this.status) params.append('status', this.status);
            if (this.search) params.append('search', this.search);
            params.append('page', page);

            const res = await fetch(`{{ route('admin.visits.index') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.success) {
                this.records = data.records;
                this.pagination = data.pagination;
            }
        } catch (e) {
            console.error('Failed to load visits', e);
        } finally {
            this.loading = false;
        }
    },

    resetFilters() {
        this.dateFrom = '';
        this.dateTo = '';
        this.employeeId = '';
        this.status = '';
        this.search = '';
        this.fetchRecords(1);
    },

    exportUrl() {
        const params = new URLSearchParams();
        if (this.dateFrom) params.append('date_from', this.dateFrom);
        if (this.dateTo) params.append('date_to', this.dateTo);
        if (this.employeeId) params.append('employee_id', this.employeeId);
        if (this.status) params.append('status', this.status);
        if (this.search) params.append('search', this.search);
        const query = params.toString();
        return `{{ route('admin.visits.export') }}${query ? '?' + query : ''}`;
    },

    getPages() {
        const current = Number(this.pagination.current_page) || 1;
        const last = Number(this.pagination.last_page) || 1;
        const delta = 2;
        const range = [];
        const rangeWithDots = [];
        let l;

        for (let i = 1; i <= last; i++) {
            if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
                range.push(i);
            }
        }

        for (let i of range) {
            if (l) {
                if (i - l === 2) {
                    rangeWithDots.push(l + 1);
                } else if (i - l !== 1) {
                    rangeWithDots.push('...');
                }
            }
            rangeWithDots.push(i);
            l = i;
        }

        return rangeWithDots;
    }
}">
    <!-- Lightbox Modal -->
    <div x-show="lightboxOpen" style="display: none; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.9); backdrop-filter: blur(4px);" x-transition>
        <button type="button" @click="lightboxOpen = false" style="position: absolute; top: 2rem; right: 2rem; color: white; background: none; border: none; cursor: pointer; z-index: 10000;">
            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <img :src="lightboxImage" style="max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); margin: auto; display: block;" @click.away="lightboxOpen = false">
    </div>

    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">Visit Records</h2>
                <p class="text-caption mt-1">Real-time dealer and store check-ins logged by field workers.</p>
            </div>
            <div class="flex items-center gap-2">
                @canany(['visits.view', 'visits.view-team', 'visits.view-all', 'visits.view-own', 'visits.manage'])
                    <a :href="exportUrl()"
                       class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-sm bg-emerald-600 hover:bg-emerald-700 border-emerald-600 text-white font-medium rounded-lg transition"
                       title="Export filtered visits to Excel">
                        <x-lucide-download class="w-4 h-4" />
                        <span>Export Excel</span>
                    </a>
                @endcanany
            </div>
        </div>

        {{-- Search & Reset Controls --}}
        <div class="mt-4 flex flex-wrap gap-3 items-end">
            {{-- Search --}}
            <div class="relative flex-1 min-w-[200px]">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                <input type="text" x-model="search" @input.debounce.350ms="fetchRecords(1)" @keydown.enter.prevent="fetchRecords(1)" placeholder="Search party name, code, purpose, notes..." class="form-input text-xs pl-9 pr-8 h-[38px] w-full">
                <button type="button"
                        x-show="search"
                        @click="search = ''; fetchRecords(1)"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-400 hover:text-ink-600 transition"
                        style="display: none;"
                        title="Clear search">
                    <x-lucide-x class="w-3.5 h-3.5" />
                </button>
            </div>

            {{-- Reset Button --}}
            <button type="button" @click="resetFilters()" class="btn-secondary h-[38px] px-4 text-xs font-semibold flex items-center justify-center gap-1.5 shrink-0" title="Reset all filters">
                <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-500" />
                <span>Reset Filters</span>
            </button>
        </div>

        {{-- Filters Grid (4 Columns, exactly like Day Starts) --}}
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" x-model="dateFrom" @change="fetchRecords(1)" class="form-input text-xs h-[38px] w-full">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" x-model="dateTo" @change="fetchRecords(1)" class="form-input text-xs h-[38px] w-full">
            </div>
            <x-filter-select
                label="Field Worker"
                placeholder="All Field Workers"
                :options="$employees->mapWithKeys(fn($e) => [$e->id => $e->name . ' (' . ($e->role_name ?? 'Field Worker') . ')'])"
                model="employeeId"
                change="fetchRecords(1)"
            />
            <x-filter-select
                label="Status"
                placeholder="All Statuses"
                :options="[
                    'pending' => 'Pending',
                    'completed' => 'Completed'
                ]"
                model="status"
                change="fetchRecords(1)"
            />
        </div>
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
                Updating visits...
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Party (Customer)</th>
                    <th>Field Worker</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Purpose / Notes</th>
                    <th class="text-right">Photo</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="visit in records" :key="visit.id">
                    <tr class="hover:bg-surface-muted/60 transition-colors">
                        <td>
                            <div class="font-semibold text-ink-900" x-text="visit.party_name"></div>
                            <template x-if="visit.party_code">
                                <div class="text-xs text-ink-400 font-mono" x-text="visit.party_code"></div>
                            </template>
                            <template x-if="visit.party_lat && visit.party_lng">
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5 flex items-center gap-1" title="Mart Actual Coordinates">
                                    <span class="text-emerald-600 font-sans font-medium text-[10px]">Mart:</span>
                                    <span x-text="`${parseFloat(visit.party_lat).toFixed(5)}, ${parseFloat(visit.party_lng).toFixed(5)}`"></span>
                                </div>
                            </template>
                        </td>
                        <td class="text-body font-medium" x-text="visit.user_name"></td>
                        <td class="text-caption">
                            <span x-text="visit.check_in_time"></span>
                            <template x-if="visit.check_in_lat && visit.check_in_lng">
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5" title="Check-In Coordinates">
                                    <span x-text="`${parseFloat(visit.check_in_lat).toFixed(5)}, ${parseFloat(visit.check_in_lng).toFixed(5)}`"></span>
                                    <template x-if="visit.distance && visit.distance !== '—'">
                                        <span class="text-brand-600 font-sans font-medium text-[10px] block" x-text="`(${visit.distance} away)`"></span>
                                    </template>
                                </div>
                            </template>
                        </td>
                        <td class="text-caption">
                            <span x-text="visit.check_out_time"></span>
                            <template x-if="visit.check_out_lat && visit.check_out_lng">
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5" title="Check-Out Coordinates">
                                    <span x-text="`${parseFloat(visit.check_out_lat).toFixed(5)}, ${parseFloat(visit.check_out_lng).toFixed(5)}`"></span>
                                </div>
                            </template>
                        </td>
                        <td>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                :class="{
                                    'bg-emerald-100 text-emerald-800': visit.status === 'completed',
                                    'bg-amber-100 text-amber-800': visit.status === 'pending',
                                    'bg-surface-muted text-ink-700': visit.status !== 'completed' && visit.status !== 'pending'
                                }"
                                x-text="visit.status.charAt(0).toUpperCase() + visit.status.slice(1)">
                            </span>
                        </td>
                        <td class="text-caption max-w-xs">
                            <template x-if="visit.purpose">
                                <span class="font-semibold text-ink-800 block" x-text="visit.purpose"></span>
                            </template>
                            <span class="text-ink-600 block truncate" :title="visit.notes" x-text="visit.notes || '—'"></span>
                        </td>
                        <td class="text-right">
                            <template x-if="visit.check_in_photo">
                                <button type="button" @click="lightboxImage = visit.check_in_photo; lightboxOpen = true;" class="inline-block relative group cursor-pointer focus:outline-none" title="View Check-In Photo">
                                    <img :src="visit.check_in_photo" alt="Check In" class="w-10 h-10 object-cover rounded-lg border border-surface-border">
                                    <span class="absolute inset-0 bg-black/40 text-white text-[9px] font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 rounded-lg">View</span>
                                </button>
                            </template>
                            <template x-if="!visit.check_in_photo">
                                <span class="text-xs text-ink-400 italic">—</span>
                            </template>
                        </td>
                    </tr>
                </template>

                <tr x-show="!loading && records.length === 0">
                    <td colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2 text-ink-400">
                            <x-lucide-inbox class="w-10 h-10" />
                            <p class="text-sm font-medium">No visits found.</p>
                            <p class="text-xs text-ink-400">Field worker visits will appear here once they check in.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    {{-- AJAX Pagination with Prev, Next & Clickable Page Numbers --}}
    <div x-show="pagination.last_page > 1" class="border-t border-surface-border px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-ink-600">
            Showing <span class="font-semibold text-ink-900" x-text="pagination.from"></span> to <span class="font-semibold text-ink-900" x-text="pagination.to"></span> of <span class="font-semibold text-ink-900" x-text="pagination.total"></span> results
        </div>
        <div class="dt-paging flex items-center gap-1">
            <button type="button"
                @click="fetchRecords(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1 || loading"
                class="dt-paging-button previous"
                :class="{ 'disabled': pagination.current_page <= 1 || loading }">
                ‹ Prev
            </button>

            <template x-for="(p, idx) in getPages()" :key="idx">
                <span>
                    <template x-if="p === '...'">
                        <span class="ellipsis">…</span>
                    </template>
                    <template x-if="p !== '...'">
                        <button type="button"
                            @click="fetchRecords(p)"
                            :class="p === pagination.current_page ? 'dt-paging-button current' : 'dt-paging-button'"
                            :disabled="loading"
                            x-text="p">
                        </button>
                    </template>
                </span>
            </template>

            <button type="button"
                @click="fetchRecords(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page || loading"
                class="dt-paging-button next"
                :class="{ 'disabled': pagination.current_page >= pagination.last_page || loading }">
                Next ›
            </button>
        </div>
    </div>
</div>
@endsection

