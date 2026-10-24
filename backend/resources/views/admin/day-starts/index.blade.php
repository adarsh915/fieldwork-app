@extends('admin.layouts.app')

@section('title', 'Day Starts')
@section('breadcrumb', 'ADMIN / DAY START')
@section('page_title', 'Day Start / Tour Plan')

@section('content')

    @if(session('status'))
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
            <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
            <p class="text-sm font-medium text-success-text">{{ session('status') }}</p>
        </div>
    @endif

    <div class="panel">
        {{-- Header --}}
        <div class="panel-header">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-section-title">Day Start Records</h2>
                    <p class="text-caption mt-1">View, search, filter, and review submitted tour plans in real-time.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @can('day-start.create')
                        <x-button href="{{ route('admin.day-starts.create') }}" variant="primary">
                            <x-lucide-plus class="w-4 h-4" />
                            Start My Day
                        </x-button>
                    @endcan
                </div>
            </div>

            {{-- Filters --}}
            <div id="day-start-filters" x-data="{
                search: '',
                dateFrom: '',
                dateTo: '',
                employeeId: '',
                status: '',
                filtersVisible: false
            }" class="mt-4">
                <div class="flex flex-wrap gap-3 items-end">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[200px]">
                        <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                        <input x-model="search" @input.debounce.400ms="window.dayStartTable.search(search).draw()"
                            class="form-input pl-9" placeholder="Search employee, purpose, location, vehicle...">
                    </div>

                    {{-- Toggle Filters Button --}}
                    <x-button type="button" variant="secondary" @click="filtersVisible = !filtersVisible">
                        <x-lucide-filter class="w-4 h-4" />
                        <span x-text="filtersVisible ? 'Hide Filters' : 'Show Filters'"></span>
                    </x-button>

                    {{-- Clear All --}}
                    <x-button type="button" variant="secondary"
                        @click="search=''; dateFrom=''; dateTo=''; employeeId=''; status=''; window.dayStartTable.search('').draw()"
                        x-show="search || dateFrom || dateTo || employeeId || status" style="display: none;">
                        <x-lucide-x class="w-4 h-4" />
                        Clear
                    </x-button>
                </div>

                {{-- Advanced Filters --}}
                <div x-show="filtersVisible" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3" style="display: none;">
                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Date From</label>
                        <input type="date" x-model="dateFrom" @change="window.dayStartTable.draw()" class="form-input">
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Date To</label>
                        <input type="date" x-model="dateTo" @change="window.dayStartTable.draw()" class="form-input">
                    </div>

                    @canany(['day-start.view-all', 'day-start.view-team'])
                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Employee</label>
                        <select x-model="employeeId" @change="window.dayStartTable.draw()" class="form-input">
                            <option value="">All Employees</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->role_name ?? ($emp->roles->first()?->name ?? 'Field Worker') }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endcanany

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Status</label>
                        <select x-model="status" @change="window.dayStartTable.draw()" class="form-input">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="table-wrap min-h-[350px]">
            <table id="day-starts-table" class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Tour Purpose</th>
                        <th>Opening KM</th>
                        <th>Closing KM</th>
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
            // Initialize DataTable
            window.dayStartTable = new DataTable('#day-starts-table', {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.day-starts.data') }}',
                    data: function (d) {
                        const filtersElement = document.getElementById('day-start-filters');
                        const filters = window.Alpine ? Alpine.$data(filtersElement) : null;
                        if (filters) {
                            d.search = filters.search || '';
                            d.date_from = filters.dateFrom || '';
                            d.date_to = filters.dateTo || '';
                            d.employee_id = filters.employeeId || '';
                            d.status = filters.status || '';
                        }
                    }
                },
                columns: [
                    {
                        data: 'employee',
                        render: function (data) {
                            return `
                                <div class="text-body-emphasis font-medium">${data.name}</div>
                                <div class="text-caption">${data.role} ${data.employee_id && data.employee_id !== '—' ? '• ' + data.employee_id : ''}</div>
                            `;
                        }
                    },
                    {
                        data: 'date',
                        render: function (data) {
                            return `<div class="text-body">${data.formatted}</div>`;
                        }
                    },
                    {
                        data: 'tour_purpose',
                        render: function (data) {
                            let sub = [];
                            if (data.tour_type) sub.push(data.tour_type);
                            if (data.vehicle) sub.push(data.vehicle);
                            if (data.visit_place) sub.push(data.visit_place);

                            let locBadge = '';
                            if (data.district || data.area) {
                                let locText = [data.district, data.area].filter(Boolean).join(' • ');
                                locBadge = `<div class="mt-1"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">📍 ${locText}</span></div>`;
                            }

                            return `
                                <div class="text-body font-medium">${data.purpose}</div>
                                ${sub.length ? `<div class="text-caption">${sub.join(' • ')}</div>` : ''}
                                ${locBadge}
                            `;
                        }
                    },
                    {
                        data: 'opening_km',
                        render: function (data) {
                            return `<div class="text-body font-mono text-xs">${data}</div>`;
                        }
                    },
                    {
                        data: 'closing_km',
                        render: function (data) {
                            return `<div class="text-body font-mono text-xs">${data}</div>`;
                        }
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            let badgeClass = 'bg-slate-100 text-slate-700';
                            let label = (data.status || 'pending').charAt(0).toUpperCase() + (data.status || 'pending').slice(1);
                            
                            if (data.status === 'approved') {
                                badgeClass = 'bg-emerald-100 text-emerald-800';
                            } else if (data.status === 'pending' || data.status === 'submitted') {
                                badgeClass = 'bg-amber-100 text-amber-800';
                                label = 'Pending';
                            } else if (data.status === 'rejected' || data.status === 'flagged') {
                                badgeClass = 'bg-rose-100 text-rose-800';
                            }

                            let html = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${badgeClass}">${label}</span>`;
                            if (data.end_time) {
                                html += `<span class="badge badge-neutral text-[10px] mt-1 block w-fit">Ended: ${data.end_time}</span>`;
                            }
                            return html;
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
                                         class="absolute right-0 mt-1 min-w-[210px] bg-white border border-surface-border rounded-xl shadow-xl z-50 py-1.5 top-full" 
                                         style="min-width: 210px; width: max-content; display: none;">
                                        <a href="${data.show_url}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink-900 hover:bg-surface-muted whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-ink-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>View Details</span>
                                        </a>
                                        ${data.can_action ? `
                                        <div class="my-1.5 border-t border-surface-border"></div>
                                        <button type="button" onclick="updateTourPlanStatus('${data.update_status_url}', 'approved')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-700 hover:bg-emerald-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Approve Tour Plan</span>
                                        </button>
                                        <button type="button" onclick="updateTourPlanStatus('${data.update_status_url}', 'rejected')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-700 hover:bg-rose-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Reject Tour Plan</span>
                                        </button>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[1, 'desc']],
                pageLength: 20,
                lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
                dom: 'rt<"flex items-center justify-between px-6 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>',
                drawCallback: function () {
                    if (window.Alpine) {
                        window.Alpine.initTree(document.getElementById('day-starts-table'));
                    }
                }
            });

            // Status Update Handler
            window.updateTourPlanStatus = async function (url, newStatus) {
                if (!confirm(`Are you sure you want to ${newStatus} this tour plan?`)) return;
                try {
                    const res = await fetch(url, {
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
                        window.toast(data.message || `Tour plan marked as ${newStatus} successfully.`, newStatus === 'approved' ? 'success' : 'warning');
                        if (window.dayStartTable) {
                            window.dayStartTable.ajax.reload(null, false);
                        }
                    } else {
                        window.toast(data.message || 'Failed to update status.', 'error');
                    }
                } catch (e) {
                    window.toast('Error updating status.', 'error');
                }
            };
        });
    </script>
@endpush
