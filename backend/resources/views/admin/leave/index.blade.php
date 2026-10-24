@extends('admin.layouts.app')

@section('title', 'Leave Management')
@section('breadcrumb', 'ADMIN / LEAVE')
@section('page_title', 'Leave Management')

@section('content')

<div id="leave-app-container" x-data="leaveManager({
    initialCounts: {
        pending: {{ (int) $pendingCount }},
        approved: {{ (int) $approvedCount }},
        rejected: {{ (int) $rejectedCount }},
        conflicts: {{ (int) $conflictCount }}
    },
    canApprove: {{ Js::from(auth()->user()->can('leaves.approve') || auth()->user()->can('leaves.manage') || auth()->user()->hasRole('Super Admin')) }},
    canReject: {{ Js::from(auth()->user()->can('leaves.reject') || auth()->user()->can('leaves.manage') || auth()->user()->hasRole('Super Admin')) }},
    canCancel: {{ Js::from(auth()->user()->can('leaves.manage') || auth()->user()->can('leaves.approve') || auth()->user()->hasRole('Super Admin')) }},
    canResolveConflict: {{ Js::from(auth()->user()->can('leaves.manage') || auth()->user()->can('leaves.approve') || auth()->user()->hasRole('Super Admin')) }},
    defaultView: '{{ request('view', 'applications') }}',
    defaultConflictTab: '{{ request('conflict_tab', 'pending') }}',
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
        {{-- Pending Requests --}}
        <div class="panel p-5 flex items-center justify-between cursor-pointer transition-all border"
             :class="activeView === 'applications' && status === 'pending' ? 'border-amber-500 ring-2 ring-amber-100 bg-amber-50/20' : 'hover:border-amber-400'"
             @click="filterByStatus('pending')">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Pending Requests</p>
                <h3 class="text-2xl font-extrabold text-amber-600 mt-1" x-text="counts.pending"></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 border border-amber-200">
                <x-lucide-clock class="w-6 h-6" />
            </div>
        </div>

        {{-- Approved Leaves --}}
        <div class="panel p-5 flex items-center justify-between cursor-pointer transition-all border"
             :class="activeView === 'applications' && status === 'approved' ? 'border-emerald-500 ring-2 ring-emerald-100 bg-emerald-50/20' : 'hover:border-emerald-400'"
             @click="filterByStatus('approved')">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Approved Leaves</p>
                <h3 class="text-2xl font-extrabold text-emerald-600 mt-1" x-text="counts.approved"></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-200">
                <x-lucide-check-circle class="w-6 h-6" />
            </div>
        </div>

        {{-- Rejected Requests --}}
        <div class="panel p-5 flex items-center justify-between cursor-pointer transition-all border"
             :class="activeView === 'applications' && status === 'rejected' ? 'border-rose-500 ring-2 ring-rose-100 bg-rose-50/20' : 'hover:border-rose-400'"
             @click="filterByStatus('rejected')">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Rejected Requests</p>
                <h3 class="text-2xl font-extrabold text-rose-600 mt-1" x-text="counts.rejected"></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-200">
                <x-lucide-x-circle class="w-6 h-6" />
            </div>
        </div>

        {{-- Work Conflicts --}}
        <div class="panel p-5 flex items-center justify-between cursor-pointer transition-all border"
             :class="activeView === 'conflicts' ? 'border-rose-500 ring-2 ring-rose-100 bg-rose-50/20' : (counts.conflicts > 0 ? 'border-2 border-rose-400 bg-rose-50/10' : 'hover:border-primary-400')"
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
                :class="activeView === 'applications' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-ink-500 hover:text-ink-800'">
            <x-lucide-calendar class="w-4 h-4" />
            <span>Leave Applications</span>
        </button>
        <button type="button"
                @click="switchView('conflicts')"
                class="px-5 py-3 font-semibold text-sm border-b-2 transition flex items-center gap-2"
                :class="activeView === 'conflicts' ? 'border-rose-600 text-rose-600 font-bold' : 'border-transparent text-ink-500 hover:text-ink-800'">
            <x-lucide-alert-triangle class="w-4 h-4" />
            <span>Work Conflicts</span>
            <template x-if="counts.conflicts > 0">
                <span class="bg-rose-600 text-white text-xs px-2 py-0.5 rounded-full font-bold" x-text="counts.conflicts"></span>
            </template>
        </button>
    </div>

    {{-- Filter Panel --}}
    <div class="panel p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-base font-bold text-ink-900" x-text="activeView === 'applications' ? 'Filter Leave Requests' : 'Filter Work Conflicts'"></h2>
                <p class="text-xs text-ink-500">Live search and instant filtering with server-side pagination</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="resetFilters()" class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5">
                    <x-lucide-rotate-ccw class="w-3.5 h-3.5" />
                    <span>Reset Filters</span>
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
                           @input.debounce.300ms="applyFilters()"
                           placeholder="Employee, leave type, reason..."
                           class="form-input text-xs pl-8 w-full" />
                    <x-lucide-search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none" />
                </div>
            </div>

            {{-- Employee filter --}}
            <div>
                <label class="form-label text-xs">Employee</label>
                <select x-model="userId" @change="applyFilters()" class="form-input text-xs w-full">
                    <option value="">All Employees</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role_name ?? 'Field Worker' }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Status filter (Only in applications view) --}}
            <template x-if="activeView === 'applications'">
                <div>
                    <label class="form-label text-xs">Status</label>
                    <select x-model="status" @change="applyFilters()" class="form-input text-xs w-full">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancellation_requested">Cancellation Requested</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </template>

            {{-- Conflict Review Tab (Only in conflicts view) --}}
            <template x-if="activeView === 'conflicts'">
                <div>
                    <label class="form-label text-xs">Conflict Status</label>
                    <select x-model="conflictTab" @change="applyConflictsFilter()" class="form-input text-xs w-full">
                        <option value="pending">Pending Review Only</option>
                        <option value="resolved">Resolved History</option>
                    </select>
                </div>
            </template>

            {{-- Month Filter --}}
            <div>
                <label class="form-label text-xs">Month</label>
                <input type="month" x-model="month" @change="applyFilters()" class="form-input text-xs w-full" />
            </div>
        </div>
    </div>

    {{-- Applications View Content (DataTables) --}}
    <div x-show="activeView === 'applications'" class="panel overflow-hidden border border-surface-border shadow-sm rounded-xl">
        <div class="table-wrap min-h-[350px]">
            <table id="leave-table" class="data-table w-full">
                <thead>
                    <tr>
                        <th class="text-left font-semibold">Employee</th>
                        <th class="text-left font-semibold">Type</th>
                        <th class="text-left font-semibold">Date Range</th>
                        <th class="text-left font-semibold">Duration / Days</th>
                        <th class="text-left font-semibold">Reason</th>
                        <th class="text-left font-semibold">Status</th>
                        <th class="text-right font-semibold whitespace-nowrap w-44 px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Populated dynamically by DataTables serverSide --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- Conflicts View Content (DataTables) --}}
    <div x-show="activeView === 'conflicts'" x-cloak class="panel overflow-hidden border border-surface-border shadow-sm rounded-xl p-6">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-5 pb-3 border-b border-surface-border">
            <div>
                <h2 class="text-base font-bold text-ink-900">Attendance Work Conflicts</h2>
                <p class="text-xs text-ink-500">Flagged when an employee performs Day Start on a date falling within an approved leave range.</p>
            </div>
            <div class="flex gap-2">
                <button type="button"
                        @click="conflictTab = 'pending'; applyConflictsFilter()"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs inline-flex items-center gap-1.5"
                        :class="conflictTab === 'pending' ? 'bg-rose-600 text-white' : 'bg-surface-muted text-ink-700 hover:bg-slate-200'">
                    <x-lucide-alert-circle class="w-3.5 h-3.5" />
                    <span>Pending Review (<span x-text="counts.conflicts"></span>)</span>
                </button>
                <button type="button"
                        @click="conflictTab = 'resolved'; applyConflictsFilter()"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs inline-flex items-center gap-1.5"
                        :class="conflictTab === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-surface-muted text-ink-700 hover:bg-slate-200'">
                    <x-lucide-check-circle class="w-3.5 h-3.5" />
                    <span>Resolved History</span>
                </button>
            </div>
        </div>

        <div class="table-wrap min-h-[350px]">
            <table id="conflicts-table" class="data-table w-full">
                <thead>
                    <tr>
                        <th class="text-left font-semibold">Employee</th>
                        <th class="text-left font-semibold">Leave Type</th>
                        <th class="text-left font-semibold">Approved Leave Range</th>
                        <th class="text-left font-semibold">Conflict Date (DayStart)</th>
                        <th class="text-left font-semibold">Status</th>
                        <th class="text-left font-semibold">Resolution</th>
                        <th class="text-right font-semibold whitespace-nowrap w-44 px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Populated dynamically by DataTables serverSide --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rejection Modal --}}
    <div x-show="rejectModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
         x-transition.opacity>
        <div @click.outside="rejectModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border" x-transition.scale>
            <div class="flex items-center justify-between mb-4 border-b border-surface-border pb-3">
                <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-x-circle class="w-5 h-5 text-rose-600" />
                    <span>Reject Leave Application</span>
                </h3>
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
                    <textarea x-model="rejectedReason" required rows="3" class="form-input text-xs" placeholder="e.g. Critical deployment scheduled during this period..."></textarea>
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
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
         x-transition.opacity>
        <div @click.outside="resolveModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-left border border-surface-border" x-transition.scale>
            <div class="flex items-center justify-between mb-4 border-b border-surface-border pb-3">
                <h3 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-alert-triangle class="w-5 h-5 text-rose-600" />
                    <span>Resolve Attendance Conflict</span>
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
                               :class="resolvedAction === 'marked_present' ? 'border-emerald-500 bg-emerald-50/30 ring-1 ring-emerald-400' : 'border-surface-border'">
                            <input type="radio" x-model="resolvedAction" value="marked_present" required class="mt-1 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="block text-xs font-bold text-emerald-700">Mark Present & Refund Leave Day</span>
                                <span class="block text-[11px] text-ink-500">Overrides calendar status to Present for this date and refunds 1 day back to employee leave balance.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer hover:bg-surface-subtle transition"
                               :class="resolvedAction === 'kept_as_leave' ? 'border-brand-500 bg-surface-subtle ring-1 ring-brand-400' : 'border-surface-border'">
                            <input type="radio" x-model="resolvedAction" value="kept_as_leave" required class="mt-1 text-brand-600 focus:ring-brand-500">
                            <div>
                                <span class="block text-xs font-bold text-ink-800">Keep as Approved Leave</span>
                                <span class="block text-[11px] text-ink-500">No balance refund. Select if Day Start was accidental or unapproved.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label font-bold text-ink-900">Resolution Note / Reason <span class="text-rose-500">*</span></label>
                    <textarea x-model="resolutionNote" required rows="3" class="form-input text-xs" placeholder="Type explicit justification for this decision (e.g. Employee performed field visit on this date, verified with manager)..."></textarea>
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
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function leaveManager(config) {
    return {
        activeView: config.defaultView || 'applications',
        conflictTab: config.defaultConflictTab || 'pending',
        userId: '',
        status: '',
        month: '',
        search: '',
        counts: config.initialCounts || { pending: 0, approved: 0, rejected: 0, conflicts: 0 },
        canApprove: config.canApprove ?? false,
        canReject: config.canReject ?? false,
        canCancel: config.canCancel ?? false,
        canResolveConflict: config.canResolveConflict ?? false,
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

        init() {
            window.leaveApp = this;
            this.$nextTick(() => {
                this.initDataTables();
            });
        },

        initDataTables() {
            const self = this;
            const DT = window.DataTable || (typeof DataTable !== 'undefined' ? DataTable : null);
            if (!DT) {
                setTimeout(() => self.initDataTables(), 100);
                return;
            }

            // 1. Leave Applications DataTable
            if (!window.leaveTable) {
                window.leaveTable = new DT('#leave-table', {
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('admin.leave.data') }}',
                        data: function (d) {
                            d.user_id = self.userId || '';
                            d.status = self.status || '';
                            d.month = self.month || '';
                            d.search_custom = self.search || '';
                        },
                        dataSrc: function (json) {
                            if (json.counts) {
                                self.counts = json.counts;
                            }
                            return json.data;
                        }
                    },
                    pageLength: 15,
                    lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
                    order: [[2, 'desc']],
                    autoWidth: false,
                    dom: '<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-b border-surface-border bg-surface-muted/20"<"text-xs text-ink-600 flex items-center gap-2"l><"text-xs text-ink-500 font-medium"i>>rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-t border-surface-border bg-white"<"text-caption text-ink-500 font-medium"i><"text-xs"p>>',
                    language: {
                        emptyTable: 'No leave requests found matching the current filters.',
                        zeroRecords: 'No matching leave requests found.',
                        info: 'Showing _START_ to _END_ of _TOTAL_ leave requests',
                        infoEmpty: 'Showing 0 to 0 of 0 leave requests',
                        infoFiltered: '(filtered from _MAX_ total)',
                        lengthMenu: 'Show _MENU_ entries',
                        paginate: {
                            first: '«',
                            previous: '‹',
                            next: '›',
                            last: '»'
                        }
                    },
                    columns: [
                        {
                            data: 'employee',
                            render: function (data) {
                                return `
                                    <div class="font-bold text-ink-900">${data.name}</div>
                                    <div class="text-xs text-ink-500 font-medium">${data.role} ${data.employee_id && data.employee_id !== '—' ? '• ' + data.employee_id : ''}</div>
                                `;
                            }
                        },
                        {
                            data: 'leave_type',
                            render: function (data) {
                                return `<span class="badge badge-info text-xs font-semibold font-mono">${data.code} — ${data.name}</span>`;
                            }
                        },
                        {
                            data: 'date_range',
                            render: function (data) {
                                return `<span class="whitespace-nowrap text-xs font-medium text-ink-800">${data.from} <span class="text-ink-400">→</span> ${data.to}</span>`;
                            }
                        },
                        {
                            data: 'duration',
                            render: function (data) {
                                const daysText = data.days_count == 1 ? '1 day' : `${data.days_count} days`;
                                return `
                                    <div class="whitespace-nowrap">
                                        <span class="font-semibold text-ink-900 capitalize text-xs">${data.formatted}</span>
                                        <span class="text-ink-500 text-xs font-normal">(${daysText})</span>
                                    </div>
                                `;
                            }
                        },
                        {
                            data: 'reason',
                            render: function (data) {
                                let html = `<div class="max-w-xs text-xs text-ink-700" title="${escapeHtml(data.reason)}">${escapeHtml(data.reason)}</div>`;
                                if (data.rejected_reason) {
                                    html += `<div class="text-[11px] text-rose-600 font-medium mt-1">Rejection: ${escapeHtml(data.rejected_reason)}</div>`;
                                }
                                return html;
                            }
                        },
                        {
                            data: 'status',
                            render: function (status) {
                                switch (status) {
                                    case 'pending':
                                        return `<span class="badge badge-warning text-xs font-medium">Pending</span>`;
                                    case 'approved':
                                        return `<span class="badge badge-success text-xs font-medium">Approved</span>`;
                                    case 'cancellation_requested':
                                        return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Cancel Requested</span>`;
                                    case 'cancelled':
                                        return `<span class="badge badge-neutral text-xs font-medium">Cancelled</span>`;
                                    case 'rejected':
                                        return `<span class="badge badge-danger text-xs font-medium">Rejected</span>`;
                                    default:
                                        return `<span class="badge badge-neutral text-xs font-medium">${status}</span>`;
                                }
                            }
                        },
                        {
                            data: 'actions',
                            orderable: false,
                            searchable: false,
                            className: 'text-right whitespace-nowrap align-middle w-44 px-5 py-3.5',
                            render: function (actions) {
                                if (actions.status === 'pending') {
                                    let buttons = [];
                                    if (actions.can_approve) {
                                        buttons.push(`
                                            <button type="button" 
                                                    onclick="window.leaveApp.approveLeave(${actions.id}, '${escapeHtml(actions.user_name)}')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                                    title="Approve Leave">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span>Approve</span>
                                            </button>
                                        `);
                                    }
                                    if (actions.can_reject) {
                                        buttons.push(`
                                            <button type="button" 
                                                    onclick="window.leaveApp.openRejectModal(${actions.id}, '${escapeHtml(actions.user_name)}')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                                    title="Reject Leave">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Reject</span>
                                            </button>
                                        `);
                                    }
                                    return `<div class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">${buttons.join('')}</div>`;
                                } else if (actions.status === 'approved' || actions.status === 'cancellation_requested') {
                                    if (actions.can_cancel) {
                                        return `
                                            <div class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                                                <button type="button" 
                                                        onclick="window.leaveApp.cancelLeave(${actions.id}, '${escapeHtml(actions.user_name)}', ${actions.days_count})"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                                        title="Cancel & Refund Leave">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    <span>Cancel & Refund</span>
                                                </button>
                                            </div>
                                        `;
                                    }
                                    return `<span class="text-xs text-ink-400 font-medium">Approved</span>`;
                                } else {
                                    return `<span class="text-xs text-ink-400 font-medium">Processed</span>`;
                                }
                            }
                        }
                    ]
                });
            }

            // 2. Work Conflicts DataTable
            if (!window.conflictsTable) {
                window.conflictsTable = new DT('#conflicts-table', {
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('admin.leave.conflicts-data') }}',
                        data: function (d) {
                            d.user_id = self.userId || '';
                            d.month = self.month || '';
                            d.conflict_tab = self.conflictTab || 'pending';
                            d.search_custom = self.search || '';
                        }
                    },
                    pageLength: 15,
                    lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
                    order: [[3, 'desc']],
                    autoWidth: false,
                    dom: '<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-b border-surface-border bg-surface-muted/20"<"text-xs text-ink-600 flex items-center gap-2"l><"text-xs text-ink-500 font-medium"i>>rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-t border-surface-border bg-white"<"text-caption text-ink-500 font-medium"i><"text-xs"p>>',
                    language: {
                        emptyTable: 'No work conflicts found in this category.',
                        zeroRecords: 'No matching work conflicts found.',
                        info: 'Showing _START_ to _END_ of _TOTAL_ conflicts',
                        infoEmpty: 'Showing 0 to 0 of 0 conflicts',
                        infoFiltered: '(filtered from _MAX_ total)',
                        lengthMenu: 'Show _MENU_ entries',
                        paginate: {
                            first: '«',
                            previous: '‹',
                            next: '›',
                            last: '»'
                        }
                    },
                    columns: [
                        {
                            data: 'employee',
                            render: function (data) {
                                return `
                                    <div class="font-bold text-ink-900">${data.name}</div>
                                    <div class="text-xs text-ink-500 font-medium">${data.role} ${data.employee_id && data.employee_id !== '—' ? '• ' + data.employee_id : ''}</div>
                                `;
                            }
                        },
                        {
                            data: 'leave_type',
                            render: function (data) {
                                return `<span class="badge badge-info text-xs font-semibold font-mono">${data.code} — ${data.name}</span>`;
                            }
                        },
                        {
                            data: 'leave_range',
                            render: function (data) {
                                return `<span class="whitespace-nowrap text-xs font-medium text-ink-800">${data.from} <span class="text-ink-400">→</span> ${data.to}</span>`;
                            }
                        },
                        {
                            data: 'conflict_date',
                            render: function (data) {
                                return `<span class="whitespace-nowrap font-bold text-rose-600 text-xs">🗓️ ${data.formatted}</span>`;
                            }
                        },
                        {
                            data: 'status',
                            render: function (status) {
                                if (status === 'pending_review') {
                                    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">⚠️ Pending Review</span>`;
                                }
                                return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">✅ Resolved</span>`;
                            }
                        },
                        {
                            data: 'resolution',
                            render: function (data) {
                                if (data.action) {
                                    const actionText = data.action === 'marked_present' ? 'Marked Present & Refunded' : 'Kept as Leave';
                                    const actionColor = data.action === 'marked_present' ? 'text-emerald-700' : 'text-ink-800';
                                    let html = `<div class="font-bold text-xs ${actionColor}">${actionText}</div>`;
                                    if (data.note) {
                                        html += `<div class="text-ink-500 italic text-[11px] mt-0.5">&ldquo;${escapeHtml(data.note)}&rdquo;</div>`;
                                    }
                                    if (data.by_name && data.at_formatted) {
                                        html += `<div class="text-[10px] text-ink-400 mt-0.5">By ${escapeHtml(data.by_name)} on ${data.at_formatted}</div>`;
                                    }
                                    return html;
                                }
                                return `<span class="text-ink-400 italic text-xs">Awaiting Admin Action</span>`;
                            }
                        },
                        {
                            data: 'actions',
                            orderable: false,
                            searchable: false,
                            className: 'text-right whitespace-nowrap align-middle w-44 px-5 py-3.5',
                            render: function (actions) {
                                if (actions.conflict_status === 'pending_review' && actions.can_resolve_conflict) {
                                    return `
                                        <div class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                                            <button type="button" 
                                                    onclick="window.leaveApp.openResolveModal(${actions.id}, '${escapeHtml(actions.user_name)}', '${escapeHtml(actions.conflict_date)}', '${escapeHtml(actions.leave_range)}')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                                    title="Resolve Conflict">
                                                <span>⚖️ Resolve Conflict</span>
                                            </button>
                                        </div>
                                    `;
                                } else {
                                    return `<span class="text-xs text-ink-400 font-medium">Completed</span>`;
                                }
                            }
                        }
                    ]
                });
            }
        },

        applyFilters() {
            if (this.activeView === 'applications' && window.leaveTable) {
                window.leaveTable.draw();
            } else if (this.activeView === 'conflicts' && window.conflictsTable) {
                window.conflictsTable.draw();
            }
        },

        applyConflictsFilter() {
            if (window.conflictsTable) {
                window.conflictsTable.draw();
            }
        },

        filterByStatus(statusVal) {
            this.activeView = 'applications';
            this.status = statusVal;
            this.applyFilters();
        },

        switchView(view) {
            this.activeView = view;
            this.$nextTick(() => {
                if (view === 'applications' && window.leaveTable) {
                    window.leaveTable.columns.adjust().draw(false);
                } else if (view === 'conflicts' && window.conflictsTable) {
                    window.conflictsTable.columns.adjust().draw(false);
                }
            });
        },

        resetFilters() {
            this.userId = '';
            this.status = '';
            this.month = '';
            this.search = '';
            this.conflictTab = 'pending';
            if (window.leaveTable) {
                window.leaveTable.search('').draw();
            }
            if (window.conflictsTable) {
                window.conflictsTable.search('').draw();
            }
        },

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

        async approveLeave(leaveId, userName) {
            if (!confirm(`Approve leave application for ${userName}?`)) return;

            try {
                const response = await fetch(`/admin/leave/${leaveId}/approve`, {
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
                    if (window.leaveTable) window.leaveTable.draw(false);
                } else {
                    this.showBanner(res.message || 'Failed to approve leave.', 'error');
                }
            } catch (err) {
                console.error('Approve error:', err);
                this.showBanner('An unexpected error occurred.', 'error');
            }
        },

        openRejectModal(leaveId, userName) {
            this.activeLeaveId = leaveId;
            this.employeeName = userName;
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
                    if (window.leaveTable) window.leaveTable.draw(false);
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

        async cancelLeave(leaveId, userName, daysCount) {
            if (!confirm(`Cancel approved leave for ${userName}? This will refund ${daysCount} day(s) back to employee balance.`)) return;

            try {
                const response = await fetch(`/admin/leave/${leaveId}/cancel`, {
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
                    if (window.leaveTable) window.leaveTable.draw(false);
                } else {
                    this.showBanner(res.message || 'Failed to cancel leave.', 'error');
                }
            } catch (err) {
                console.error('Cancel error:', err);
                this.showBanner('An unexpected error occurred.', 'error');
            }
        },

        openResolveModal(conflictId, empName, conflictDate, leaveDates) {
            this.conflictId = conflictId;
            this.conflictEmp = empName;
            this.conflictDate = conflictDate;
            this.conflictLeaveDates = leaveDates;
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
                    if (window.conflictsTable) window.conflictsTable.draw(false);
                    if (window.leaveTable) window.leaveTable.draw(false);
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
