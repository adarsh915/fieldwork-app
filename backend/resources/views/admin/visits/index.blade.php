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
    records: {{ Js::from($visits->map(fn($visit) => [
        'id'             => $visit->id,
        'party_name'     => $visit->party?->name ?? '—',
        'party_code'     => $visit->party?->party_code ?? $visit->party?->code ?? '',
        'user_name'      => $visit->user?->name ?? 'Unknown',
        'check_in_time'  => $visit->check_in_time ? $visit->check_in_time->format('d M Y, h:i A') : 'Pending',
        'check_out_time' => $visit->check_out_time ? $visit->check_out_time->format('d M Y, h:i A') : '—',
        'status'         => $visit->status,
        'purpose'        => $visit->purpose ?? '',
        'notes'          => $visit->notes ?? '',
        'check_in_photo' => $visit->check_in_photo_url,
    ])) }},
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
        </div>

        {{-- AJAX Filters Form --}}
        <form @submit.prevent="fetchRecords(1)" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" x-model="dateFrom" @change="fetchRecords(1)" class="form-input">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" x-model="dateTo" @change="fetchRecords(1)" class="form-input">
            </div>
            <div>
                <label class="form-label">Field Worker</label>
                <select x-model="employeeId" @change="fetchRecords(1)" class="form-input">
                    <option value="">All Field Workers</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">
                            {{ $employee->name }} ({{ $employee->role_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select x-model="status" @change="fetchRecords(1)" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div>
                <label class="form-label">Search</label>
                <input type="text" x-model="search" @keydown.enter.prevent="fetchRecords(1)" placeholder="Party or notes..." class="form-input">
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
                        </td>
                        <td class="text-body font-medium" x-text="visit.user_name"></td>
                        <td class="text-caption" x-text="visit.check_in_time"></td>
                        <td class="text-caption" x-text="visit.check_out_time"></td>
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
    
    {{-- AJAX Pagination --}}
    <div x-show="pagination.last_page > 1" class="border-t border-surface-border px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-ink-500">
            Showing <span class="font-semibold text-ink-800" x-text="pagination.from"></span> to <span class="font-semibold text-ink-800" x-text="pagination.to"></span> of <span class="font-semibold text-ink-800" x-text="pagination.total"></span> visits
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

