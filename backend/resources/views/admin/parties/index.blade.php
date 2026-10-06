@extends('admin.layouts.app')
@section('title', 'Party Master')
@section('breadcrumb', 'ADMIN / PARTIES')
@section('page_title', 'Party Master')

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
                    <h2 class="text-section-title">Manage Parties</h2>
                    <p class="text-caption mt-1">View, search, filter, and assign parties to field workers.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @can('customers.view')
                        <x-button href="{{ route('admin.parties.export') }}" variant="secondary"
                            class="border-brand-200 text-brand-700 hover:bg-brand-50">
                            <x-lucide-download class="w-4 h-4" />
                            Export CSV
                        </x-button>
                        <x-button href="{{ route('admin.parties.export-pdf') }}" variant="secondary"
                            class="border-brand-200 text-brand-700 hover:bg-brand-50">
                            <x-lucide-file-text class="w-4 h-4" />
                            Export PDF
                        </x-button>
                    @endcan
                    @can('parties.assign')
                        <x-button x-data @click="$dispatch('open-bulk-assign-modal')" variant="secondary"
                            class="border-brand-200 text-brand-700 hover:bg-brand-50">
                            <x-lucide-users class="w-4 h-4" />
                            Bulk Assign
                        </x-button>
                    @endcan
                    @can('customers.create')
                        <x-button href="{{ route('admin.parties.bulk-import') }}" variant="secondary"
                            class="border-brand-200 text-brand-700 hover:bg-brand-50">
                            <x-lucide-upload class="w-4 h-4" />
                            Bulk Import
                        </x-button>
                        <x-button href="{{ route('admin.parties.create') }}" variant="primary">
                            <x-lucide-plus class="w-4 h-4" />
                            Add Party
                        </x-button>
                    @endcan
                </div>
            </div>

            {{-- Filters --}}
            <div id="party-filters" x-data="{
                search: '',
                employeeId: '',
                partyType: '',
                category: '',
                status: '',
                filtersVisible: false
            }" class="mt-4">
                <div class="flex flex-wrap gap-3 items-end">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[200px]">
                        <x-lucide-search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                        <input x-model="search" @input.debounce.500ms="window.partyTable.search(search).draw()"
                            class="form-input pl-9" placeholder="Search name, code, mobile, email, city…">
                    </div>

                    {{-- Toggle Filters Button --}}
                    <x-button type="button" variant="secondary" @click="filtersVisible = !filtersVisible">
                        <x-lucide-filter class="w-4 h-4" />
                        <span x-text="filtersVisible ? 'Hide Filters' : 'Show Filters'"></span>
                    </x-button>

                    {{-- Clear All --}}
                    <x-button type="button" variant="secondary"
                        @click="search=''; employeeId=''; partyType=''; category=''; status=''; window.partyTable.search('').draw()"
                        x-show="search || employeeId || partyType || category || status">
                        <x-lucide-x class="w-4 h-4" />
                        Clear
                    </x-button>
                </div>

                {{-- Advanced Filters --}}
                <div x-show="filtersVisible" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Assigned Employee</label>
                        <select x-model="employeeId" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Employees</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->role_name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Party Type</label>
                        <select x-model="partyType" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Types</option>
                            @foreach ($partyTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Category</label>
                        <select x-model="category" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Status</label>
                        <select x-model="status" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending Approval</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="table-wrap">
            <table id="parties-table" class="data-table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                        </th>
                        <th>Party Info</th>
                        <th>Contact</th>
                        <th>Classification</th>
                        <th>Assigned To</th>
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

    {{-- Bulk Assignment Modal --}}
    <div x-data="{
                                                                        open: false,
                                                                        selectedParties: [],
                                                                        selectedEmployee: '',
                                                                        employees: {{ Js::from($employees->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'role_name' => $e->role_name, 'name_with_role' => $e->name_with_role])) }},
                                                                        init() {
                                                                            this.$watch('open', value => {
                                                                                if (!value) {
                                                                                    this.selectedEmployee = '';
                                                                                }
                                                                            });
                                                                            window.addEventListener('employee-deleted', (e) => {
                                                                                const id = parseInt(e.detail.id);
                                                                                this.employees = this.employees.filter(emp => emp.id !== id);
                                                                            });
                                                                            window.addEventListener('open-bulk-assign-modal', async () => {
                                                                                this.selectedParties = window.getSelectedPartyIds();
                                                                                if (this.selectedParties.length === 0) {
                                                                                    alert('Please select at least one party to assign.');
                                                                                    return;
                                                                                }
                                                                                this.open = true;
                                                                                try {
                                                                                    const res = await fetch('/admin/employees/active-dropdown', {
                                                                                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                                                                    });
                                                                                    const data = await res.json();
                                                                                    if (data.success && data.employees) {
                                                                                        this.employees = data.employees;
                                                                                    }
                                                                                } catch (e) {
                                                                                    console.error('Failed to fetch fresh employees', e);
                                                                                }
                                                                            });
                                                                        },
                                                                        async assignParties() {
                                                                            if (!this.selectedEmployee) {
                                                                                window.toast('Please select an employee.', 'warning');
                                                                                return;
                                                                            }

                                                                            this.loading = true;
                                                                            try {
                                                                                const response = await fetch('{{ route('admin.parties.bulk-assign') }}', {
                                                                                    method: 'POST',
                                                                                    headers: {
                                                                                        'Content-Type': 'application/json',
                                                                                        'Accept': 'application/json',
                                                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                                                    },
                                                                                    body: JSON.stringify({
                                                                                        party_ids: this.selectedParties,
                                                                                        employee_id: this.selectedEmployee
                                                                                    })
                                                                                });

                                                                                const data = await response.json();

                                                                                if (data.success) {
                                                                                    window.partyTable.draw(false);
                                                                                    this.open = false;
                                                                                    window.toast(data.message, 'success');
                                                                                    document.getElementById('select-all').checked = false;
                                                                                } else {
                                                                                    window.toast(data.message || 'Assignment failed.', 'error');
                                                                                }
                                                                            } catch (error) {
                                                                                window.toast('An error occurred. Please try again.', 'error');
                                                                            } finally {
                                                                                this.loading = false;
                                                                            }
                                                                        }
                                                                    }" x-show="open" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="open = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Backdrop --}}
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false"
                class="fixed inset-0 bg-black bg-opacity-50"></div>

            {{-- Modal --}}
            <div x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-ink-900">Bulk Assign Parties</h3>
                    <button @click="open = false" class="text-ink-400 hover:text-ink-600">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <p class="text-sm text-ink-600 mb-4">
                    Assign <span x-text="selectedParties.length" class="font-semibold"></span> selected parties to a field
                    worker.
                </p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-ink-900 mb-2">Select Field Worker</label>
                    <select x-model="selectedEmployee" class="form-input" x-bind:disabled="loading">
                        <option value="">Choose an employee...</option>
                        <template x-for="emp in employees" :key="emp.id">
                            <option :value="emp.id" x-text="emp.name_with_role || `${emp.name} (${emp.role_name || 'Field Worker'})`"></option>
                        </template>
                    </select>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" @click="open = false" x-bind:disabled="loading" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="button" @click="assignParties" x-bind:disabled="loading" class="btn btn-primary">
                        <span x-show="!loading">Assign Parties</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Assigning...
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ AJAX Edit Party Modal ═══ --}}
    <div x-data="{
            open: false,
            loading: false,
            saving: false,
            errors: {},
            empSearch: '',
            form: {
                id: null,
                name: '',
                owner_name: '',
                mobile: '',
                email: '',
                party_type: '',
                category: '',
                credit_limit: '',
                credit_days: '',
                is_active: true,
                city: '',
                state: '',
                pincode: '',
                address: '',
                employee_ids: []
            },
            assignableEmployees: {{ Js::from($assignableEmployees->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'role_name' => $e->role_name, 'name_with_role' => $e->name_with_role])) }},
            init() {
                window.addEventListener('employee-deleted', (e) => {
                    const id = parseInt(e.detail.id);
                    this.assignableEmployees = this.assignableEmployees.filter(emp => emp.id !== id);
                });
                window.addEventListener('open-edit-party-modal', async (e) => {
                    const partyId = e.detail.id;
                    this.errors = {};
                    this.empSearch = '';
                    this.loading = true;
                    this.open = true;
                    try {
                        const res = await fetch(`/admin/parties/${partyId}/edit`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await res.json();
                        if (data.success && data.party) {
                            const p = data.party;
                            if (data.employees) {
                                this.assignableEmployees = data.employees;
                            }
                            this.form = {
                                id: p.id,
                                name: p.name || '',
                                owner_name: p.owner_name || '',
                                mobile: p.mobile || '',
                                email: p.email || '',
                                party_type: p.party_type || '',
                                category: p.category || '',
                                credit_limit: p.credit_limit || '',
                                credit_days: p.credit_days || '',
                                is_active: !!p.is_active,
                                city: p.city || '',
                                state: p.state || '',
                                pincode: p.pincode || '',
                                address: p.address || '',
                                employee_ids: (data.assignedEmployeeIds || []).map(id => parseInt(id))
                            };
                        } else {
                            alert('Failed to load party details.');
                            this.open = false;
                        }
                    } catch (err) {
                        alert('Error loading party details.');
                        this.open = false;
                    } finally {
                        this.loading = false;
                    }
                });
            },
            toggleEmployee(empId) {
                const id = parseInt(empId);
                const idx = this.form.employee_ids.indexOf(id);
                if (idx > -1) {
                    this.form.employee_ids.splice(idx, 1);
                } else {
                    this.form.employee_ids.push(id);
                }
            },
            async saveParty() {
                this.errors = {};
                this.saving = true;
                try {
                    const payload = {
                        ...this.form,
                        credit_days: this.form.credit_days !== '' && this.form.credit_days !== null ? parseInt(this.form.credit_days) : 0,
                        credit_limit: this.form.credit_limit !== '' && this.form.credit_limit !== null ? parseFloat(this.form.credit_limit) : 0
                    };
                    const res = await fetch(`/admin/parties/${this.form.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.open = false;
                        window.partyTable.draw(false);
                        window.toast(data.message || 'Party updated successfully!', 'success');
                    } else if (res.status === 422 && data.errors) {
                        this.errors = data.errors;
                        window.toast(data.message || 'Please fix validation errors.', 'error');
                    } else {
                        window.toast(data.message || 'Failed to update party.', 'error');
                    }
                } catch (err) {
                    window.toast('An error occurred while saving.', 'error');
                } finally {
                    this.saving = false;
                }
            }
        }"
        x-show="open" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false"
                class="fixed inset-0 bg-black bg-opacity-50"></div>

            {{-- Modal --}}
            <div x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 my-8 max-h-[90vh] flex flex-col">
                
                {{-- Header --}}
                <div class="flex items-center justify-between pb-4 border-b border-surface-border">
                    <div>
                        <h3 class="text-lg font-semibold text-ink-900">Edit Party</h3>
                        <p class="text-xs text-ink-500">Update party details & field worker assignment via AJAX</p>
                    </div>
                    <button @click="open = false" class="text-ink-400 hover:text-ink-600">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Loading Skeleton --}}
                <div x-show="loading" class="py-12 flex flex-col items-center justify-center gap-3">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-ink-500">Loading party information...</p>
                </div>

                {{-- Form Body --}}
                <form x-show="!loading" @submit.prevent="saveParty" class="flex-1 overflow-y-auto py-4 space-y-4 pr-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Party Name <span class="text-danger-text">*</span></label>
                            <input type="text" x-model="form.name" class="form-input w-full" placeholder="e.g. Royal Enterprise" required>
                            <template x-if="errors.name">
                                <p class="text-xs text-danger-text mt-1" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        {{-- Mobile --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Mobile <span class="text-danger-text">*</span></label>
                            <input type="text" x-model="form.mobile" class="form-input w-full" placeholder="10-digit mobile number" required>
                            <template x-if="errors.mobile">
                                <p class="text-xs text-danger-text mt-1" x-text="errors.mobile[0]"></p>
                            </template>
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Email</label>
                            <input type="email" x-model="form.email" class="form-input w-full" placeholder="party@example.com">
                            <template x-if="errors.email">
                                <p class="text-xs text-danger-text mt-1" x-text="errors.email[0]"></p>
                            </template>
                        </div>

                        {{-- Owner Name --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Owner / Contact Person</label>
                            <input type="text" x-model="form.owner_name" class="form-input w-full" placeholder="Owner Name">
                        </div>

                        {{-- Party Type --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Party Type</label>
                            <select x-model="form.party_type" class="form-input w-full">
                                <option value="">Select Type</option>
                                @foreach ($partyTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Category --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Category</label>
                            <select x-model="form.category" class="form-input w-full">
                                <option value="">Select Category</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">Category {{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Active Status --}}
                        <div class="flex items-center pt-5">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-ink-800">Active Status</span>
                            </label>
                        </div>

                        {{-- City & State --}}
                        <div>
                            <label class="block text-xs font-semibold text-ink-700 mb-1">City</label>
                            <input type="text" x-model="form.city" class="form-input w-full" placeholder="City">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-ink-700 mb-1">State</label>
                            <input type="text" x-model="form.state" class="form-input w-full" placeholder="State">
                        </div>

                        {{-- Address --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Address</label>
                            <textarea x-model="form.address" rows="2" class="form-input w-full" placeholder="Street / Full Address"></textarea>
                        </div>

                        {{-- Employee Assignment --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-ink-700 mb-1">Assign to Field Workers</label>
                            <input type="text" x-model="empSearch" placeholder="Search field workers..." class="form-input w-full text-xs mb-2">
                            <div class="max-h-36 overflow-y-auto border border-surface-border rounded-lg p-2 space-y-1 bg-surface-muted/30">
                                <template x-for="emp in assignableEmployees" :key="emp.id">
                                    <label x-show="!empSearch || emp.name.toLowerCase().includes(empSearch.toLowerCase())" class="flex items-center gap-2 p-1.5 rounded hover:bg-white text-xs text-ink-800 cursor-pointer">
                                        <input type="checkbox"
                                            :checked="form.employee_ids.includes(emp.id)"
                                            @change="toggleEmployee(emp.id)"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span x-text="emp.name_with_role || `${emp.name} (${emp.role_name || 'Field Worker'})`"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="flex gap-3 justify-end pt-4 border-t border-surface-border mt-4">
                        <button type="button" @click="open = false" class="btn btn-secondary text-sm">Cancel</button>
                        <button type="submit" :disabled="saving" class="btn btn-primary text-sm flex items-center gap-2">
                            <span x-show="!saving">Save Changes</span>
                            <span x-show="saving" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize DataTable
            window.partyTable = new DataTable('#parties-table', {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.parties.data') }}',
                    data: function (d) {
                        const filtersElement = document.getElementById('party-filters');
                        const filters = Alpine.$data(filtersElement);
                        d.search = filters ? filters.search : '';
                        d.employee_id = filters ? filters.employeeId : '';
                        d.party_type = filters ? filters.partyType : '';
                        d.category = filters ? filters.category : '';
                        d.status = filters ? filters.status : '';
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return `<input type="checkbox" class="party-checkbox rounded border-gray-300" value="${row.party_info.id}">`;
                        }
                    },
                    {
                        data: 'party_info',
                        render: function (data) {
                            return `
                                <div class="text-body-emphasis">${data.name}</div>
                                <div class="text-caption">Code: ${data.party_code} • ${data.city}</div>
                                ${data.submitted_by_name ? `
                                    <span class="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-violet-100 text-violet-700">
                                        Added by ${data.submitted_by_name}
                                    </span>
                                ` : ''}
                            `;
                        }
                    },
                    {
                        data: 'contact',
                        render: function (data) {
                            return `
                                <div class="text-body">${data.mobile}</div>
                                <div class="text-caption">${data.email}</div>
                            `;
                        }
                    },
                    {
                        data: 'classification',
                        render: function (data) {
                            return `
                                <div class="text-body">${data.type}</div>
                                <div class="text-caption">Category: ${data.category}</div>
                            `;
                        }
                    },
                    {
                        data: 'assignment',
                        render: function (data) {
                            if (data.employee_name) {
                                return `
                                    <div class="text-body-emphasis">${data.employee_name}</div>
                                    <div class="text-caption">${data.assigned_at}</div>
                                `;
                            }
                            return '<span class="text-caption italic">Not assigned</span>';
                        }
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            if (data === 'active') {
                                return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success-text">Active</span>';
                            } else if (data === 'pending') {
                                return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Pending Approval</span>';
                            } else if (data === 'rejected') {
                                return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>';
                            }
                            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>';
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: function (data) {
                            return `
                                <div class="relative inline-block" x-data="{ open: false }">
                                    <button @click="open = !open" @click.stop class="p-1.5 rounded-lg hover:bg-surface-muted transition-colors">
                                        <svg class="w-4 h-4 text-ink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-1 w-48 bg-white border border-surface-border rounded-xl shadow-lg z-20 py-1 top-full" style="display: none;">
                                        <a href="${data.view}" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-900 hover:bg-surface-muted">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View Profile
                                        </a>
                                        ${data.can_edit ? `
                                        <button type="button" onclick="openEditPartyModal(${data.id || data.party_info?.id})" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-ink-900 hover:bg-surface-muted text-left">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit Details
                                        </button>
                                        ` : ''}
                                        <a href="${data.history}" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-900 hover:bg-surface-muted">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            View History
                                        </a>
                                        ${data.approval_status === 'pending' ? `
                                        <div class="my-1 border-t border-surface-border"></div>
                                        <button type="button" onclick="approveParty('${data.approve}')" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-green-700 hover:bg-green-50">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Approve Party
                                        </button>
                                        <button type="button" onclick="rejectParty('${data.reject}')" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-danger-text hover:bg-danger-bg">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Reject Party
                                        </button>
                                        ` : ''}
                                        ${data.can_delete ? `
                                        <div class="my-1 border-t border-surface-border"></div>
                                        <button type="button" onclick="deleteParty('${data.delete}')" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-danger-text hover:bg-danger-bg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete Party
                                        </button>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[1, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                dom: 'rt<"flex items-center justify-between px-6 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>'
            });

            // Select all checkbox
            document.getElementById('select-all').addEventListener('change', function () {
                document.querySelectorAll('.party-checkbox').forEach(cb => {
                    cb.checked = this.checked;
                });
            });

            // Helper function to get selected party IDs
            window.getSelectedPartyIds = function () {
                return Array.from(document.querySelectorAll('.party-checkbox:checked')).map(cb => parseInt(cb.value));
            };

            window.openEditPartyModal = function (id) {
                window.dispatchEvent(new CustomEvent('open-edit-party-modal', { detail: { id } }));
            };

            window.deleteParty = async function (url) {
                if (!confirm('Are you sure you want to delete this party? This action cannot be undone.')) return;
                
                try {
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.toast(data.message || 'Party deleted successfully.', 'success');
                        window.partyTable.draw(false);
                    } else {
                        window.toast(data.message || 'Failed to delete party.', 'error');
                    }
                } catch (error) {
                    window.toast('An error occurred while deleting the party.', 'error');
                }
            };

            window.approveParty = async function (url) {
                if (!confirm('Are you sure you want to approve this party?')) return;
                
                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.toast(data.message || 'Party approved successfully.', 'success');
                        window.partyTable.draw(false);
                    } else {
                        window.toast(data.message || 'Failed to approve party.', 'error');
                    }
                } catch (error) {
                    window.toast('An error occurred. Please try again.', 'error');
                }
            };

            window.rejectParty = async function (url) {
                if (!confirm('Are you sure you want to reject this party?')) return;
                
                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.toast(data.message || 'Party rejected successfully.', 'warning');
                        window.partyTable.draw(false);
                    } else {
                        window.toast(data.message || 'Failed to reject party.', 'error');
                    }
                } catch (error) {
                    window.toast('An error occurred. Please try again.', 'error');
                }
            };
        });
    </script>
@endpush