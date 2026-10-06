@extends('admin.layouts.app')

@section('title', 'Day Starts')
@section('breadcrumb', 'ADMIN / DAY START')
@section('page_title', 'Day Start / Tour Plan')

@section('content')
<div class="panel" x-data="{
    dateFrom: '{{ request('date_from') }}',
    dateTo: '{{ request('date_to') }}',
    employeeId: '{{ request('employee_id') }}',
    status: '{{ request('status') }}',
    loading: false,
    records: {{ Js::from($dayStarts->map(fn($ds) => [
        'id'                => $ds->id,
        'user_name'         => $ds->user?->name ?? 'Unknown',
        'date_formatted'    => $ds->date?->format('d M Y') ?? '—',
        'tour_purpose'      => $ds->tourPurpose?->name ?? '—',
        'opening_km'        => $ds->opening_km ?? '—',
        'closing_km'        => $ds->closing_km ?? '—',
        'status'            => $ds->status,
        'end_time'          => $ds->end_time,
        'show_url'          => route('admin.day-starts.show', $ds->id),
        'update_status_url' => route('admin.day-starts.updateStatus', $ds->id),
    ])) }},
    pagination: {
        current_page: {{ $dayStarts->currentPage() }},
        last_page: {{ $dayStarts->lastPage() }},
        per_page: {{ $dayStarts->perPage() }},
        total: {{ $dayStarts->total() }},
        from: {{ $dayStarts->firstItem() ?? 0 }},
        to: {{ $dayStarts->lastItem() ?? 0 }}
    },
    canApprove: {{ auth()->user()->can('day-start.approve') ? 'true' : 'false' }},

    async fetchRecords(page = 1) {
        this.loading = true;
        try {
            const params = new URLSearchParams();
            if (this.dateFrom) params.append('date_from', this.dateFrom);
            if (this.dateTo) params.append('date_to', this.dateTo);
            if (this.employeeId) params.append('employee_id', this.employeeId);
            if (this.status) params.append('status', this.status);
            params.append('page', page);

            const res = await fetch(`{{ route('admin.day-starts.index') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.success) {
                this.records = data.records;
                this.pagination = data.pagination;
                this.canApprove = data.can_approve;
            }
        } catch (e) {
            console.error('Failed to load day starts', e);
        } finally {
            this.loading = false;
        }
    },

    resetFilters() {
        this.dateFrom = '';
        this.dateTo = '';
        this.employeeId = '';
        this.status = '';
        this.fetchRecords(1);
    },

    async updateRecordStatus(record, newStatus) {
        if (!confirm(`Are you sure you want to ${newStatus} this tour plan?`)) return;
        try {
            const res = await fetch(record.update_status_url, {
                method: 'POST',
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
                record.status = newStatus;
                window.toast(data.message || `Day start record marked as ${newStatus} successfully.`, newStatus === 'approved' ? 'success' : 'warning');
            } else {
                window.toast(data.message || 'Failed to update status.', 'error');
            }
        } catch (e) {
            window.toast('Error updating status.', 'error');
        }
    }
}">
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">Day Start Records</h2>
                <p class="text-caption mt-1">View and filter submitted tour plans in real-time.</p>
            </div>
            @can('day-start.create')
                <x-button href="{{ route('admin.day-starts.create') }}" variant="primary">
                    <x-lucide-plus class="w-4 h-4" />
                    Start My Day
                </x-button>
            @endcan
        </div>

        {{-- AJAX Filters --}}
        <form @submit.prevent="fetchRecords(1)" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" x-model="dateFrom" @change="fetchRecords(1)" class="form-input">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" x-model="dateTo" @change="fetchRecords(1)" class="form-input">
            </div>
            @canany(['day-start.view-all', 'day-start.view-team'])
                <div>
                    <label class="form-label">Employee</label>
                    <select x-model="employeeId" @change="fetchRecords(1)" class="form-input">
                        <option value="">All employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee['id'] ?? $employee->id }}">
                                {{ $employee['name'] ?? $employee->name }} ({{ $employee->role_name ?? 'Field Worker' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endcanany
            <div>
                <label class="form-label">Status</label>
                <select x-model="status" @change="fetchRecords(1)" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" :disabled="loading" class="btn btn-primary flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-semibold">
                    <span x-show="!loading" class="flex items-center gap-1">
                        <x-lucide-filter class="w-4 h-4" />
                        Filter
                    </span>
                    <span x-show="loading" class="flex items-center gap-1">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Filtering...
                    </span>
                </button>
                <button type="button" @click="resetFilters" class="btn btn-secondary px-3 py-2 text-sm">Reset</button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap relative min-h-[250px]">
        {{-- Loading Overlay --}}
        <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center z-10" x-transition>
            <div class="flex items-center gap-2 text-indigo-600 font-semibold text-sm">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating records...
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Tour Purpose</th>
                    <th>Opening KM</th>
                    <th>Closing KM</th>
                    <th>Status</th>
                    <th class="text-right">View</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="item in records" :key="item.id">
                    <tr class="hover:bg-surface-muted/50 transition-colors">
                        <td class="text-body-emphasis" x-text="item.user_name"></td>
                        <td class="text-body" x-text="item.date_formatted"></td>
                        <td class="text-body" x-text="item.tour_purpose"></td>
                        <td class="text-body" x-text="item.opening_km"></td>
                        <td class="text-body" x-text="item.closing_km"></td>
                        <td>
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold w-fit"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': item.status === 'approved',
                                        'bg-rose-100 text-rose-800': item.status === 'rejected' || item.status === 'flagged',
                                        'bg-amber-100 text-amber-800': item.status === 'pending' || item.status === 'submitted',
                                        'bg-slate-100 text-slate-700': item.status !== 'approved' && item.status !== 'rejected' && item.status !== 'flagged' && item.status !== 'pending' && item.status !== 'submitted'
                                    }"
                                    x-text="item.status.charAt(0).toUpperCase() + item.status.slice(1)">
                                </span>
                                <template x-if="item.end_time">
                                    <span class="badge badge-neutral text-[10px] w-fit" x-text="'Ended: ' + item.end_time"></span>
                                </template>
                                <template x-if="canApprove && (item.status === 'pending' || item.status === 'submitted')">
                                    <div class="flex items-center gap-1 mt-1">
                                        <button type="button" @click="updateRecordStatus(item, 'approved')" class="px-2 py-0.5 text-[10px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded transition shadow-sm">
                                            ✓ Approve
                                        </button>
                                        <button type="button" @click="updateRecordStatus(item, 'rejected')" class="px-2 py-0.5 text-[10px] font-bold bg-rose-600 hover:bg-rose-700 text-white rounded transition shadow-sm">
                                            ✕ Reject
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </td>
                        <td class="text-right">
                            <a :href="item.show_url" class="btn btn-secondary text-xs px-2.5 py-1.5 inline-flex items-center gap-1">
                                <x-lucide-eye class="w-4 h-4" />
                                View
                            </a>
                        </td>
                    </tr>
                </template>

                <tr x-show="!loading && records.length === 0">
                    <td colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2 text-ink-400">
                            <x-lucide-inbox class="w-10 h-10" />
                            <p class="text-sm font-medium">No day start records found.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- AJAX Pagination --}}
    <div x-show="pagination.last_page > 1" class="border-t border-surface-border px-6 py-4 flex items-center justify-between">
        <div class="text-xs text-ink-500">
            Showing <span class="font-semibold text-ink-800" x-text="pagination.from"></span> to <span class="font-semibold text-ink-800" x-text="pagination.to"></span> of <span class="font-semibold text-ink-800" x-text="pagination.total"></span> records
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button"
                @click="fetchRecords(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1 || loading"
                class="btn btn-secondary text-xs px-2.5 py-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                Previous
            </button>
            <span class="text-xs text-ink-600 px-2 font-medium">
                Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
            </span>
            <button type="button"
                @click="fetchRecords(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page || loading"
                class="btn btn-secondary text-xs px-2.5 py-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>
    </div>
</div>
@endsection

