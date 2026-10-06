@extends('admin.layouts.app')

@section('title', 'Leave Rules & Policy')
@section('breadcrumb', 'ADMIN / LEAVE RULES')
@section('page_title', 'Leave Policy & Entitlement Rules')

@section('content')
<div x-data="leavePolicyManager({
    initialLeaveTypes: {{ Js::from($leaveTypes->map(function ($t) {
        $quotaDisplay = $t->accrual_frequency === 'monthly'
            ? ($t->days_per_month . ' days / month')
            : ($t->days_per_year == 0 ? 'Unlimited' : ($t->days_per_year . ' days / year'));

        return [
            'id'                => $t->id,
            'name'              => $t->name,
            'code'              => $t->code,
            'days_per_year'     => (int) $t->days_per_year,
            'accrual_frequency' => $t->accrual_frequency ?? 'annual',
            'days_per_month'    => (float) $t->days_per_month,
            'carry_forward'     => (bool) $t->carry_forward,
            'is_active'         => (bool) $t->is_active,
            'quota_display'     => $quotaDisplay,
        ];
    })) }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Header Action & Search Bar --}}
    <div class="panel p-6 border border-surface-border shadow-sm rounded-2xl">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900">Leave Entitlement Rules</h2>
                <p class="text-caption mt-0.5 text-xs text-ink-500">Manage yearly quotas, monthly accrual rates, and carry-forward policies.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                {{-- Live Search --}}
                <div class="relative w-full sm:w-60">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text"
                           x-model="search"
                           placeholder="Filter leave rules…"
                           class="form-input text-xs pl-9 w-full">
                </div>

                {{-- Status Filter --}}
                <select x-model="statusFilter" class="form-input text-xs w-auto">
                    <option value="all">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="disabled">Disabled Only</option>
                </select>

                {{-- Add Leave Type Button --}}
                <button type="button"
                        @click="openCreateModal()"
                        class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-2"
                        style="background-color: #4f46e5; color: #ffffff;">
                    <x-lucide-plus class="w-4 h-4" />
                    Add Leave Type
                </button>
            </div>
        </div>
    </div>

    {{-- Data Table Panel --}}
    <div class="panel overflow-hidden border border-surface-border shadow-sm rounded-2xl">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Leave Type Name</th>
                        <th>Accrual Frequency</th>
                        <th>Quota Rate</th>
                        <th>Carry Forward</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border">
                    <template x-for="item in filteredLeaveTypes()" :key="item.id">
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td>
                                <span class="badge badge-primary font-mono font-bold text-xs" x-text="item.code"></span>
                            </td>
                            <td class="font-bold text-ink-900 text-xs" x-text="item.name"></td>
                            <td>
                                <span x-show="item.accrual_frequency === 'monthly'" class="badge badge-warning text-xs inline-flex items-center gap-1">
                                    <x-lucide-calendar-days class="w-3 h-3" /> Monthly Accrual
                                </span>
                                <span x-show="item.accrual_frequency !== 'monthly'" class="badge badge-primary text-xs inline-flex items-center gap-1">
                                    <x-lucide-calendar class="w-3 h-3" /> Annual Upfront
                                </span>
                            </td>
                            <td class="font-extrabold text-ink-900 text-xs" x-text="item.quota_display"></td>
                            <td>
                                <span x-show="item.carry_forward" class="badge badge-success text-xs inline-flex items-center gap-1">
                                    <x-lucide-check class="w-3 h-3" /> Allowed
                                </span>
                                <span x-show="!item.carry_forward" class="badge badge-secondary text-xs inline-flex items-center gap-1">
                                    <x-lucide-x class="w-3 h-3" /> No
                                </span>
                            </td>
                            <td>
                                <button type="button"
                                        @click="toggleStatus(item)"
                                        class="cursor-pointer transition-transform active:scale-95"
                                        :title="item.is_active ? 'Click to disable leave type' : 'Click to activate leave type'">
                                    <span x-show="item.is_active" class="badge badge-success text-xs inline-flex items-center gap-1 hover:opacity-80">
                                        ● Active
                                    </span>
                                    <span x-show="!item.is_active" class="badge badge-danger text-xs inline-flex items-center gap-1 hover:opacity-80">
                                        ○ Disabled
                                    </span>
                                </button>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEditModal(item)"
                                            class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-600 hover:bg-surface-muted hover:text-ink-900 transition-colors shadow-xs"
                                            title="Edit Leave Rule">
                                        <x-lucide-pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button"
                                            @click="deleteLeaveType(item)"
                                            class="p-1.5 rounded-lg border border-red-200 bg-white text-rose-600 hover:bg-rose-50 transition-colors shadow-xs"
                                            title="Delete / Disable Leave Rule">
                                        <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty State --}}
                    <tr x-show="filteredLeaveTypes().length === 0">
                        <td colspan="7" class="text-center py-16 text-ink-400">
                            <div class="flex flex-col items-center gap-2">
                                <x-lucide-file-x class="w-10 h-10 stroke-1 text-ink-300" />
                                <p class="text-xs font-semibold text-ink-600">No leave rules matching filter criteria.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: CREATE LEAVE TYPE (AJAX)                                           --}}
    {{-- ========================================================================= --}}
    <div x-show="createModal.open" x-cloak style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="createModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div x-show="createModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="createModal.open = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

            {{-- Dialog --}}
            <div x-show="createModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                        <x-lucide-calendar-plus class="w-5 h-5 text-indigo-600" />
                        Add Leave Type Rule
                    </h3>
                    <button type="button" @click="createModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate()" class="space-y-4">
                    {{-- Leave Name --}}
                    <div>
                        <label class="form-label text-xs">Leave Name <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="createModal.form.name"
                               required
                               placeholder="e.g. Casual Leave, Earned Leave"
                               class="form-input text-xs w-full"
                               :class="createModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="createModal.errors.name">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="createModal.errors.name[0]"></p>
                        </template>
                    </div>

                    {{-- Short Code --}}
                    <div>
                        <label class="form-label text-xs">Short Code <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="createModal.form.code"
                               required
                               maxlength="10"
                               placeholder="e.g. CL, EL, SL"
                               class="form-input text-xs w-full uppercase font-mono"
                               :class="createModal.errors.code ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="createModal.errors.code">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="createModal.errors.code[0]"></p>
                        </template>
                    </div>

                    {{-- Accrual Policy Frequency --}}
                    <div>
                        <label class="form-label text-xs">Accrual Policy Frequency <span class="text-rose-500">*</span></label>
                        <select x-model="createModal.form.accrual_frequency" required class="form-input text-xs w-full">
                            <option value="annual">Annual Upfront (Granted Jan 1st)</option>
                            <option value="monthly">Monthly Accrual (Granted 1st of every month)</option>
                        </select>
                    </div>

                    {{-- Days Per Year (Annual) --}}
                    <div x-show="createModal.form.accrual_frequency === 'annual'">
                        <label class="form-label text-xs">Allowed Days Per Year <span class="text-rose-500">*</span></label>
                        <input type="number"
                               x-model.number="createModal.form.days_per_year"
                               min="0"
                               placeholder="e.g. 12 (0 for unlimited)"
                               class="form-input text-xs w-full"
                               :class="createModal.errors.days_per_year ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="createModal.errors.days_per_year">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="createModal.errors.days_per_year[0]"></p>
                        </template>
                    </div>

                    {{-- Days Per Month (Monthly) --}}
                    <div x-show="createModal.form.accrual_frequency === 'monthly'">
                        <label class="form-label text-xs">Allowed Days Granted Per Month <span class="text-rose-500">*</span></label>
                        <input type="number"
                               step="0.5"
                               x-model.number="createModal.form.days_per_month"
                               min="0"
                               placeholder="e.g. 1.5 days/month"
                               class="form-input text-xs w-full"
                               :class="createModal.errors.days_per_month ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <p class="text-[11px] text-ink-500 mt-1">System automatically adds this amount to each fieldworker's balance on the 1st of every month.</p>
                        <template x-if="createModal.errors.days_per_month">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="createModal.errors.days_per_month[0]"></p>
                        </template>
                    </div>

                    {{-- Carry Forward --}}
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox"
                               x-model="createModal.form.carry_forward"
                               id="create_carry_forward"
                               class="rounded border-surface-border text-indigo-600 focus:ring-indigo-500">
                        <label for="create_carry_forward" class="text-xs font-medium text-ink-700 cursor-pointer">
                            Allow Carry Forward to Next Year
                        </label>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-surface-border">
                        <button type="button" @click="createModal.open = false" :disabled="createModal.saving" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="submit" :disabled="createModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="createModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="createModal.saving ? 'Saving...' : 'Save Leave Rule'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: EDIT LEAVE TYPE (AJAX)                                             --}}
    {{-- ========================================================================= --}}
    <div x-show="editModal.open" x-cloak style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="editModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div x-show="editModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="editModal.open = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

            {{-- Dialog --}}
            <div x-show="editModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                        <x-lucide-pencil class="w-4 h-4 text-indigo-600" />
                        Edit Leave Rule
                    </h3>
                    <button type="button" @click="editModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit()" class="space-y-4">
                    {{-- Leave Name --}}
                    <div>
                        <label class="form-label text-xs">Leave Name <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="editModal.form.name"
                               required
                               class="form-input text-xs w-full"
                               :class="editModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="editModal.errors.name">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.name[0]"></p>
                        </template>
                    </div>

                    {{-- Short Code --}}
                    <div>
                        <label class="form-label text-xs">Short Code <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="editModal.form.code"
                               required
                               maxlength="10"
                               class="form-input text-xs w-full uppercase font-mono"
                               :class="editModal.errors.code ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="editModal.errors.code">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.code[0]"></p>
                        </template>
                    </div>

                    {{-- Accrual Policy Frequency --}}
                    <div>
                        <label class="form-label text-xs">Accrual Policy Frequency <span class="text-rose-500">*</span></label>
                        <select x-model="editModal.form.accrual_frequency" required class="form-input text-xs w-full">
                            <option value="annual">Annual Upfront (Granted Jan 1st)</option>
                            <option value="monthly">Monthly Accrual (Granted 1st of every month)</option>
                        </select>
                    </div>

                    {{-- Days Per Year (Annual) --}}
                    <div x-show="editModal.form.accrual_frequency === 'annual'">
                        <label class="form-label text-xs">Allowed Days Per Year <span class="text-rose-500">*</span></label>
                        <input type="number"
                               x-model.number="editModal.form.days_per_year"
                               min="0"
                               class="form-input text-xs w-full"
                               :class="editModal.errors.days_per_year ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="editModal.errors.days_per_year">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.days_per_year[0]"></p>
                        </template>
                    </div>

                    {{-- Days Per Month (Monthly) --}}
                    <div x-show="editModal.form.accrual_frequency === 'monthly'">
                        <label class="form-label text-xs">Allowed Days Granted Per Month <span class="text-rose-500">*</span></label>
                        <input type="number"
                               step="0.5"
                               x-model.number="editModal.form.days_per_month"
                               min="0"
                               class="form-input text-xs w-full"
                               :class="editModal.errors.days_per_month ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="editModal.errors.days_per_month">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.days_per_month[0]"></p>
                        </template>
                    </div>

                    {{-- Carry Forward & Active Status --}}
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                   x-model="editModal.form.carry_forward"
                                   id="edit_carry_forward"
                                   class="rounded border-surface-border text-indigo-600 focus:ring-indigo-500">
                            <label for="edit_carry_forward" class="text-xs font-medium text-ink-700 cursor-pointer">
                                Carry Forward
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                   x-model="editModal.form.is_active"
                                   id="edit_is_active"
                                   class="rounded border-surface-border text-indigo-600 focus:ring-indigo-500">
                            <label for="edit_is_active" class="text-xs font-medium text-ink-700 cursor-pointer">
                                Active Status
                            </label>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-surface-border">
                        <button type="button" @click="editModal.open = false" :disabled="editModal.saving" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="editModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="editModal.saving ? 'Saving...' : 'Update Leave Rule'"></span>
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
function leavePolicyManager(config) {
    return {
        leaveTypes: config.initialLeaveTypes || [],
        csrfToken: config.csrfToken,
        search: '',
        statusFilter: 'all',

        createModal: {
            open: false,
            saving: false,
            form: {
                name: '',
                code: '',
                accrual_frequency: 'annual',
                days_per_year: 12,
                days_per_month: 1.5,
                carry_forward: false
            },
            errors: {}
        },

        editModal: {
            open: false,
            id: null,
            saving: false,
            form: {
                name: '',
                code: '',
                accrual_frequency: 'annual',
                days_per_year: 12,
                days_per_month: 1.5,
                carry_forward: false,
                is_active: true
            },
            errors: {}
        },

        filteredLeaveTypes() {
            const q = this.search.toLowerCase().trim();
            return this.leaveTypes.filter(t => {
                const matchesSearch = !q ||
                    (t.name && t.name.toLowerCase().includes(q)) ||
                    (t.code && t.code.toLowerCase().includes(q)) ||
                    (t.accrual_frequency && t.accrual_frequency.toLowerCase().includes(q));

                const matchesStatus = this.statusFilter === 'all' ||
                    (this.statusFilter === 'active' && t.is_active) ||
                    (this.statusFilter === 'disabled' && !t.is_active);

                return matchesSearch && matchesStatus;
            });
        },

        openCreateModal() {
            this.createModal.form = {
                name: '',
                code: '',
                accrual_frequency: 'annual',
                days_per_year: 12,
                days_per_month: 1.5,
                carry_forward: false
            };
            this.createModal.errors = {};
            this.createModal.open = true;
        },

        async submitCreate() {
            this.createModal.saving = true;
            this.createModal.errors = {};

            try {
                const res = await fetch(`{{ route('admin.leave-types.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.createModal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.leaveTypes.push(data.leave_type);
                    this.createModal.open = false;
                    window.toast(data.message || 'Leave rule created successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.createModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to create leave rule.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error creating leave rule.', 'error');
            } finally {
                this.createModal.saving = false;
            }
        },

        openEditModal(item) {
            this.editModal.id = item.id;
            this.editModal.form = {
                name: item.name,
                code: item.code,
                accrual_frequency: item.accrual_frequency,
                days_per_year: item.days_per_year,
                days_per_month: item.days_per_month,
                carry_forward: Boolean(item.carry_forward),
                is_active: Boolean(item.is_active)
            };
            this.editModal.errors = {};
            this.editModal.open = true;
        },

        async submitEdit() {
            this.editModal.saving = true;
            this.editModal.errors = {};

            try {
                const res = await fetch(`/admin/leave-types/${this.editModal.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.editModal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.leaveTypes.findIndex(t => t.id === this.editModal.id);
                    if (idx > -1) {
                        this.leaveTypes[idx] = data.leave_type;
                    }
                    this.editModal.open = false;
                    window.toast(data.message || 'Leave rule updated successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.editModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to update leave rule.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error updating leave rule.', 'error');
            } finally {
                this.editModal.saving = false;
            }
        },

        async toggleStatus(item) {
            try {
                const res = await fetch(`/admin/leave-types/${item.id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.leaveTypes.findIndex(t => t.id === item.id);
                    if (idx > -1) {
                        this.leaveTypes[idx] = data.leave_type;
                    }
                    window.toast(data.message || 'Status updated.', 'success');
                } else {
                    window.toast(data.message || 'Failed to toggle status.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error updating status.', 'error');
            }
        },

        async deleteLeaveType(item) {
            if (!confirm(`Are you sure you want to delete or deactivate leave rule '${item.name}' (${item.code})?`)) {
                return;
            }

            try {
                const res = await fetch(`/admin/leave-types/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.deleted) {
                        this.leaveTypes = this.leaveTypes.filter(t => t.id !== item.id);
                    } else if (data.deactivated && data.leave_type) {
                        const idx = this.leaveTypes.findIndex(t => t.id === item.id);
                        if (idx > -1) {
                            this.leaveTypes[idx] = data.leave_type;
                        }
                    }
                    window.toast(data.message || 'Action completed.', 'success');
                } else {
                    window.toast(data.message || 'Failed to delete leave rule.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error deleting leave rule.', 'error');
            }
        }
    };
}
</script>
@endpush
