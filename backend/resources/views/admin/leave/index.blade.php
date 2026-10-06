@extends('admin.layouts.app')

@section('title', 'Leave Management')
@section('breadcrumb', 'ADMIN / LEAVE')
@section('page_title', 'Leave Management')

@section('content')

@php
$initialRecords = $leaves->map(function ($leave) {
    return [
        'id'                  => $leave->id,
        'user_id'             => $leave->user_id,
        'user_name'           => $leave->user?->name ?? 'Unknown',
        'leave_type_code'     => $leave->leaveType?->code ?? 'L',
        'leave_type_name'     => $leave->leaveType?->name ?? 'Leave',
        'from_date_formatted' => $leave->from_date ? $leave->from_date->format('d M Y') : '—',
        'to_date_formatted'   => $leave->to_date ? $leave->to_date->format('d M Y') : '—',
        'duration'            => $leave->duration,
        'duration_formatted'  => ucwords(str_replace('_', ' ', $leave->duration ?? '')),
        'days_count'          => (float) $leave->days_count,
        'reason'              => $leave->reason ?? '-',
        'status'              => $leave->status,
        'rejected_reason'     => $leave->rejected_reason,
        'approve_url'         => route('admin.leave.approve', $leave->id),
        'reject_url'          => route('admin.leave.reject', $leave->id),
        'cancel_url'          => route('admin.leave.cancel', $leave->id),
    ];
});

$initialConflicts = $conflicts->map(function ($c) {
    return [
        'id'                      => $c->id,
        'user_name'               => $c->user?->name ?? 'Unknown',
        'leave_type_code'         => $c->leaveType?->code ?? 'L',
        'leave_type_name'         => $c->leaveType?->name ?? 'Leave',
        'from_date_formatted'     => $c->from_date ? $c->from_date->format('d M Y') : '—',
        'to_date_formatted'       => $c->to_date ? $c->to_date->format('d M Y') : '—',
        'conflict_date_formatted' => $c->conflict_date ? \Carbon\Carbon::parse($c->conflict_date)->format('d M Y') : '—',
        'conflict_status'         => $c->conflict_status,
        'resolved_action'         => $c->resolved_action,
        'resolution_note'         => $c->resolution_note,
        'resolved_by_name'        => $c->resolvedBy?->name ?? 'Admin',
        'resolved_at_formatted'   => $c->resolved_at ? $c->resolved_at->format('d M Y H:i') : null,
        'resolve_url'             => route('admin.leave.resolve-conflict', $c->id),
    ];
});
@endphp

<div x-data="leaveManager({
    initialRecords: {{ Js::from($initialRecords) }},
    initialConflicts: {{ Js::from($initialConflicts) }},
    initialCounts: {
        pending: {{ (int) $pendingCount }},
        approved: {{ (int) $approvedCount }},
        rejected: {{ (int) $rejectedCount }},
        conflicts: {{ (int) $conflictCount }}
    },
    initialPagination: {
        current_page: {{ $leaves->currentPage() }},
        last_page: {{ $leaves->lastPage() }},
        per_page: {{ $leaves->perPage() }},
        total: {{ $leaves->total() }},
        from: {{ $leaves->firstItem() ?? 0 }},
        to: {{ $leaves->lastItem() ?? 0 }}
    },
    defaultView: '{{ request('view', 'applications') }}',
    defaultConflictTab: '{{ request('conflict_tab', 'pending') }}',
    fetchUrl: '{{ route('admin.leave.index') }}',
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Notification banner --}}
    <div x-show="bannerMessage" x-cloak class="flex items-center gap-3 rounded-xl border px-4 py-3 transition-all"
         :class="bannerType === 'success' ? 'border-green-200 bg-success-bg text-success-text' : 'border-red-200 bg-danger-bg text-danger-text'">
        <template x-if="bannerType === 'success'">
            <x-lucide-check-circle class="w-5 h-5 shrink-0" />
        </template>
        <template x-if="bannerType === 'error'">
            <x-lucide-alert-circle class="w-5 h-5 shrink-0" />
        </template>
        <p class="text-sm font-medium" x-text="bannerMessage"></p>
        <button type="button" @click="bannerMessage = ''" class="ml-auto opacity-70 hover:opacity-100">
            <x-lucide-x class="w-4 h-4" />
        </button>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="panel p-5 flex items-center justify-between cursor-pointer hover:border-amber-400 transition"
             @click="activeView = 'applications'; status = 'pending'; fetchRecords(1)">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Pending Requests</p>
                <h3 class="text-2xl font-extrabold text-amber-600 mt-1" x-text="counts.pending"></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 border border-amber-200">
                <x-lucide-clock class="w-6 h-6" />
            </div>
        </div>

        <div class="panel p-5 flex items-center justify-between cursor-pointer hover:border-emerald-400 transition"
             @click="activeView = 'applications'; status = 'approved'; fetchRecords(1)">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Approved Leaves</p>
                <h3 class="text-2xl font-extrabold text-emerald-600 mt-1" x-text="counts.approved"></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-200">
                <x-lucide-check-circle class="w-6 h-6" />
            </div>
        </div>

        <div class="panel p-5 flex items-center justify-between cursor-pointer hover:border-rose-400 transition"
             @click="activeView = 'applications'; status = 'rejected'; fetchRecords(1)">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Rejected Requests</p>
                <h3 class="text-2xl font-extrabold text-rose-600 mt-1" x-text="counts.rejected"></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-200">
                <x-lucide-x-circle class="w-6 h-6" />
            </div>
        </div>

        <div class="panel p-5 flex items-center justify-between cursor-pointer transition"
             :class="counts.conflicts > 0 ? 'border-2 border-rose-400 bg-rose-50/20' : 'hover:border-primary-400'"
             @click="switchView('conflicts')">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Work Conflicts</p>
                <div class="flex items-center gap-2 mt-1">
                    <h3 class="text-2xl font-extrabold" :class="counts.conflicts > 0 ? 'text-rose-600' : 'text-ink-700'" x-text="counts.conflicts"></h3>
                    <template x-if="counts.conflicts > 0">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 animate-pulse">Needs Review</span>
                    </template>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                 :class="counts.conflicts > 0 ? 'bg-rose-100 text-rose-600 border border-rose-300' : 'bg-surface-subtle text-ink-500 border border-surface-border'">
                <x-lucide-alert-triangle class="w-6 h-6" />
            </div>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="flex border-b border-surface-border">
        <button type="button"
                @click="switchView('applications')"
                class="px-5 py-3 font-semibold text-sm border-b-2 transition flex items-center gap-2"
                :class="activeView === 'applications' ? 'border-primary-600 text-primary-600' : 'border-transparent text-ink-500 hover:text-ink-800'">
            📋 Leave Applications
        </button>
        <button type="button"
                @click="switchView('conflicts')"
                class="px-5 py-3 font-semibold text-sm border-b-2 transition flex items-center gap-2"
                :class="activeView === 'conflicts' ? 'border-rose-600 text-rose-600' : 'border-transparent text-ink-500 hover:text-ink-800'">
            ⚠️ Work Conflicts
            <template x-if="counts.conflicts > 0">
                <span class="bg-rose-600 text-white text-xs px-2 py-0.5 rounded-full font-bold" x-text="counts.conflicts"></span>
            </template>
        </button>
    </div>

    {{-- Filter Panel --}}
    <div class="panel p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-base font-bold text-ink-900" x-text="activeView === 'applications' ? 'Filter Leave Requests' : 'Filter Conflicts'"></h2>
                <p class="text-xs text-ink-500">Live backend-driven search and filtering without page reloads</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="resetFilters()" class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5">
                    <x-lucide-rotate-ccw class="w-3.5 h-3.5" /> Reset Filters
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            {{-- Search input --}}
            <div>
                <label class="form-label text-xs">Search</label>
                <div class="relative">
                    <input type="text"
                           x-model="search"
                           @input.debounce.400ms="fetchRecords(1)"
                           placeholder="Employee, leave type, reason..."
                           class="form-input text-xs pl-8 w-full" />
                    <x-lucide-search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400" />
                </div>
            </div>

            {{-- Employee filter --}}
            <div>
                <label class="form-label text-xs">Employee</label>
                <select x-model="userId" @change="fetchRecords(1)" class="form-input text-xs w-full">
                    <option value="">All Employees</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role_name }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Status filter (Only in applications view) --}}
            <template x-if="activeView === 'applications'">
                <div>
                    <label class="form-label text-xs">Status</label>
                    <select x-model="status" @change="fetchRecords(1)" class="form-input text-xs w-full">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancellation_requested">Cancellation Requested</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </template>

            {{-- Month Filter --}}
            <div>
                <label class="form-label text-xs">Month</label>
                <input type="month" x-model="month" @change="fetchRecords(1)" class="form-input text-xs w-full" />
            </div>
        </div>
    </div>

    {{-- Applications View Content --}}
    <div x-show="activeView === 'applications'" x-cloak class="panel overflow-hidden">
        <div class="table-wrap relative">
            {{-- Loading spinner overlay --}}
            <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                <div class="flex items-center gap-2 text-primary-600 font-semibold text-sm">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading leaves...</span>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Date Range</th>
                        <th>Duration / Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="leave in records" :key="leave.id">
                        <tr>
                            <td class="font-semibold text-ink-900" x-text="leave.user_name"></td>
                            <td>
                                <span class="badge badge-primary font-mono font-bold" x-text="leave.leave_type_code + ' — ' + leave.leave_type_name"></span>
                            </td>
                            <td class="whitespace-nowrap text-sm text-ink-600">
                                <span x-text="leave.from_date_formatted"></span> → <span x-text="leave.to_date_formatted"></span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="font-medium text-ink-900 capitalize" x-text="leave.duration_formatted"></span>
                                <span class="text-ink-500 text-xs" x-text="'(' + leave.days_count + (leave.days_count == 1 ? ' day' : ' days') + ')'"></span>
                            </td>
                            <td class="max-w-xs text-sm text-ink-700">
                                <div x-text="leave.reason"></div>
                                <template x-if="leave.status === 'rejected' && leave.rejected_reason">
                                    <div class="text-xs text-rose-600 font-semibold mt-1" x-text="'Rejection: ' + leave.rejected_reason"></div>
                                </template>
                            </td>
                            <td>
                                <template x-if="leave.status === 'pending'">
                                    <span class="badge badge-warning">Pending</span>
                                </template>
                                <template x-if="leave.status === 'approved'">
                                    <span class="badge badge-success">Approved</span>
                                </template>
                                <template x-if="leave.status === 'cancellation_requested'">
                                    <span class="badge badge-warning" style="background-color: #f59e0b20; color: #d97706; border: 1px solid #f59e0b40; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">Cancel Requested</span>
                                </template>
                                <template x-if="leave.status === 'cancelled'">
                                    <span class="badge" style="background-color: #64748b20; color: #64748b; border: 1px solid #64748b40; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">Cancelled</span>
                                </template>
                                <template x-if="leave.status === 'rejected'">
                                    <span class="badge badge-danger">Rejected</span>
                                </template>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <template x-if="leave.status === 'pending'">
                                    <div class="flex gap-1.5 justify-end items-center">
                                        <button type="button"
                                                @click="approveLeave(leave)"
                                                :disabled="actionLoadingId === leave.id"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg inline-flex items-center gap-1 shadow-sm transition disabled:opacity-50 hover:brightness-110"
                                                style="background-color: #059669; color: #ffffff;">
                                            <x-lucide-check class="w-3.5 h-3.5 stroke-[2.5]" /> Approve
                                        </button>
                                        <button type="button"
                                                @click="openRejectModal(leave)"
                                                :disabled="actionLoadingId === leave.id"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg inline-flex items-center gap-1 shadow-sm transition disabled:opacity-50 hover:brightness-110"
                                                style="background-color: #e11d48; color: #ffffff;">
                                            <x-lucide-x class="w-3.5 h-3.5 stroke-[2.5]" /> Reject
                                        </button>
                                    </div>
                                </template>
                                <template x-if="leave.status === 'approved' || leave.status === 'cancellation_requested'">
                                    <div class="flex gap-1.5 justify-end items-center">
                                        <button type="button"
                                                @click="cancelLeave(leave)"
                                                :disabled="actionLoadingId === leave.id"
                                                class="px-2.5 py-1 text-xs font-bold rounded-lg inline-flex items-center gap-1 shadow-sm transition disabled:opacity-50 hover:brightness-110"
                                                style="background-color: #d97706; color: #ffffff;">
                                            <x-lucide-rotate-ccw class="w-3.5 h-3.5 stroke-[2.5]" /> Cancel & Refund
                                        </button>
                                    </div>
                                </template>
                                <template x-if="leave.status !== 'pending' && leave.status !== 'approved' && leave.status !== 'cancellation_requested'">
                                    <span class="text-xs text-ink-400 font-medium">Processed</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <template x-if="records.length === 0 && !loading">
                        <tr>
                            <td colspan="7" class="text-center py-8 text-ink-500">
                                No leave requests found matching the current filters.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- AJAX Pagination --}}
        <template x-if="pagination.total > pagination.per_page">
            <div class="p-4 border-t border-surface-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-600">
                <div>
                    Showing <span class="font-bold text-ink-900" x-text="pagination.from"></span> to
                    <span class="font-bold text-ink-900" x-text="pagination.to"></span> of
                    <span class="font-bold text-ink-900" x-text="pagination.total"></span> leave requests
                </div>
                <div class="flex items-center gap-1">
                    <button type="button"
                            @click="fetchRecords(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="px-2.5 py-1.5 border border-surface-border rounded-lg bg-white hover:bg-surface-subtle disabled:opacity-40 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <span class="px-3 py-1.5 font-bold text-ink-900" x-text="pagination.current_page + ' / ' + pagination.last_page"></span>
                    <button type="button"
                            @click="fetchRecords(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-2.5 py-1.5 border border-surface-border rounded-lg bg-white hover:bg-surface-subtle disabled:opacity-40 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Conflicts View Content --}}
    <div x-show="activeView === 'conflicts'" x-cloak class="panel p-6">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-5 pb-3 border-b border-surface-border">
            <div>
                <h2 class="text-base font-bold text-ink-900">Attendance Work Conflicts</h2>
                <p class="text-xs text-ink-500">Flagged when an employee performs Day Start on a date falling within an approved leave range.</p>
            </div>
            <div class="flex gap-2">
                <button type="button"
                        @click="conflictTab = 'pending'; fetchRecords(1)"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-xs"
                        :style="conflictTab === 'pending' ? 'background-color: #e11d48; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;'">
                    Pending Review (<span x-text="counts.conflicts"></span>)
                </button>
                <button type="button"
                        @click="conflictTab = 'resolved'; fetchRecords(1)"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-xs"
                        :style="conflictTab === 'resolved' ? 'background-color: #059669; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;'">
                    Resolved History
                </button>
            </div>
        </div>

        <div class="table-wrap relative">
            {{-- Loading spinner overlay --}}
            <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                <div class="flex items-center gap-2 text-primary-600 font-semibold text-sm">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading conflicts...</span>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Approved Leave Range</th>
                        <th>Conflict Date (DayStart)</th>
                        <th>Status</th>
                        <th>Resolution</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in conflicts" :key="c.id">
                        <tr>
                            <td class="font-semibold text-ink-900" x-text="c.user_name"></td>
                            <td>
                                <span class="badge badge-primary font-mono font-bold" x-text="c.leave_type_code"></span>
                            </td>
                            <td class="whitespace-nowrap text-sm text-ink-600">
                                <span x-text="c.from_date_formatted"></span> → <span x-text="c.to_date_formatted"></span>
                            </td>
                            <td class="whitespace-nowrap font-bold text-rose-600" x-text="'🗓️ ' + c.conflict_date_formatted"></td>
                            <td>
                                <template x-if="c.conflict_status === 'pending_review'">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        ⚠️ Pending Review
                                    </span>
                                </template>
                                <template x-if="c.conflict_status === 'resolved'">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        ✅ Resolved
                                    </span>
                                </template>
                            </td>
                            <td class="max-w-xs text-xs text-ink-700">
                                <template x-if="c.conflict_status === 'resolved'">
                                    <div>
                                        <div class="font-bold" :class="c.resolved_action === 'marked_present' ? 'text-emerald-700' : 'text-ink-800'"
                                             x-text="c.resolved_action === 'marked_present' ? 'Marked Present & Refunded' : 'Kept as Leave'"></div>
                                        <div class="text-ink-500 italic mt-0.5" x-text="'&ldquo;' + c.resolution_note + '&rdquo;'"></div>
                                        <template x-if="c.resolved_by_name && c.resolved_at_formatted">
                                            <div class="text-[10px] text-ink-400 mt-0.5" x-text="'By ' + c.resolved_by_name + ' on ' + c.resolved_at_formatted"></div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="c.conflict_status !== 'resolved'">
                                    <span class="text-ink-400 italic">Awaiting Admin Action</span>
                                </template>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <template x-if="c.conflict_status === 'pending_review'">
                                    <button type="button"
                                            class="px-3 py-1.5 font-bold text-xs rounded-lg shadow-xs transition hover:brightness-110"
                                            style="background-color: #e11d48; color: #ffffff;"
                                            @click="openResolveModal(c)">
                                        ⚖️ Resolve Conflict
                                    </button>
                                </template>
                                <template x-if="c.conflict_status === 'resolved'">
                                    <span class="text-xs text-ink-400">Completed</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                    <template x-if="conflicts.length === 0 && !loading">
                        <tr>
                            <td colspan="7" class="text-center py-8 text-ink-500">
                                No work conflicts found in this category.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rejection Modal --}}
    <div x-show="rejectModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="rejectModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
            <div class="flex items-center justify-between mb-4 border-b border-surface-border pb-3">
                <h3 class="text-base font-bold text-ink-900">Reject Leave Application</h3>
                <button type="button" @click="rejectModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>
            <p class="text-sm text-ink-600 mb-4">
                Please provide a reason for rejecting leave for <span class="font-bold text-ink-900" x-text="employeeName"></span>.
            </p>
            <form @submit.prevent="submitReject()">
                <div class="mb-4">
                    <label class="form-label text-xs font-bold">Rejection Reason <span class="text-rose-500">*</span></label>
                    <textarea x-model="rejectedReason" required rows="3" class="form-input text-xs" placeholder="e.g. High workload scheduled for these dates..."></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary text-xs px-3 py-1.5" @click="rejectModalOpen = false">Cancel</button>
                    <button type="submit" class="btn-danger text-xs px-3 py-1.5" :disabled="submittingReject">
                        <span x-text="submittingReject ? 'Rejecting...' : 'Confirm Rejection'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Conflict Resolution Modal --}}
    <div x-show="resolveModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="resolveModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-left border border-surface-border">
            <div class="flex items-center justify-between mb-4 border-b border-surface-border pb-3">
                <h3 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    ⚠️ Resolve Attendance Conflict
                </h3>
                <button type="button" @click="resolveModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 mb-4 text-xs text-rose-900 space-y-1">
                <p><strong>Employee:</strong> <span x-text="conflictEmp"></span></p>
                <p><strong>Conflict Date (DayStart):</strong> <span x-text="conflictDate"></span></p>
                <p><strong>Approved Leave Range:</strong> <span x-text="conflictLeaveDates"></span></p>
            </div>

            <form @submit.prevent="submitResolve()">
                <div class="mb-4">
                    <label class="form-label font-bold text-ink-900">Choose Resolution Action <span class="text-rose-500">*</span></label>
                    <div class="space-y-2 mt-2">
                        <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer hover:bg-surface-subtle transition"
                               :class="resolvedAction === 'marked_present' ? 'border-emerald-500 bg-emerald-50/30' : 'border-surface-border'">
                            <input type="radio" x-model="resolvedAction" value="marked_present" required class="mt-1 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="block text-xs font-bold text-emerald-700">Mark Present & Refund Leave Day</span>
                                <span class="block text-[11px] text-ink-500">Overrides calendar status to Present for this date and refunds 1 day back to employee leave balance.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer hover:bg-surface-subtle transition"
                               :class="resolvedAction === 'kept_as_leave' ? 'border-primary-500 bg-surface-subtle' : 'border-surface-border'">
                            <input type="radio" x-model="resolvedAction" value="kept_as_leave" required class="mt-1 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="block text-xs font-bold text-ink-800">Keep as Approved Leave</span>
                                <span class="block text-[11px] text-ink-500">No balance refund. Select if Day Start was accidental or unapproved.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label font-bold text-ink-900">Resolution Note / Reason <span class="text-rose-500">*</span></label>
                    <textarea x-model="resolutionNote" required rows="3" class="form-input text-xs" placeholder="Type explicit justification for this decision (e.g. Employee performed full field visit on 04 Sep, verified with manager)..."></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary text-xs px-3 py-1.5" @click="resolveModalOpen = false">Cancel</button>
                    <button type="submit" class="btn-primary text-xs px-3 py-1.5" :disabled="submittingResolve">
                        <span x-text="submittingResolve ? 'Applying...' : 'Confirm & Apply Resolution'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function leaveManager(config) {
    return {
        activeView: config.defaultView || 'applications',
        conflictTab: config.defaultConflictTab || 'pending',
        userId: '',
        status: '',
        month: '',
        search: '',
        records: config.initialRecords || [],
        conflicts: config.initialConflicts || [],
        counts: config.initialCounts || { pending: 0, approved: 0, rejected: 0, conflicts: 0 },
        pagination: config.initialPagination || { current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 },
        loading: false,
        actionLoadingId: null,
        bannerMessage: '',
        bannerType: 'success',

        // Reject modal state
        rejectModalOpen: false,
        activeLeaveId: null,
        employeeName: '',
        rejectedReason: '',
        submittingReject: false,

        // Resolve conflict modal state
        resolveModalOpen: false,
        conflictId: null,
        conflictEmp: '',
        conflictDate: '',
        conflictLeaveDates: '',
        resolvedAction: 'marked_present',
        resolutionNote: '',
        submittingResolve: false,

        showBanner(msg, type = 'success') {
            this.bannerMessage = msg;
            this.bannerType = type;
            if (window.toast) {
                window.toast(msg, type);
            }
            setTimeout(() => {
                if (this.bannerMessage === msg) this.bannerMessage = '';
            }, 6000);
        },

        switchView(view) {
            this.activeView = view;
            this.fetchRecords(1);
        },

        resetFilters() {
            this.userId = '';
            this.status = '';
            this.month = '';
            this.search = '';
            this.fetchRecords(1);
        },

        async fetchRecords(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.userId) params.append('user_id', this.userId);
                if (this.status && this.activeView === 'applications') params.append('status', this.status);
                if (this.month) params.append('month', this.month);
                if (this.search) params.append('search', this.search);
                if (this.activeView === 'conflicts') params.append('conflict_tab', this.conflictTab);
                params.append('page', page);

                const response = await fetch(`${config.fetchUrl}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.records = data.records || [];
                    this.conflicts = data.conflicts || [];
                    this.counts = data.counts || this.counts;
                    this.pagination = data.pagination || this.pagination;
                }
            } catch (err) {
                console.error('Error fetching leaves:', err);
                this.showBanner('Failed to load leave records. Please refresh.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async approveLeave(leave) {
            if (!confirm(`Approve leave for ${leave.user_name}?`)) return;

            this.actionLoadingId = leave.id;
            try {
                const response = await fetch(leave.approve_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });

                const res = await response.json();
                if (response.ok && res.success) {
                    this.showBanner(res.message || 'Leave approved successfully.', 'success');
                    await this.fetchRecords(this.pagination.current_page);
                } else {
                    this.showBanner(res.message || 'Failed to approve leave.', 'error');
                }
            } catch (err) {
                console.error('Approve error:', err);
                this.showBanner('An unexpected error occurred.', 'error');
            } finally {
                this.actionLoadingId = null;
            }
        },

        openRejectModal(leave) {
            this.activeLeaveId = leave.id;
            this.employeeName = leave.user_name;
            this.rejectedReason = '';
            this.rejectModalOpen = true;
        },

        async submitReject() {
            if (!this.rejectedReason.trim()) return;

            this.submittingReject = true;
            try {
                const response = await fetch(`/admin/leave/${this.activeLeaveId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        rejected_reason: this.rejectedReason
                    })
                });

                const res = await response.json();
                if (response.ok && res.success) {
                    this.rejectModalOpen = false;
                    this.showBanner(res.message || 'Leave rejected successfully.', 'success');
                    await this.fetchRecords(this.pagination.current_page);
                } else {
                    this.showBanner(res.message || 'Failed to reject leave.', 'error');
                }
            } catch (err) {
                console.error('Reject error:', err);
                this.showBanner('An unexpected error occurred.', 'error');
            } finally {
                this.submittingReject = false;
            }
        },

        async cancelLeave(leave) {
            if (!confirm(`Cancel approved leave for ${leave.user_name}? This will refund ${leave.days_count} day(s) back to employee balance.`)) return;

            this.actionLoadingId = leave.id;
            try {
                const response = await fetch(leave.cancel_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });

                const res = await response.json();
                if (response.ok && res.success) {
                    this.showBanner(res.message || 'Leave cancelled successfully.', 'success');
                    await this.fetchRecords(this.pagination.current_page);
                } else {
                    this.showBanner(res.message || 'Failed to cancel leave.', 'error');
                }
            } catch (err) {
                console.error('Cancel error:', err);
                this.showBanner('An unexpected error occurred.', 'error');
            } finally {
                this.actionLoadingId = null;
            }
        },

        openResolveModal(c) {
            this.conflictId = c.id;
            this.conflictEmp = c.user_name;
            this.conflictDate = c.conflict_date_formatted;
            this.conflictLeaveDates = `${c.from_date_formatted} → ${c.to_date_formatted}`;
            this.resolvedAction = 'marked_present';
            this.resolutionNote = '';
            this.resolveModalOpen = true;
        },

        async submitResolve() {
            if (!this.resolutionNote.trim()) return;

            this.submittingResolve = true;
            try {
                const response = await fetch(`/admin/leave/${this.conflictId}/resolve-conflict`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        resolved_action: this.resolvedAction,
                        resolution_note: this.resolutionNote
                    })
                });

                const res = await response.json();
                if (response.ok && res.success) {
                    this.resolveModalOpen = false;
                    this.showBanner(res.message || 'Attendance conflict resolved successfully.', 'success');
                    await this.fetchRecords(1);
                } else {
                    this.showBanner(res.message || 'Failed to resolve conflict.', 'error');
                }
            } catch (err) {
                console.error('Resolve error:', err);
                this.showBanner('An unexpected error occurred.', 'error');
            } finally {
                this.submittingResolve = false;
            }
        }
    };
}
</script>
@endpush
@endsection
