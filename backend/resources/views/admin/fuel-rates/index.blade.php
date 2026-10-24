@extends('admin.layouts.app')

@section('title', 'Fuel Allowance Rates')
@section('breadcrumb', 'ADMIN / EXPENSES / FUEL RATES')
@section('page_title', 'Role & Employee Fuel Allowance Rates')

@section('content')
<div x-data="fuelRatesManager({
    initialVehicleTypes: {{ Js::from($vehicleTypes) }},
    initialRoleRates: {{ Js::from($roleRates) }},
    initialUserOverrides: {{ Js::from($userOverrides) }},
    initialRoles: {{ Js::from($roles) }},
    initialUsers: {{ Js::from($users) }},
    currentDate: '{{ $date }}',
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Toast / Flash Notification Banner (Zero-reload AJAX feedback) --}}
    <div x-show="toast.show" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="flex items-center gap-3 rounded-xl border px-4 py-3 shadow-sm transition-all"
         :class="toast.type === 'success' ? 'border-green-200 bg-success-bg text-success-text' : 'border-red-200 bg-danger-bg text-danger-text'">
        <template x-if="toast.type === 'success'">
            <x-lucide-check-circle class="w-5 h-5 shrink-0" />
        </template>
        <template x-if="toast.type === 'error'">
            <x-lucide-alert-circle class="w-5 h-5 shrink-0" />
        </template>
        <p class="text-sm font-medium" x-text="toast.message"></p>
        <button type="button" @click="toast.show = false" class="ml-auto opacity-70 hover:opacity-100">
            <x-lucide-x class="w-4 h-4" />
        </button>
    </div>

    {{-- Top Header Action Panel --}}
    <div class="panel p-6 border border-surface-border shadow-sm rounded-xl">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900">Per-Kilometer Travel & Fuel Allowances</h2>
                <p class="text-caption mt-0.5 text-xs text-ink-600">
                    Base rates are set in <a href="{{ route('admin.master-data.index', 'vehicle-types') }}" class="font-semibold text-brand-700 underline hover:text-brand-800">Master Data</a>. 
                    Configure <span class="font-semibold text-brand-700">Role Defaults</span> and <span class="font-semibold text-brand-700">Employee Overrides</span> below.
                </p>

                {{-- Quick Reference: Master Data Base Rates --}}
                <div class="flex flex-wrap items-center gap-2 mt-3 pt-2">
                    <span class="text-[11px] font-semibold text-ink-500 uppercase tracking-wider">Master Data Base Rates:</span>
                    <template x-for="v in vehicleTypes" :key="v.id">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-surface-muted border border-surface-border text-xs text-ink-800">
                            <span class="font-semibold" x-text="v.name + ':'"></span>
                            <span class="font-bold text-ink-900">₹<span x-text="parseFloat(v.rate_per_km || 0).toFixed(2)"></span>/km</span>
                        </span>
                    </template>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                {{-- Live Search --}}
                <div class="relative w-full sm:w-60">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text"
                           x-model="search"
                           @input="filterTables()"
                           placeholder="Search rate records…"
                           class="form-input text-xs pl-9 w-full">
                </div>

                {{-- Status Filter --}}
                <select x-model="statusFilter" @change="filterTables()" class="form-input text-xs w-auto">
                    <option value="all">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="archived">Archived Only</option>
                </select>

                {{-- Add Rate Button --}}
                <button type="button"
                        @click="openAddModal(activeTab === 'users' ? 'user' : 'role')"
                        class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-2">
                    <x-lucide-plus class="w-4 h-4" />
                    <span x-text="activeTab === 'users' ? 'Add Employee Override' : 'Add Role Rate'"></span>
                </button>
            </div>
        </div>

        {{-- Tab Navigation Buttons --}}
        <div class="flex flex-wrap items-center gap-2 mt-6 pt-5 border-t border-surface-border">
            <button type="button"
                    @click="setTab('roles')"
                    class="px-4 py-2 text-xs font-semibold rounded-lg transition-all inline-flex items-center gap-2"
                    :class="activeTab === 'roles'
                        ? 'bg-brand-600 text-white shadow-sm'
                        : 'bg-surface-muted text-ink-700 hover:bg-slate-200'">
                <x-lucide-shield-alert class="w-4 h-4" />
                <span>1. Role-Based Default Rates</span>
                <span class="badge" :class="activeTab === 'roles' ? 'bg-white/20 text-white' : 'bg-surface-border text-ink-700'" x-text="roleRates.length"></span>
            </button>

            <button type="button"
                    @click="setTab('users')"
                    class="px-4 py-2 text-xs font-semibold rounded-lg transition-all inline-flex items-center gap-2"
                    :class="activeTab === 'users'
                        ? 'bg-brand-600 text-white shadow-sm'
                        : 'bg-surface-muted text-ink-700 hover:bg-slate-200'">
                <x-lucide-user-check class="w-4 h-4" />
                <span>2. Individual Employee Overrides</span>
                <span class="badge" :class="activeTab === 'users' ? 'bg-white/20 text-white' : 'bg-surface-border text-ink-700'" x-text="userOverrides.length"></span>
            </button>

            <button type="button"
                    @click="setTab('inspector')"
                    class="px-4 py-2 text-xs font-semibold rounded-lg transition-all inline-flex items-center gap-2"
                    :class="activeTab === 'inspector'
                        ? 'bg-brand-600 text-white shadow-sm'
                        : 'bg-surface-muted text-ink-700 hover:bg-slate-200'">
                <x-lucide-calculator class="w-4 h-4" />
                <span>Rate Calculator & Trace</span>
            </button>
        </div>
    </div>

    {{-- TAB 1: Role Default Rates --}}
    <div x-show="activeTab === 'roles'" class="panel overflow-hidden border border-surface-border shadow-sm rounded-xl">
        <div class="panel-header flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-ink-900">Role-Based Default Rates</h3>
                <p class="text-caption text-xs mt-0.5">Applied automatically to all employees in this role instead of the Master Data base rate.</p>
            </div>
            <button type="button" @click="openAddModal('role')" class="btn-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5">
                <x-lucide-plus class="w-3.5 h-3.5" />
                <span>Add Role Rate</span>
            </button>
        </div>

        <div class="table-wrap">
            <table id="role-rates-table" class="data-table w-full">
                <thead>
                    <tr>
                        <th class="text-left font-semibold">Role Name</th>
                        <th class="text-left font-semibold">Vehicle Type</th>
                        <th class="text-left font-semibold">Rate Per KM</th>
                        <th class="text-left font-semibold">Effective From</th>
                        <th class="text-left font-semibold">Effective To</th>
                        <th class="text-left font-semibold">Status</th>
                        <th class="text-right font-semibold whitespace-nowrap w-36 px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- DataTables will populate this --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB 2: Individual Employee Overrides --}}
    <div x-show="activeTab === 'users'" class="panel overflow-hidden border border-surface-border shadow-sm rounded-xl">
        <div class="panel-header flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-ink-900">Individual Employee Custom Overrides (Highest Priority)</h3>
                <p class="text-caption text-xs mt-0.5">Custom per-KM rate for a specific employee. Overrides both their Role Default and Master Data rates.</p>
            </div>
            <button type="button" @click="openAddModal('user')" class="btn-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1.5">
                <x-lucide-plus class="w-3.5 h-3.5" />
                <span>Add Employee Override</span>
            </button>
        </div>

        <div class="table-wrap">
            <table id="user-overrides-table" class="data-table w-full">
                <thead>
                    <tr>
                        <th class="text-left font-semibold">Employee Name</th>
                        <th class="text-left font-semibold">Employee ID</th>
                        <th class="text-left font-semibold">Vehicle Type</th>
                        <th class="text-left font-semibold">Override Rate</th>
                        <th class="text-left font-semibold">Effective From</th>
                        <th class="text-left font-semibold">Effective To</th>
                        <th class="text-left font-semibold">Status</th>
                        <th class="text-right font-semibold whitespace-nowrap w-36 px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- DataTables will populate this --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB 3: Inspector & Live Rate Calculator --}}
    <div x-show="activeTab === 'inspector'" class="panel p-6 border border-surface-border shadow-sm rounded-xl">
        <div class="max-w-xl space-y-4">
            <div>
                <h3 class="text-base font-bold text-ink-900">Live Rate Resolution Inspector</h3>
                <p class="text-caption text-xs mt-0.5">Test how the system resolves an employee's rate and claim total for any given trip date.</p>
            </div>

            <form @submit.prevent="runInspector()" class="space-y-4 pt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Employee <span class="text-red-500">*</span></label>
                        <select x-model="simForm.user_id" required class="form-input text-xs">
                            <option value="">Select Employee...</option>
                            <template x-for="u in users" :key="u.id">
                                <option :value="u.id" x-text="u.name + (u.employee_id ? ' (' + u.employee_id + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Vehicle Type <span class="text-red-500">*</span></label>
                        <select x-model="simForm.vehicle_identifier" required class="form-input text-xs">
                            <option value="">Select Vehicle...</option>
                            <template x-for="v in vehicleTypes" :key="v.id">
                                <option :value="v.id" x-text="v.name + (v.slug ? ' (' + v.slug + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Trip Date</label>
                        <input type="date" x-model="simForm.date" class="form-input text-xs">
                    </div>

                    <div>
                        <label class="form-label">Claim Distance (KM)</label>
                        <input type="number" step="0.1" x-model="simForm.distance_km" placeholder="e.g. 50" class="form-input text-xs">
                    </div>
                </div>

                <button type="submit" :disabled="simulating" class="btn-primary text-xs py-2 px-4 inline-flex items-center gap-2">
                    <x-lucide-play class="w-4 h-4" />
                    <span x-text="simulating ? 'Calculating…' : 'Calculate & Trace Rate'"></span>
                </button>
            </form>

            {{-- Inspector Output Result Card --}}
            <div x-show="simResult" x-transition class="mt-6 p-4 rounded-xl border border-surface-border bg-surface-muted space-y-3">
                <div class="flex items-center justify-between border-b border-surface-border pb-2">
                    <span class="text-xs font-bold text-ink-900 uppercase tracking-wider">Resolution Trace Result</span>
                    <span class="badge"
                          :class="{
                              'badge-warning': simResult?.source === 'user_override',
                              'badge-info': simResult?.source === 'role_default',
                              'badge-success': simResult?.source === 'global_base' || simResult?.source === 'vehicle_fallback'
                          }"
                          x-text="formatSourceLabel(simResult?.source)"></span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs pt-1">
                    <div>
                        <span class="text-ink-400 block text-xs">Resolved Rate</span>
                        <span class="text-base font-bold text-ink-900">₹<span x-text="parseFloat(simResult?.rate_per_km || 0).toFixed(2)"></span> <span class="text-xs font-normal text-ink-500">/ km</span></span>
                    </div>
                    <div x-show="simResult?.distance_km">
                        <span class="text-ink-400 block text-xs">Travel Distance</span>
                        <span class="text-base font-bold text-ink-900"><span x-text="simResult?.distance_km"></span> km</span>
                    </div>
                    <div x-show="simResult?.total_amount">
                        <span class="text-ink-400 block text-xs">Calculated Expense</span>
                        <span class="text-base font-bold text-brand-700">₹<span x-text="parseFloat(simResult?.total_amount || 0).toFixed(2)"></span></span>
                    </div>
                    <div>
                        <span class="text-ink-400 block text-xs">Vehicle</span>
                        <span class="font-semibold text-ink-900" x-text="simResult?.vehicle_name || '—'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Add / Edit Rate Form --}}
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-transition.opacity style="display: none;">
        <div @click.away="showAddModal = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-surface-border" x-transition.scale>
            <div class="px-6 py-4 border-b border-surface-border bg-surface-muted/50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-ink-900 text-base" x-text="modalTitle()"></h3>
                    <p class="text-xs text-ink-500" x-text="modalSubtitle()"></p>
                </div>
                <button type="button" @click="showAddModal = false" class="text-ink-400 hover:text-ink-700 p-1 rounded-lg">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitRateForm()" class="p-6 space-y-4">
                {{-- Hidden ID when editing --}}
                <input type="hidden" x-model="form.id">

                {{-- Scope Selection (Role or Employee) --}}
                <div>
                    <label class="form-label font-semibold text-xs text-ink-700">Type of Rate *</label>
                    <select x-model="form.scope_type" required class="form-input text-xs mt-1">
                        <option value="role">Role-Based Default Rate (Applies to all employees in role)</option>
                        <option value="user">Individual Employee Override (Custom rate for one employee)</option>
                    </select>
                </div>

                {{-- Role Select if scope_type === 'role' --}}
                <div x-show="form.scope_type === 'role'">
                    <label class="form-label font-semibold text-xs text-ink-700">Select Role *</label>
                    <select x-model="form.scope_id" :required="form.scope_type === 'role'" class="form-input text-xs mt-1">
                        <option value="">-- Choose Role --</option>
                        <template x-for="r in roles" :key="r.id">
                            <option :value="r.id" x-text="r.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Employee Select if scope_type === 'user' --}}
                <div x-show="form.scope_type === 'user'">
                    <label class="form-label font-semibold text-xs text-ink-700">Select Employee *</label>
                    <select x-model="form.scope_id" :required="form.scope_type === 'user'" class="form-input text-xs mt-1">
                        <option value="">-- Choose Employee --</option>
                        <template x-for="u in users" :key="u.id">
                            <option :value="u.id" x-text="u.name + (u.employee_id ? ' (' + u.employee_id + ')' : '')"></option>
                        </template>
                    </select>
                </div>

                {{-- Vehicle Type --}}
                <div>
                    <label class="form-label font-semibold text-xs text-ink-700">Vehicle Type *</label>
                    <select x-model="form.vehicle_type_id" required class="form-input text-xs mt-1">
                        <option value="">-- Choose Vehicle Type --</option>
                        <template x-for="v in vehicleTypes" :key="v.id">
                            <option :value="v.id" x-text="v.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Rate Per KM --}}
                <div>
                    <label class="form-label font-semibold text-xs text-ink-700">Rate Per KM (₹) *</label>
                    <div class="flex items-stretch rounded-lg shadow-sm mt-1 overflow-hidden border border-surface-border focus-within:ring-2 focus-within:ring-brand-500 focus-within:border-brand-500 bg-white">
                        <span class="inline-flex items-center px-3.5 bg-surface-muted border-r border-surface-border text-ink-600 font-bold text-sm select-none">
                            ₹
                        </span>
                        <input type="number" step="0.01" min="0" x-model="form.rate_per_km" required class="flex-1 px-3 py-2 border-0 font-bold text-ink-900 focus:outline-none focus:ring-0 text-sm bg-transparent" placeholder="0.00">
                    </div>
                </div>

                {{-- Effective Dates --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label font-semibold text-xs text-ink-700">Effective From *</label>
                        <input type="date" x-model="form.effective_from" required class="form-input text-xs mt-1">
                    </div>
                    <div>
                        <label class="form-label font-semibold text-xs text-ink-700">Effective To (Optional)</label>
                        <input type="date" x-model="form.effective_to" class="form-input text-xs mt-1">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="form-label font-semibold text-xs text-ink-700">Notes / Remarks</label>
                    <input type="text" x-model="form.notes" placeholder="e.g. Rate adjustment for sales team" class="form-input text-xs mt-1">
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end gap-2.5 pt-4 border-t border-surface-border">
                    <button type="button" @click="showAddModal = false" :disabled="saving" class="btn-secondary text-xs">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving" class="btn-primary text-xs">
                        <span x-text="saving ? 'Saving...' : 'Save Rate'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function fuelRatesManager(config) {
    return {
        activeTab: 'roles',
        search: '',
        statusFilter: 'all',
        vehicleTypes: config.initialVehicleTypes || [],
        roleRates: config.initialRoleRates || [],
        userOverrides: config.initialUserOverrides || [],
        roles: config.initialRoles || [],
        users: config.initialUsers || [],
        currentDate: config.currentDate || '',
        csrfToken: config.csrfToken || '',

        showAddModal: false,
        saving: false,
        simulating: false,
        simResult: null,

        roleTable: null,
        userTable: null,

        toast: {
            show: false,
            message: '',
            type: 'success',
            timer: null,
        },

        form: {
            id: null,
            scope_type: 'role',
            scope_id: '',
            vehicle_type_id: '',
            rate_per_km: '',
            effective_from: config.currentDate || '',
            effective_to: '',
            notes: '',
        },

        simForm: {
            user_id: '',
            vehicle_identifier: '',
            date: config.currentDate || '',
            distance_km: '',
        },

        init() {
            window.fuelRatesApp = this;
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

            // 1. Role Rates DataTable
            if (this.roleTable) {
                try { this.roleTable.destroy(); } catch (e) {}
            }

            this.roleTable = new DT('#role-rates-table', {
                data: this.roleRates,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']],
                dom: '<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-b border-surface-border bg-surface-muted/20"<"text-xs text-ink-600 flex items-center gap-2"l><"text-xs text-ink-500 font-medium"i>>rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-t border-surface-border bg-white"<"text-caption text-ink-500 font-medium"i><"text-xs"p>>',
                language: {
                    emptyTable: 'No role-based rates configured. Employees without an override will use Master Data rate.',
                    zeroRecords: 'No matching role rates found.',
                    info: 'Showing _START_ to _END_ of _TOTAL_ role rates',
                    infoEmpty: 'Showing 0 to 0 of 0 role rates',
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
                        data: 'role',
                        render: function(data, type, row) {
                            const name = data && data.name ? data.name : ('Role #' + (row.scope_id || ''));
                            return `<span class="badge badge-info text-xs font-semibold">${name}</span>`;
                        }
                    },
                    {
                        data: 'vehicle_type',
                        render: function(data, type, row) {
                            const name = data && data.name ? data.name : ('Vehicle #' + (row.vehicle_type_id || ''));
                            return `<span class="font-semibold text-ink-800">${name}</span>`;
                        }
                    },
                    {
                        data: 'rate_per_km',
                        render: function(data) {
                            return `<span class="font-bold text-ink-900">₹${parseFloat(data || 0).toFixed(2)}</span> <span class="text-xs font-normal text-ink-400">/ km</span>`;
                        }
                    },
                    {
                        data: 'effective_from',
                        render: function(data) {
                            return self.formatDate(data);
                        }
                    },
                    {
                        data: 'effective_to',
                        render: function(data) {
                            if (!data) {
                                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active (Ongoing)</span>`;
                            }
                            return `<span class="text-xs text-ink-700 font-medium">${self.formatDate(data)}</span>`;
                        }
                    },
                    {
                        data: 'is_active',
                        render: function(data) {
                            if (data) {
                                return `<span class="badge badge-success text-xs font-medium">Active</span>`;
                            }
                            return `<span class="badge badge-neutral text-xs font-medium">Archived</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap align-middle w-36 px-5 py-3.5',
                        render: function(data, type, row) {
                            return `
                                <div class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                                    <button type="button" 
                                            onclick="window.fuelRatesApp.openEditModalById(${row.id})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                            title="Edit Rate">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" 
                                            onclick="window.fuelRatesApp.deleteRate(${row.id})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                            title="Delete Rate">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ]
            });

            // 2. Individual Employee Overrides DataTable
            if (this.userTable) {
                try { this.userTable.destroy(); } catch (e) {}
            }

            this.userTable = new DT('#user-overrides-table', {
                data: this.userOverrides,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']],
                dom: '<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-b border-surface-border bg-surface-muted/20"<"text-xs text-ink-600 flex items-center gap-2"l><"text-xs text-ink-500 font-medium"i>>rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-3 border-t border-surface-border bg-white"<"text-caption text-ink-500 font-medium"i><"text-xs"p>>',
                language: {
                    emptyTable: 'No individual employee overrides configured. Employees will use their Role rate or Master Data base rate.',
                    zeroRecords: 'No matching employee overrides found.',
                    info: 'Showing _START_ to _END_ of _TOTAL_ overrides',
                    infoEmpty: 'Showing 0 to 0 of 0 overrides',
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
                        data: 'user',
                        render: function(data, type, row) {
                            const name = data && data.name ? data.name : ('User #' + (row.scope_id || ''));
                            return `<span class="font-bold text-ink-900">${name}</span>`;
                        }
                    },
                    {
                        data: 'user',
                        render: function(data) {
                            const code = data && data.employee_id ? data.employee_id : '—';
                            return `<span class="badge badge-neutral text-xs font-mono font-medium">${code}</span>`;
                        }
                    },
                    {
                        data: 'vehicle_type',
                        render: function(data, type, row) {
                            const name = data && data.name ? data.name : ('Vehicle #' + (row.vehicle_type_id || ''));
                            return `<span class="font-semibold text-ink-800">${name}</span>`;
                        }
                    },
                    {
                        data: 'rate_per_km',
                        render: function(data) {
                            return `<span class="font-bold text-brand-700">₹${parseFloat(data || 0).toFixed(2)}</span> <span class="text-xs font-normal text-ink-400">/ km</span>`;
                        }
                    },
                    {
                        data: 'effective_from',
                        render: function(data) {
                            return self.formatDate(data);
                        }
                    },
                    {
                        data: 'effective_to',
                        render: function(data) {
                            if (!data) {
                                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active (Ongoing)</span>`;
                            }
                            return `<span class="text-xs text-ink-700 font-medium">${self.formatDate(data)}</span>`;
                        }
                    },
                    {
                        data: 'is_active',
                        render: function(data) {
                            if (data) {
                                return `<span class="badge badge-success text-xs font-medium">Active</span>`;
                            }
                            return `<span class="badge badge-neutral text-xs font-medium">Archived</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap align-middle w-36 px-5 py-3.5',
                        render: function(data, type, row) {
                            return `
                                <div class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                                    <button type="button" 
                                            onclick="window.fuelRatesApp.openEditModalById(${row.id})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                            title="Edit Override">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" 
                                            onclick="window.fuelRatesApp.deleteRate(${row.id})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors shadow-2xs cursor-pointer"
                                            title="Delete Override">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ]
            });
        },

        filterTables() {
            const q = (this.search || '').trim();
            const status = this.statusFilter;

            if (this.roleTable) {
                this.roleTable.search(q);
                if (status === 'active') {
                    this.roleTable.column(5).search('^Active$', true, false);
                } else if (status === 'archived') {
                    this.roleTable.column(5).search('^Archived$', true, false);
                } else {
                    this.roleTable.column(5).search('');
                }
                this.roleTable.draw();
            }

            if (this.userTable) {
                this.userTable.search(q);
                if (status === 'active') {
                    this.userTable.column(6).search('^Active$', true, false);
                } else if (status === 'archived') {
                    this.userTable.column(6).search('^Archived$', true, false);
                } else {
                    this.userTable.column(6).search('');
                }
                this.userTable.draw();
            }
        },

        setTab(tab) {
            this.activeTab = tab;
            this.$nextTick(() => {
                if (tab === 'roles' && this.roleTable) {
                    this.roleTable.columns.adjust().draw(false);
                } else if (tab === 'users' && this.userTable) {
                    this.userTable.columns.adjust().draw(false);
                }
            });
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timer) clearTimeout(this.toast.timer);
            this.toast.timer = setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },

        openAddModal(scopeType = 'role') {
            this.form.id = null;
            this.form.scope_type = scopeType;
            this.form.scope_id = '';
            this.form.vehicle_type_id = this.vehicleTypes[0]?.id || '';
            this.form.rate_per_km = '';
            this.form.effective_from = this.currentDate;
            this.form.effective_to = '';
            this.form.notes = '';
            this.showAddModal = true;
        },

        openEditModalById(id) {
            const rate = this.roleRates.find(r => r.id === id) || this.userOverrides.find(r => r.id === id);
            if (rate) {
                this.openEditModal(rate);
            }
        },

        openEditModal(rate) {
            this.form.id = rate.id;
            this.form.scope_type = rate.scope_type;
            this.form.scope_id = rate.scope_id || '';
            this.form.vehicle_type_id = rate.vehicle_type_id || '';
            this.form.rate_per_km = parseFloat(rate.rate_per_km).toFixed(2);
            this.form.effective_from = rate.effective_from ? rate.effective_from.substring(0, 10) : this.currentDate;
            this.form.effective_to = rate.effective_to ? rate.effective_to.substring(0, 10) : '';
            this.form.notes = rate.notes || '';
            this.showAddModal = true;
        },

        modalTitle() {
            const prefix = this.form.id ? 'Edit ' : 'Add ';
            if (this.form.scope_type === 'user') return prefix + 'Employee Rate Override';
            return prefix + 'Role Default Rate';
        },

        modalSubtitle() {
            if (this.form.scope_type === 'user') return 'Custom rate override for a specific employee';
            return 'Default rate for all employees with this role';
        },

        async submitRateForm() {
            this.saving = true;
            try {
                const res = await fetch('{{ route('admin.fuel-rates.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await res.json();
                if (data.success) {
                    if (data.role_rates) {
                        this.roleRates = data.role_rates;
                        if (this.roleTable) {
                            this.roleTable.clear().rows.add(this.roleRates).draw(false);
                        }
                    }
                    if (data.user_overrides) {
                        this.userOverrides = data.user_overrides;
                        if (this.userTable) {
                            this.userTable.clear().rows.add(this.userOverrides).draw(false);
                        }
                    }
                    this.showAddModal = false;
                    this.showToast(data.message || 'Rate saved successfully!', 'success');
                } else {
                    this.showToast(data.message || 'Validation error while saving rate.', 'error');
                }
            } catch (err) {
                this.showToast('An error occurred while saving rate.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteRate(id) {
            if (!confirm('Are you sure you want to remove this fuel allowance rate?')) return;

            try {
                const res = await fetch(`{{ url('/admin/fuel-rates') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    if (data.role_rates) {
                        this.roleRates = data.role_rates;
                        if (this.roleTable) {
                            this.roleTable.clear().rows.add(this.roleRates).draw(false);
                        }
                    }
                    if (data.user_overrides) {
                        this.userOverrides = data.user_overrides;
                        if (this.userTable) {
                            this.userTable.clear().rows.add(this.userOverrides).draw(false);
                        }
                    }
                    this.showToast(data.message || 'Rate override removed successfully!', 'success');
                } else {
                    this.showToast(data.message || 'Failed to remove rate.', 'error');
                }
            } catch (e) {
                this.showToast('Error removing rate.', 'error');
            }
        },

        async runInspector() {
            this.simulating = true;
            this.simResult = null;
            try {
                const res = await fetch('{{ route('admin.fuel-rates.resolve') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.simForm)
                });

                const data = await res.json();
                if (data.success) {
                    this.simResult = data.data;
                } else {
                    this.showToast(data.message || 'Error running calculator.', 'error');
                }
            } catch (e) {
                this.showToast('Error running rate inspection.', 'error');
            } finally {
                this.simulating = false;
            }
        },

        formatDate(d) {
            if (!d) return '—';
            const date = new Date(d);
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatSourceLabel(src) {
            if (src === 'user_override') return 'Employee Custom Override (Tier 1)';
            if (src === 'role_default') return 'Role Default Rate (Tier 2)';
            return 'Master Data Base Rate (Tier 3)';
        }
    };
}
</script>
@endsection
