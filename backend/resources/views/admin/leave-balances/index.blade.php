@extends('admin.layouts.app')

@section('title', 'Employee Leave Balances')
@section('breadcrumb', 'ADMIN / LEAVE BALANCES')
@section('page_title', 'Employee Leave Balances & Manual Adjustments')

@section('content')
<div x-data="leaveBalanceManager({
    initialBalances: {{ Js::from($formattedBalances) }},
    initialSummary: {{ Js::from($summaryData) }},
    initialPagination: {
        total: {{ $balances->total() }},
        current_page: {{ $balances->currentPage() }},
        last_page: {{ $balances->lastPage() }},
        per_page: {{ $balances->perPage() }},
        from: {{ $balances->firstItem() ?? 0 }},
        to: {{ $balances->lastItem() ?? 0 }}
    },
    users: {{ Js::from($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'role_name' => $u->role_name])) }},
    leaveTypes: {{ Js::from($leaveTypes->map(fn($lt) => ['id' => $lt->id, 'name' => $lt->name, 'code' => $lt->code])) }},
    initialYear: {{ (int)$year }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Filter Panel --}}
    <div class="panel p-6 border border-surface-border shadow-sm rounded-2xl">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <h2 class="text-lg font-bold text-ink-900">Leave Balance Directory</h2>
                    <p class="text-caption mt-0.5 text-xs text-ink-500">View quotas, accrued balances, used leave, and make adjustments per employee.</p>
                </div>

                {{-- Fast Search Input --}}
                <div class="relative w-full lg:w-72">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text"
                           x-model="search"
                           @input.debounce.300ms="fetchBalances(1)"
                           placeholder="Search employee or code…"
                           class="form-input text-xs pl-9 w-full">
                </div>
            </div>

            {{-- Filter Selectors --}}
            <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-surface-border">
                {{-- Year Selector --}}
                <div class="flex items-center gap-2">
                    <label class="form-label text-xs font-bold mb-0 whitespace-nowrap text-ink-700">Year:</label>
                    <select x-model="year" @change="fetchBalances(1)" class="form-input text-xs w-auto min-w-[100px]">
                        @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Employee Filter --}}
                <div class="flex items-center gap-2 min-w-[200px] flex-1 sm:flex-none">
                    <label class="form-label text-xs font-bold mb-0 whitespace-nowrap text-ink-700">Employee:</label>
                    <select x-model="userId" @change="fetchBalances(1)" class="form-input text-xs w-full">
                        <option value="">All Employees</option>
                        <template x-for="u in users" :key="u.id">
                            <option :value="u.id" x-text="`${u.name} (${u.role_name})`"></option>
                        </template>
                    </select>
                </div>

                {{-- Leave Type Filter --}}
                <div class="flex items-center gap-2 min-w-[180px] flex-1 sm:flex-none">
                    <label class="form-label text-xs font-bold mb-0 whitespace-nowrap text-ink-700">Leave Type:</label>
                    <select x-model="leaveTypeId" @change="fetchBalances(1)" class="form-input text-xs w-full">
                        <option value="">All Leave Types</option>
                        <template x-for="lt in leaveTypes" :key="lt.id">
                            <option :value="lt.id" x-text="`${lt.name} (${lt.code})`"></option>
                        </template>
                    </select>
                </div>

                {{-- Reset Button --}}
                <button type="button"
                        x-show="userId || leaveTypeId || search"
                        @click="resetFilters()"
                        class="btn-secondary text-xs py-2 px-3 inline-flex items-center gap-1.5 ml-auto">
                    <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-500" />
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    {{-- Metric Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Total Allocated --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-indigo-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Allocated</p>
                <h3 class="text-2xl font-extrabold text-indigo-600 mt-1" x-text="summary.total_allocated"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                <x-lucide-calendar-check class="w-5 h-5" />
            </div>
        </div>

        {{-- Carry Forward --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-blue-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Carried Forward</p>
                <h3 class="text-2xl font-extrabold text-blue-600 mt-1" x-text="summary.total_carried_forward"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                <x-lucide-corner-down-right class="w-5 h-5" />
            </div>
        </div>

        {{-- Total Used --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-rose-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Used</p>
                <h3 class="text-2xl font-extrabold text-rose-600 mt-1" x-text="summary.total_used"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                <x-lucide-palmtree class="w-5 h-5" />
            </div>
        </div>

        {{-- Total Available --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-emerald-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Available</p>
                <h3 class="text-2xl font-extrabold text-emerald-600 mt-1" x-text="summary.total_available"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                <x-lucide-check-circle-2 class="w-5 h-5" />
            </div>
        </div>
    </div>

    {{-- Data Table Panel --}}
    <div class="panel overflow-hidden border border-surface-border shadow-sm rounded-2xl relative">
        
        {{-- Loading Overlay --}}
        <div x-show="loading"
             x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-white/75 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center gap-3">
            <svg class="animate-spin h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-bold text-ink-600">Updating balances...</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Leave Type</th>
                        <th>Year</th>
                        <th class="text-center">Allocated</th>
                        <th class="text-center">Carry Forward</th>
                        <th class="text-center">Used</th>
                        <th class="text-center">Available</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border">
                    <template x-for="b in balances" :key="b.id">
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td>
                                <div class="font-bold text-ink-900 text-xs" x-text="b.user_name"></div>
                            </td>
                            <td>
                                <span class="badge badge-primary font-mono font-bold text-xs inline-flex items-center gap-1.5">
                                    <span x-text="b.leave_type_code"></span>
                                    <span class="font-normal opacity-80" x-text="`— ${b.leave_type_name}`"></span>
                                </span>
                            </td>
                            <td class="font-medium text-ink-600 text-xs" x-text="b.year"></td>
                            <td class="text-center font-bold text-ink-900 text-xs" x-text="b.allocated"></td>
                            <td class="text-center text-blue-600 font-semibold text-xs" x-text="b.carried_forward"></td>
                            <td class="text-center font-bold text-rose-600 text-xs" x-text="b.used"></td>
                            <td class="text-center">
                                <span class="badge badge-success font-extrabold text-xs px-2.5 py-0.5" x-text="b.available"></span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button"
                                        @click="openAdjustModal(b)"
                                        class="btn-secondary text-xs py-1 px-2.5 inline-flex items-center gap-1.5">
                                    <x-lucide-sliders-horizontal class="w-3.5 h-3.5 text-indigo-600" />
                                    Adjust Balance
                                </button>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty State --}}
                    <tr x-show="!loading && balances.length === 0">
                        <td colspan="8" class="text-center py-16 text-ink-400">
                            <div class="flex flex-col items-center gap-2">
                                <x-lucide-wallet-cards class="w-10 h-10 stroke-1 text-ink-300" />
                                <p class="text-xs font-semibold text-ink-600">No leave balance records found for <span x-text="year"></span>.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div x-show="pagination.last_page > 1" class="p-4 border-t border-surface-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-600">
            <div>
                Showing <span class="font-bold text-ink-900" x-text="pagination.from || 0"></span> to
                <span class="font-bold text-ink-900" x-text="pagination.to || 0"></span> of
                <span class="font-bold text-ink-900" x-text="pagination.total"></span> balances
            </div>

            <div class="inline-flex items-center gap-1.5">
                <button type="button"
                        @click="fetchBalances(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-700 hover:bg-surface-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <x-lucide-chevron-left class="w-4 h-4" />
                </button>

                <span class="px-2.5 py-1 font-bold text-ink-900 bg-surface-muted rounded-lg"
                      x-text="`Page ${pagination.current_page} of ${pagination.last_page}`"></span>

                <button type="button"
                        @click="fetchBalances(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-700 hover:bg-surface-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <x-lucide-chevron-right class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: ADJUST LEAVE BALANCE (AJAX)                                        --}}
    {{-- ========================================================================= --}}
    <div x-show="adjustModal.open" x-cloak style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="adjustModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div x-show="adjustModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="adjustModal.open = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

            {{-- Dialog --}}
            <div x-show="adjustModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <div>
                        <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                            <x-lucide-sliders-horizontal class="w-4 h-4 text-indigo-600" />
                            Adjust Leave Balance
                        </h3>
                        <p class="text-xs text-ink-500 mt-0.5">
                            For <span class="font-bold text-ink-900" x-text="adjustModal.user_name"></span>
                            — <span class="font-bold text-indigo-600" x-text="adjustModal.code"></span> (<span x-text="adjustModal.leave_type_name"></span>)
                        </p>
                    </div>
                    <button type="button" @click="adjustModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAdjust()" class="space-y-4">
                    {{-- Allocated Days --}}
                    <div>
                        <label class="form-label text-xs">Allocated Days <span class="text-rose-500">*</span></label>
                        <input type="number"
                               step="0.5"
                               x-model.number="adjustModal.form.allocated"
                               min="0"
                               required
                               class="form-input text-xs w-full"
                               :class="adjustModal.errors.allocated ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="adjustModal.errors.allocated">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="adjustModal.errors.allocated[0]"></p>
                        </template>
                    </div>

                    {{-- Carry Forward Days --}}
                    <div>
                        <label class="form-label text-xs">Carry Forward Days <span class="text-rose-500">*</span></label>
                        <input type="number"
                               step="0.5"
                               x-model.number="adjustModal.form.carried_forward"
                               min="0"
                               required
                               class="form-input text-xs w-full"
                               :class="adjustModal.errors.carried_forward ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="adjustModal.errors.carried_forward">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="adjustModal.errors.carried_forward[0]"></p>
                        </template>
                    </div>

                    {{-- Used Days --}}
                    <div>
                        <label class="form-label text-xs">Used Days <span class="text-rose-500">*</span></label>
                        <input type="number"
                               step="0.5"
                               x-model.number="adjustModal.form.used"
                               min="0"
                               required
                               class="form-input text-xs w-full"
                               :class="adjustModal.errors.used ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="adjustModal.errors.used">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="adjustModal.errors.used[0]"></p>
                        </template>
                    </div>

                    {{-- Dynamic Available Preview --}}
                    <div class="p-3 bg-surface-muted rounded-xl border border-surface-border flex items-center justify-between">
                        <span class="text-xs text-ink-600 font-medium">Calculated Available Balance:</span>
                        <span class="text-sm font-extrabold text-emerald-600" x-text="`${calculatedAvailable()} days`"></span>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-surface-border">
                        <button type="button" @click="adjustModal.open = false" :disabled="adjustModal.saving" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="submit" :disabled="adjustModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="adjustModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="adjustModal.saving ? 'Saving...' : 'Save Adjustments'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function leaveBalanceManager(config) {
    return {
        balances: config.initialBalances || [],
        summary: config.initialSummary || {},
        pagination: config.initialPagination || {},
        users: config.users || [],
        leaveTypes: config.leaveTypes || [],
        year: config.initialYear,
        userId: '',
        leaveTypeId: '',
        search: '',
        csrfToken: config.csrfToken,

        loading: false,

        adjustModal: {
            open: false,
            saving: false,
            id: null,
            user_name: '',
            code: '',
            leave_type_name: '',
            form: {
                allocated: 0,
                carried_forward: 0,
                used: 0
            },
            errors: {}
        },

        calculatedAvailable() {
            const alloc = parseFloat(this.adjustModal.form.allocated) || 0;
            const cf = parseFloat(this.adjustModal.form.carried_forward) || 0;
            const used = parseFloat(this.adjustModal.form.used) || 0;
            return Math.max(0, alloc + cf - used).toFixed(1);
        },

        init() {
            window.addEventListener('employee-deleted', (e) => {
                const id = parseInt(e.detail.id);
                this.users = this.users.filter(u => u.id !== id);
            });
        },

        async fetchBalances(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    year: this.year,
                    page: page
                });
                if (this.userId) params.append('user_id', this.userId);
                if (this.leaveTypeId) params.append('leave_type_id', this.leaveTypeId);
                if (this.search) params.append('search', this.search);

                const url = `/admin/leave-balances?${params.toString()}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.balances = data.balances || [];
                    this.summary = data.summary || {};
                    this.pagination = data.pagination || {};
                    if (data.users) {
                        this.users = data.users;
                    }
                    window.history.pushState(null, '', url);
                } else {
                    window.toast('Failed to load leave balances.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error loading balances.', 'error');
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.userId = '';
            this.leaveTypeId = '';
            this.search = '';
            this.fetchBalances(1);
        },

        openAdjustModal(b) {
            this.adjustModal.id = b.id;
            this.adjustModal.user_name = b.user_name;
            this.adjustModal.code = b.leave_type_code;
            this.adjustModal.leave_type_name = b.leave_type_name;
            this.adjustModal.form = {
                allocated: b.allocated,
                carried_forward: b.carried_forward,
                used: b.used
            };
            this.adjustModal.errors = {};
            this.adjustModal.open = true;
        },

        async submitAdjust() {
            this.adjustModal.saving = true;
            this.adjustModal.errors = {};

            try {
                const res = await fetch(`/admin/leave-balances/${this.adjustModal.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.adjustModal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    // Update in list
                    const idx = this.balances.findIndex(b => b.id === this.adjustModal.id);
                    if (idx > -1 && data.balance) {
                        this.balances[idx] = data.balance;
                    }
                    this.adjustModal.open = false;
                    window.toast(data.message || 'Balance adjusted successfully!', 'success');
                    // Refresh summary totals
                    this.fetchBalances(this.pagination.current_page || 1);
                } else if (res.status === 422 && data.errors) {
                    this.adjustModal.errors = data.errors;
                    window.toast(data.message || 'Please check input values.', 'error');
                } else {
                    window.toast(data.message || 'Failed to update balance.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error saving balance adjustment.', 'error');
            } finally {
                this.adjustModal.saving = false;
            }
        }
    };
}
</script>
@endpush
