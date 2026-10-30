@extends('admin.layouts.app')

@section('title', 'Employees')
@section('breadcrumb', 'ADMIN / EMPLOYEES')
@section('page_title', 'Employee Management')

@section('content')
    <div x-data="employeeManager({
            csrfToken: '{{ csrf_token() }}',
            allCustomers: {{ Js::from($customers) }},
            allRoles: {{ Js::from($roles) }},
            allManagers: {{ Js::from($managers) }}
        })" class="space-y-4">

        {{-- Main Panel --}}
        <div class="panel">
            {{-- Header --}}
            <div class="panel-header">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-section-title">All Employees</h2>
                        <p class="text-caption mt-1">View, search, and manage all field workers and staff via AJAX.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @can('reports.view')
                            <x-button href="{{ route('admin.reports.export-csv') }}" variant="secondary"
                                class="border-surface-border text-ink-700 hover:bg-surface-muted">
                                <x-lucide-download class="w-4 h-4" />
                                Export CSV
                            </x-button>
                        @endcan
                        @can('employees.create')
                            <button type="button" @click="openCreateModal()" class="btn-primary">
                                <x-lucide-plus class="w-4 h-4" />
                                Create Employee
                            </button>
                        @endcan
                    </div>
                </div>

                {{-- Filters --}}
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="relative flex-1 max-w-sm">
                        <x-lucide-search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                        <input type="text" x-model="filters.search" @input.debounce.200ms="applyFilters()" id="dt-search"
                            class="form-input pl-9" placeholder="Search name, email, ID, phone…">
                    </div>
                    <select x-model="filters.role" @change="applyFilters()" id="roleFilter" class="form-input w-auto">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                    <select x-model="filters.status" @change="applyFilters()" id="statusFilter" class="form-input w-auto">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                    <button type="button" @click="resetFilters()" id="resetFilters" class="btn-secondary">Reset</button>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-wrap">
                <table id="employeesTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Contact</th>
                            <th>Position</th>
                            <th>Reporting To</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Hired</th>
                            @canany(['employees.edit', 'employees.delete'])
                                <th class="text-right">Actions</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr id="employee-row-{{ $employee->id }}" data-id="{{ $employee->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="relative shrink-0">
                                            <img id="avatar-img-{{ $employee->id }}" src="{{ $employee->profile_image_url }}"
                                                alt="{{ $employee->name }}"
                                                class="{{ $employee->profile_image_url ? '' : 'hidden' }} h-9 w-9 rounded-lg object-cover shadow-sm border border-surface-border">
                                            <span id="avatar-{{ $employee->id }}"
                                                class="{{ $employee->profile_image_url ? 'hidden' : 'flex' }} h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-ink-900 text-sm font-bold text-white shadow-sm">
                                                {{ strtoupper(substr($employee->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div id="name-{{ $employee->id }}"
                                                class="text-body-emphasis font-bold text-ink-900">{{ $employee->name }}</div>
                                            <div id="code-{{ $employee->id }}" class="text-caption text-ink-500">
                                                {{ $employee->employee_id ?? '#' . $employee->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div id="email-{{ $employee->id }}" class="text-body text-ink-800">{{ $employee->email }}
                                    </div>
                                    <div id="phone-{{ $employee->id }}" class="text-caption text-ink-500">
                                        {{ $employee->phone ?? '—' }}</div>
                                </td>
                                <td>
                                    <span id="pos-{{ $employee->id }}"
                                        class="text-body font-medium text-ink-700">{{ $employee->position ?? '—' }}</span>
                                </td>
                                <td>
                                    <span id="manager-{{ $employee->id }}"
                                        class="text-body font-medium text-ink-700">{{ $employee->reportingManager?->name_with_role ?? '—' }}</span>
                                </td>
                                <td>
                                    <div id="roles-{{ $employee->id }}" class="flex flex-wrap gap-1">
                                        @forelse ($employee->roles as $role)
                                            <span class="badge badge-info">{{ $role->name }}</span>
                                        @empty
                                            <span class="badge badge-neutral">No role</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span id="status-badge-{{ $employee->id }}">
                                        @if($employee->is_active)
                                            <span class="badge badge-success inline-flex items-center gap-1"><x-lucide-check-circle
                                                    class="w-3 h-3" /> Active</span>
                                        @else
                                            <span class="badge badge-danger inline-flex items-center gap-1"><x-lucide-x-circle
                                                    class="w-3 h-3" /> Inactive</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="text-caption text-ink-500">
                                    {{ $employee->hire_date ? $employee->hire_date->format('d M Y') : '—' }}
                                </td>

                                @canany(['employees.edit', 'employees.delete'])
                                    <td class="text-right">
                                        <div x-data="{ open: false }" class="relative inline-block text-left">
                                            <button @click="open = !open" @click.stop
                                                class="p-1.5 rounded-lg hover:bg-surface-muted transition-colors text-ink-600 hover:text-ink-900"
                                                title="Actions">
                                                <x-lucide-more-vertical class="w-4 h-4" />
                                            </button>

                                            <div x-show="open" @click.outside="open = false" x-cloak style="display: none;"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute right-0 mt-1 w-44 bg-white border border-surface-border rounded-xl shadow-xl z-30 py-1 text-left">

                                                {{-- View Profile --}}
                                                @can('employees.view')
                                                    <a href="{{ route('admin.employees.profile', $employee->id) }}"
                                                        class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-ink-700 hover:bg-surface-muted hover:text-ink-900 transition-colors">
                                                        <x-lucide-eye class="w-3.5 h-3.5 text-ink-500 shrink-0" />
                                                        View Profile
                                                    </a>
                                                @endcan

                                                {{-- Quick Edit AJAX --}}
                                                @can('employees.edit')
                                                    <button type="button" @click="open = false; openEditModal({{ $employee->id }})"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-ink-700 hover:bg-surface-muted hover:text-ink-900 transition-colors">
                                                        <x-lucide-pencil class="w-3.5 h-3.5 text-ink-500 shrink-0" />
                                                        Edit Details
                                                    </button>
                                                @endcan

                                                {{-- Full Edit & Territories --}}
                                                @can('employees.edit')
                                                    <a href="{{ route('admin.employees.edit', $employee->id) }}"
                                                        class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50 transition-colors">
                                                        <x-lucide-map-pin class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                                                        Territories & Full Edit
                                                    </a>
                                                @endcan

                                                {{-- Manage Parties AJAX --}}
                                                @can('employees.edit')
                                                    <button type="button"
                                                        @click="open = false; openPartiesModal({{ $employee->id }}, '{{ addslashes($employee->name) }}', '{{ $employee->employee_id ?? '#' . $employee->id }}')"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-ink-700 hover:bg-surface-muted hover:text-ink-900 transition-colors">
                                                        <x-lucide-building class="w-3.5 h-3.5 text-ink-500 shrink-0" />
                                                        Parties
                                                    </button>
                                                @endcan

                                                {{-- Toggle Status AJAX --}}
                                                @can('employees.edit')
                                                    <button type="button" id="toggle-btn-{{ $employee->id }}"
                                                        @click="open = false; toggleStatus({{ $employee->id }}, '{{ addslashes($employee->name) }}')"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold transition-colors {{ $employee->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                                                        <x-lucide-power class="w-3.5 h-3.5 shrink-0" />
                                                        <span
                                                            id="toggle-label-{{ $employee->id }}">{{ $employee->is_active ? 'Deactivate' : 'Activate' }}</span>
                                                    </button>
                                                @endcan

                                                {{-- Delete Employee AJAX --}}
                                                @if(auth()->user()->can('employees.delete') && auth()->id() !== $employee->id)
                                                    <div class="my-1 border-t border-surface-border"></div>
                                                    <button type="button"
                                                        @click="open = false; deleteEmployee({{ $employee->id }}, '{{ addslashes($employee->name) }}')"
                                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                                                        <x-lucide-trash-2 class="w-3.5 h-3.5 shrink-0" />
                                                        Delete Employee
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @endcanany
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL: MANAGE PARTIES (AJAX) --}}
        {{-- ========================================================================= --}}
        <div x-show="partiesModal.open" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="partiesModal.open = false">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                {{-- Backdrop --}}
                <div x-show="partiesModal.open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" @click="partiesModal.open = false"
                    class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

                {{-- Dialog --}}
                <div x-show="partiesModal.open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 my-8 max-h-[90vh] flex flex-col border border-surface-border">

                    {{-- Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-surface-border">
                        <div>
                            <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                                <x-lucide-building class="w-5 h-5 text-indigo-600" />
                                Assign Parties to Field Worker
                            </h3>
                            <p class="text-xs text-ink-500 mt-0.5">
                                Assigning to: <span class="font-bold text-ink-900"
                                    x-text="partiesModal.employeeName"></span>
                                <span class="text-ink-400" x-text="'(' + partiesModal.employeeCode + ')'"></span>
                            </p>
                        </div>
                        <button @click="partiesModal.open = false" class="text-ink-400 hover:text-ink-700">
                            <x-lucide-x class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Loading Spinner --}}
                    <div x-show="partiesModal.loading" class="py-16 flex flex-col items-center justify-center gap-3">
                        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <p class="text-xs font-semibold text-ink-600">Loading assigned parties...</p>
                    </div>

                    {{-- Body --}}
                    <div x-show="!partiesModal.loading" class="flex-1 overflow-hidden flex flex-col py-4 gap-3">
                        {{-- Search & Selection toolbar --}}
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                            <div class="relative w-full sm:w-72">
                                <x-lucide-search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                                <input type="text" x-model="partiesModal.search" placeholder="Filter parties..."
                                    class="form-input text-xs pl-9 w-full">
                            </div>
                            <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
                                <button type="button" @click="selectAllFilteredParties()"
                                    class="btn-secondary text-xs py-1.5 px-2.5">
                                    Select All Filtered
                                </button>
                                <button type="button" @click="clearPartiesSelection()"
                                    class="btn-secondary text-xs py-1.5 px-2.5">
                                    Clear
                                </button>
                            </div>
                        </div>

                        {{-- Counter Pill --}}
                        <div class="flex items-center justify-between bg-surface-muted px-3 py-2 rounded-lg text-xs">
                            <span class="font-medium text-ink-600">Selected Parties:</span>
                            <span class="font-bold text-indigo-600"
                                x-text="partiesModal.selectedIds.length + ' assigned to this employee'"></span>
                        </div>

                        {{-- Party List Checkboxes --}}
                        <div
                            class="flex-1 overflow-y-auto border border-surface-border rounded-xl p-3 divide-y divide-surface-border/50 max-h-72">
                            <template x-for="cust in filteredCustomers()" :key="cust.id">
                                <label class="flex items-center justify-between py-2.5 px-2 rounded-lg transition"
                                    :class="cust.is_assigned_to_other ? 'bg-slate-50/80 opacity-75 cursor-not-allowed' : 'hover:bg-slate-50 cursor-pointer'">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" :value="cust.id" :disabled="cust.is_assigned_to_other"
                                            :checked="partiesModal.selectedIds.includes(cust.id)"
                                            @change="togglePartyId(cust.id)"
                                            class="rounded border-ink-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 disabled:opacity-40 disabled:cursor-not-allowed">
                                        <div>
                                            <div class="text-xs font-bold text-ink-900" x-text="cust.name"></div>
                                            <div class="text-[11px] text-ink-400"
                                                x-text="cust.city ? cust.city : 'ID: #' + cust.id"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <template x-if="partiesModal.selectedIds.includes(cust.id)">
                                            <span
                                                class="text-[10px] leading-tight font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">
                                                ✓ Assigned
                                            </span>
                                        </template>
                                        <template x-if="cust.is_assigned_to_other">
                                            <span
                                                class="text-[10px] leading-tight font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded"
                                                x-text="'Assigned to ' + cust.assigned_to_name">
                                            </span>
                                        </template>
                                        <template
                                            x-if="!partiesModal.selectedIds.includes(cust.id) && !cust.is_assigned_to_other">
                                            <span
                                                class="text-[10px] leading-tight font-medium text-ink-500 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded">
                                                Available
                                            </span>
                                        </template>
                                    </div>
                                </label>
                            </template>

                            <div x-show="filteredCustomers().length === 0" class="py-8 text-center text-xs text-ink-400">
                                No parties match your search query.
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-border">
                        <button type="button" @click="partiesModal.open = false" :disabled="partiesModal.saving"
                            class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="button" @click="saveAssignedParties()" :disabled="partiesModal.saving"
                            class="btn-primary text-xs inline-flex items-center gap-1.5"
                            style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="partiesModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="partiesModal.saving ? 'Saving...' : 'Save Assigned Parties'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL: EDIT EMPLOYEE (AJAX) --}}
        {{-- ========================================================================= --}}
        <div x-show="editModal.open" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="editModal.open = false">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                {{-- Backdrop --}}
                <div x-show="editModal.open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    @click="editModal.open = false" class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

                {{-- Dialog --}}
                <div x-show="editModal.open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 my-8 max-h-[92vh] flex flex-col border border-surface-border">

                    {{-- Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-surface-border">
                        <div>
                            <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                                <x-lucide-pencil class="w-5 h-5 text-indigo-600" />
                                Edit Employee Details
                            </h3>
                            <p class="text-xs text-ink-500 mt-0.5">
                                Update profile, role, position, and status via AJAX.
                            </p>
                        </div>
                        <button @click="editModal.open = false" class="text-ink-400 hover:text-ink-700">
                            <x-lucide-x class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Loading Skeleton --}}
                    <div x-show="editModal.loading" class="py-16 flex flex-col items-center justify-center gap-3">
                        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <p class="text-xs font-semibold text-ink-600">Loading employee details...</p>
                    </div>

                    {{-- Form Body --}}
                    <form x-show="!editModal.loading" @submit.prevent="saveEmployeeEdit()"
                        class="flex-1 overflow-y-auto py-4 space-y-5 pr-1">
                        {{-- Profile Photo Upload & Preview --}}
                        <div
                            class="flex items-center gap-4 p-3 rounded-xl bg-surface-ground/50 border border-surface-border">
                            <div class="relative group shrink-0">
                                <template x-if="editModal.photoPreview">
                                    <img :src="editModal.photoPreview" alt="Profile Preview"
                                        class="w-16 h-16 rounded-full object-cover shadow-sm border-2 border-indigo-500">
                                </template>
                                <template x-if="!editModal.photoPreview">
                                    <div
                                        class="w-16 h-16 rounded-full bg-surface-card border-2 border-dashed border-ink-300 flex flex-col items-center justify-center text-ink-400 group-hover:border-indigo-500 group-hover:text-indigo-600 transition-colors">
                                        <x-lucide-camera class="w-5 h-5" />
                                        <span class="text-[9px] mt-0.5 font-medium">Photo</span>
                                    </div>
                                </template>
                            </div>
                            <div class="flex-1">
                                <label class="form-label text-xs block mb-0.5">Profile Photo</label>
                                <div class="flex items-center gap-2">
                                    <label
                                        class="btn btn-secondary text-xs cursor-pointer inline-flex items-center gap-1.5 py-1 px-2.5">
                                        <x-lucide-upload class="w-3.5 h-3.5" />
                                        <span x-text="editModal.photoPreview ? 'Change' : 'Upload'"></span>
                                        <input type="file" accept="image/*" class="hidden"
                                            @change="handleEditPhotoChange($event)">
                                    </label>
                                    <button type="button" x-show="editModal.photoPreview" @click="removeEditPhoto()"
                                        class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-2 py-1">
                                        Remove
                                    </button>
                                </div>
                                <p class="text-[10px] text-ink-400 mt-0.5">JPG, PNG, or WEBP up to 5MB.</p>
                                <template x-if="editModal.errors.profile_image">
                                    <p class="mt-1 text-xs text-rose-600" x-text="editModal.errors.profile_image[0]"></p>
                                </template>
                            </div>
                        </div>

                        {{-- Basic Info Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Name --}}
                            <div>
                                <label class="form-label text-xs">Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="editModal.form.name" required placeholder="Full Name"
                                    class="form-input text-xs w-full"
                                    :class="editModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                                <template x-if="editModal.errors.name">
                                    <p class="mt-1 text-xs text-rose-600" x-text="editModal.errors.name[0]"></p>
                                </template>
                            </div>

                            {{-- Employee ID --}}
                            <div>
                                <label class="form-label text-xs">Employee ID</label>
                                <input type="text" x-model="editModal.form.employee_id" placeholder="e.g. EMP001"
                                    class="form-input text-xs w-full"
                                    :class="editModal.errors.employee_id ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                                <template x-if="editModal.errors.employee_id">
                                    <p class="mt-1 text-xs text-rose-600" x-text="editModal.errors.employee_id[0]"></p>
                                </template>
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="form-label text-xs">Email Address <span class="text-rose-500">*</span></label>
                                <input type="email" x-model="editModal.form.email" required placeholder="email@company.com"
                                    class="form-input text-xs w-full"
                                    :class="editModal.errors.email ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                                <template x-if="editModal.errors.email">
                                    <p class="mt-1 text-xs text-rose-600" x-text="editModal.errors.email[0]"></p>
                                </template>
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label class="form-label text-xs">Phone Number</label>
                                <input type="text" x-model="editModal.form.phone" placeholder="+91 9876543210"
                                    class="form-input text-xs w-full">
                                <template x-if="editModal.errors.phone">
                                    <p class="mt-1 text-xs text-rose-600" x-text="editModal.errors.phone[0]"></p>
                                </template>
                            </div>

                            {{-- Position --}}
                            <div>
                                <label class="form-label text-xs">Position / Designation</label>
                                <input type="text" x-model="editModal.form.position" placeholder="e.g. Field Sales Officer"
                                    class="form-input text-xs w-full">
                            </div>

                            {{-- Department --}}
                            <div>
                                <label class="form-label text-xs">Department</label>
                                <input type="text" x-model="editModal.form.department" placeholder="e.g. Sales & Marketing"
                                    class="form-input text-xs w-full">
                            </div>

                            {{-- Salary --}}
                            <div>
                                <label class="form-label text-xs">Salary</label>
                                <input type="number" step="0.01" x-model="editModal.form.salary" placeholder="0.00"
                                    class="form-input text-xs w-full">
                            </div>

                            {{-- Reporting To --}}
                            <div>
                                <label class="form-label text-xs">Reporting Manager</label>
                                <select x-model="editModal.form.reporting_to" class="form-input text-xs w-full">
                                    <option value="">None (Independent)</option>
                                    <template x-for="m in allManagers" :key="m.id">
                                        <option :value="m.id" :selected="m.id == editModal.form.reporting_to"
                                            x-text="m.name + ' (' + (m.role_name || 'Manager') + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Password (Optional) --}}
                            <div class="md:col-span-2">
                                <label class="form-label text-xs">New Password (leave blank to keep current)</label>
                                <input type="password" x-model="editModal.form.password" placeholder="••••••••"
                                    class="form-input text-xs w-full">
                                <template x-if="editModal.errors.password">
                                    <p class="mt-1 text-xs text-rose-600" x-text="editModal.errors.password[0]"></p>
                                </template>
                            </div>
                        </div>

                        {{-- Single Role Select --}}
                        <div>
                            <label class="form-label text-xs font-bold text-ink-800 mb-1.5 block">Role <span
                                    class="text-rose-500">*</span></label>
                            @if(auth()->user()->can('employees.assign-roles') || auth()->user()->hasRole('Super Admin'))
                                <select x-model="editModal.form.role" class="form-input text-xs w-full">
                                    <option value="">Select Role</option>
                                    <template x-for="role in allRoles" :key="role.id">
                                        <option :value="role.name"
                                            x-text="role.name.charAt(0).toUpperCase() + role.name.slice(1)"></option>
                                    </template>
                                </select>
                            @else
                                <div
                                    class="px-3 py-2 bg-slate-100 rounded-lg text-xs font-semibold text-slate-700 w-full border border-slate-200 flex items-center justify-between">
                                    <span x-text="editModal.form.role || 'No Role'"></span>
                                    <span class="text-[10px] text-slate-400 font-normal">Locked (requires permission)</span>
                                </div>
                            @endif
                            <template x-if="editModal.errors.role || editModal.errors.roles">
                                <p class="mt-1 text-xs text-rose-600 font-semibold"
                                    x-text="(editModal.errors.role || editModal.errors.roles)[0]"></p>
                            </template>
                        </div>

                        {{-- Status Toggle --}}
                        <div
                            class="flex items-center justify-between p-3 bg-slate-50 border border-surface-border rounded-xl">
                            <div>
                                <div class="text-xs font-bold text-ink-900">Active Account Status</div>
                                <div class="text-[11px] text-ink-500">Allow this employee to log in and use mobile/web app.
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="editModal.form.is_active" class="sr-only peer">
                                <div
                                    class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600">
                                </div>
                            </label>
                        </div>

                        {{-- Territory Assignment Link --}}
                        <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-lucide-map-pin class="w-4 h-4 text-indigo-600 shrink-0" />
                                <span class="text-xs text-indigo-900 font-semibold">Assign Districts & Areas / Beats:</span>
                            </div>
                            <a :href="'/admin/employees/' + editModal.id + '/edit'" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline inline-flex items-center gap-1">
                                Open Territory Manager →
                            </a>
                        </div>

                        {{-- Form Footer --}}
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-surface-border">
                            <button type="button" @click="editModal.open = false" :disabled="editModal.saving"
                                class="btn-secondary text-xs">
                                Cancel
                            </button>
                            <button type="submit" :disabled="editModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                                <svg x-show="editModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="editModal.saving ? 'Saving...' : 'Update Employee'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL: CREATE EMPLOYEE (AJAX) --}}
        {{-- ========================================================================= --}}
        <div x-show="createModal.open" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="createModal.open = false">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                {{-- Backdrop --}}
                <div x-show="createModal.open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" @click="createModal.open = false"
                    class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

                {{-- Dialog --}}
                <div x-show="createModal.open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 my-8 max-h-[92vh] flex flex-col border border-surface-border">

                    {{-- Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-surface-border">
                        <div>
                            <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                                <x-lucide-plus-circle class="w-5 h-5 text-indigo-600" />
                                Create New Employee
                            </h3>
                            <p class="text-xs text-ink-500 mt-0.5">
                                Add a new employee and assign roles/parties via AJAX.
                            </p>
                        </div>
                        <button @click="createModal.open = false" class="text-ink-400 hover:text-ink-700">
                            <x-lucide-x class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Form Body --}}
                    <form @submit.prevent="saveEmployeeCreate()" class="flex-1 overflow-y-auto py-4 space-y-5 pr-1">
                        {{-- Profile Photo Upload & Preview --}}
                        <div
                            class="flex items-center gap-4 p-3 rounded-xl bg-surface-ground/50 border border-surface-border">
                            <div class="relative group shrink-0">
                                <template x-if="createModal.photoPreview">
                                    <img :src="createModal.photoPreview" alt="Profile Preview"
                                        class="w-16 h-16 rounded-full object-cover shadow-sm border-2 border-indigo-500">
                                </template>
                                <template x-if="!createModal.photoPreview">
                                    <div
                                        class="w-16 h-16 rounded-full bg-surface-card border-2 border-dashed border-ink-300 flex flex-col items-center justify-center text-ink-400 group-hover:border-indigo-500 group-hover:text-indigo-600 transition-colors">
                                        <x-lucide-camera class="w-5 h-5" />
                                        <span class="text-[9px] mt-0.5 font-medium">Photo</span>
                                    </div>
                                </template>
                            </div>
                            <div class="flex-1">
                                <label class="form-label text-xs block mb-0.5">Profile Photo</label>
                                <div class="flex items-center gap-2">
                                    <label
                                        class="btn btn-secondary text-xs cursor-pointer inline-flex items-center gap-1.5 py-1 px-2.5">
                                        <x-lucide-upload class="w-3.5 h-3.5" />
                                        <span x-text="createModal.photoPreview ? 'Change' : 'Upload'"></span>
                                        <input type="file" accept="image/*" class="hidden"
                                            @change="handleCreatePhotoChange($event)">
                                    </label>
                                    <button type="button" x-show="createModal.photoPreview" @click="removeCreatePhoto()"
                                        class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-2 py-1">
                                        Remove
                                    </button>
                                </div>
                                <p class="text-[10px] text-ink-400 mt-0.5">JPG, PNG, or WEBP up to 5MB.</p>
                                <template x-if="createModal.errors.profile_image">
                                    <p class="mt-1 text-xs text-rose-600" x-text="createModal.errors.profile_image[0]"></p>
                                </template>
                            </div>
                        </div>

                        {{-- Basic Info Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Name --}}
                            <div>
                                <label class="form-label text-xs">Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="createModal.form.name" required placeholder="e.g. Rahul Sharma"
                                    class="form-input text-xs w-full"
                                    :class="createModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                                <template x-if="createModal.errors.name">
                                    <p class="mt-1 text-xs text-rose-600" x-text="createModal.errors.name[0]"></p>
                                </template>
                            </div>

                            {{-- Employee ID --}}
                            <div>
                                <label class="form-label text-xs">Employee ID</label>
                                <input type="text" x-model="createModal.form.employee_id" placeholder="e.g. EMP001"
                                    class="form-input text-xs w-full"
                                    :class="createModal.errors.employee_id ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                                <template x-if="createModal.errors.employee_id">
                                    <p class="mt-1 text-xs text-rose-600" x-text="createModal.errors.employee_id[0]"></p>
                                </template>
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="form-label text-xs">Email Address <span class="text-rose-500">*</span></label>
                                <input type="email" x-model="createModal.form.email" required
                                    placeholder="email@company.com" class="form-input text-xs w-full"
                                    :class="createModal.errors.email ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                                <template x-if="createModal.errors.email">
                                    <p class="mt-1 text-xs text-rose-600" x-text="createModal.errors.email[0]"></p>
                                </template>
                            </div>

                            {{-- Password --}}
                            <div>
                                <label class="form-label text-xs">Password <span class="text-rose-500">*</span></label>
                                <input type="password" x-model="createModal.form.password" required
                                    placeholder="Minimum 8 characters" class="form-input text-xs w-full"
                                    :class="createModal.errors.password ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                                <template x-if="createModal.errors.password">
                                    <p class="mt-1 text-xs text-rose-600" x-text="createModal.errors.password[0]"></p>
                                </template>
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label class="form-label text-xs">Phone Number</label>
                                <input type="text" x-model="createModal.form.phone" placeholder="+91 9876543210"
                                    class="form-input text-xs w-full">
                                <template x-if="createModal.errors.phone">
                                    <p class="mt-1 text-xs text-rose-600" x-text="createModal.errors.phone[0]"></p>
                                </template>
                            </div>

                            {{-- Position --}}
                            <div>
                                <label class="form-label text-xs">Position / Designation</label>
                                <input type="text" x-model="createModal.form.position" placeholder="e.g. Sales Executive"
                                    class="form-input text-xs w-full">
                            </div>

                            {{-- Department --}}
                            <div>
                                <label class="form-label text-xs">Department</label>
                                <input type="text" x-model="createModal.form.department" placeholder="e.g. Field Operations"
                                    class="form-input text-xs w-full">
                            </div>

                            {{-- Reporting To --}}
                            <div>
                                <label class="form-label text-xs">Reporting Manager</label>
                                <select x-model="createModal.form.reporting_to" class="form-input text-xs w-full">
                                    <option value="">None (Independent)</option>
                                    <template x-for="m in allManagers" :key="m.id">
                                        <option :value="m.id" x-text="m.name + ' (' + (m.role_name || 'Manager') + ')'">
                                        </option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Single Role Select --}}
                        <div>
                            <label class="form-label text-xs font-bold text-ink-800 mb-1.5 block">Role <span
                                    class="text-rose-500">*</span></label>
                            @if(auth()->user()->can('employees.assign-roles') || auth()->user()->hasRole('Super Admin'))
                                <select x-model="createModal.form.role" class="form-input text-xs w-full">
                                    <option value="">Select Role</option>
                                    <template x-for="role in allRoles" :key="role.id">
                                        <option :value="role.name"
                                            x-text="role.name.charAt(0).toUpperCase() + role.name.slice(1)"></option>
                                    </template>
                                </select>
                            @else
                                <div
                                    class="px-3 py-2 bg-slate-100 rounded-lg text-xs font-semibold text-slate-700 w-full border border-slate-200 flex items-center justify-between">
                                    <span>Field Worker</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Default role</span>
                                </div>
                            @endif
                            <template x-if="createModal.errors.role || createModal.errors.roles">
                                <p class="mt-1 text-xs text-rose-600 font-semibold"
                                    x-text="(createModal.errors.role || createModal.errors.roles)[0]"></p>
                            </template>
                        </div>

                        {{-- Status Toggle --}}
                        <div
                            class="flex items-center justify-between p-3 bg-slate-50 border border-surface-border rounded-xl">
                            <div>
                                <div class="text-xs font-bold text-ink-900">Active Account Status</div>
                                <div class="text-[11px] text-ink-500">Enable account immediately on creation.</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="createModal.form.is_active" class="sr-only peer">
                                <div
                                    class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600">
                                </div>
                            </label>
                        </div>

                        {{-- Form Footer --}}
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-surface-border">
                            <button type="button" @click="createModal.open = false" :disabled="createModal.saving"
                                class="btn-secondary text-xs">
                                Cancel
                            </button>
                            <button type="submit" :disabled="createModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                                <svg x-show="createModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="createModal.saving ? 'Creating...' : 'Create Employee'"></span>
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
        function employeeManager(config) {
            return {
                csrfToken: config.csrfToken,
                allCustomers: config.allCustomers || [],
                allRoles: config.allRoles || [],
                allManagers: config.allManagers || [],

                filters: {
                    search: '',
                    role: '',
                    status: '',
                },

                toast: {
                    show(msg, type = 'success') {
                        if (window.toast) {
                            window.toast(msg, type);
                        }
                    }
                },

                // Manage Parties State
                partiesModal: {
                    open: false,
                    loading: false,
                    saving: false,
                    employeeId: null,
                    employeeName: '',
                    employeeCode: '',
                    search: '',
                    selectedIds: [],
                    partiesList: [],
                },

                // Edit Employee State
                editModal: {
                    open: false,
                    loading: false,
                    saving: false,
                    employeeId: null,
                    photoFile: null,
                    photoPreview: null,
                    removePhoto: false,
                    form: {
                        name: '',
                        email: '',
                        phone: '',
                        employee_id: '',
                        position: '',
                        department: '',
                        salary: '',
                        reporting_to: '',
                        password: '',
                        is_active: true,
                        role: '',
                        roles: [],
                    },
                    errors: {},
                },

                // Create Employee State
                createModal: {
                    open: false,
                    saving: false,
                    photoFile: null,
                    photoPreview: null,
                    form: {
                        name: '',
                        email: '',
                        phone: '',
                        employee_id: '',
                        position: '',
                        department: '',
                        salary: '',
                        reporting_to: '',
                        password: '',
                        is_active: true,
                        role: '',
                        roles: [],
                    },
                    errors: {},
                },

                init() {
                    this.$nextTick(() => {
                        this.initDataTable();
                    });
                },

                initDataTable() {
                    if (window.employeesTable) {
                        try {
                            window.employeesTable.destroy();
                        } catch (e) { }
                    }

                    if (typeof DataTable !== 'undefined') {
                        window.employeesTable = new DataTable('#employeesTable', {
                            pageLength: 25,
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                            order: [],
                            language: {
                                emptyTable: 'No employees found.',
                                zeroRecords: 'No matching employees.',
                            },
                            columnDefs: [{ targets: -1, orderable: false, searchable: false }],
                            dom: '<"flex items-center justify-between px-6 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>rt',
                        });
                    }
                },

                applyFilters() {
                    if (!window.employeesTable) return;

                    // Global search
                    const q = this.filters.search ? this.filters.search.trim() : '';
                    window.employeesTable.search(q);

                    // Role search on Column 4
                    if (this.filters.role) {
                        window.employeesTable.column(4).search(this.filters.role, false, false);
                    } else {
                        window.employeesTable.column(4).search('');
                    }

                    // Status search on Column 5 (exact match so 'Active' does not match 'Inactive')
                    if (this.filters.status === 'Active') {
                        window.employeesTable.column(5).search('(^|\\s)Active(\\s|$)', true, false);
                    } else if (this.filters.status === 'Inactive') {
                        window.employeesTable.column(5).search('(^|\\s)Inactive(\\s|$)', true, false);
                    } else {
                        window.employeesTable.column(5).search('');
                    }

                    window.employeesTable.draw();
                },

                resetFilters() {
                    this.filters.search = '';
                    this.filters.role = '';
                    this.filters.status = '';
                    this.applyFilters();
                },

                // =========================================================================
                // PARTIES ASSIGNMENT (AJAX)
                // =========================================================================
                async openPartiesModal(empId, empName, empCode) {
                    this.partiesModal.employeeId = empId;
                    this.partiesModal.employeeName = empName;
                    this.partiesModal.employeeCode = empCode;
                    this.partiesModal.search = '';
                    this.partiesModal.selectedIds = [];
                    this.partiesModal.partiesList = [];
                    this.partiesModal.loading = true;
                    this.partiesModal.open = true;

                    try {
                        const res = await fetch(`/admin/employees/${empId}/parties`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.partiesModal.selectedIds = (data.assigned_party_ids || []).map(id => parseInt(id));
                            this.partiesModal.partiesList = data.parties || [];
                        } else {
                            this.toast.show(data.message || 'Failed to load parties.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        this.toast.show('Network error while loading parties.', 'error');
                    } finally {
                        this.partiesModal.loading = false;
                    }
                },

                filteredCustomers() {
                    const list = this.partiesModal.partiesList.length > 0 ? this.partiesModal.partiesList : this.allCustomers;
                    const q = this.partiesModal.search.toLowerCase().trim();
                    if (!q) return list;
                    return list.filter(c => c.name.toLowerCase().includes(q) || (c.city && c.city.toLowerCase().includes(q)) || String(c.id).includes(q));
                },

                togglePartyId(id) {
                    const numId = parseInt(id);
                    const party = this.partiesModal.partiesList.find(c => c.id === numId);
                    if (party && party.is_assigned_to_other) {
                        this.toast.show(`Cannot select '${party.name}'. It is already assigned to ${party.assigned_to_name}.`, 'warning');
                        return;
                    }
                    const idx = this.partiesModal.selectedIds.indexOf(numId);
                    if (idx > -1) {
                        this.partiesModal.selectedIds.splice(idx, 1);
                    } else {
                        this.partiesModal.selectedIds.push(numId);
                    }
                },

                selectAllFilteredParties() {
                    const filtered = this.filteredCustomers();
                    filtered.forEach(c => {
                        const numId = parseInt(c.id);
                        if (!c.is_assigned_to_other && !this.partiesModal.selectedIds.includes(numId)) {
                            this.partiesModal.selectedIds.push(numId);
                        }
                    });
                },

                clearPartiesSelection() {
                    this.partiesModal.selectedIds = [];
                },

                async saveAssignedParties() {
                    this.partiesModal.saving = true;
                    try {
                        const res = await fetch(`/admin/employees/${this.partiesModal.employeeId}/parties/bulk-sync`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                customer_ids: this.partiesModal.selectedIds
                            })
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.toast.show(data.message || 'Parties assigned successfully!', 'success');
                            this.partiesModal.open = false;
                        } else {
                            this.toast.show(data.message || 'Failed to save assigned parties.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        this.toast.show('Network error while saving parties.', 'error');
                    } finally {
                        this.partiesModal.saving = false;
                    }
                },

                // =========================================================================
                // EDIT EMPLOYEE (AJAX)
                // =========================================================================
                async openEditModal(empId) {
                    this.editModal.employeeId = empId;
                    this.editModal.errors = {};
                    this.editModal.loading = true;
                    this.editModal.open = true;
                    this.editModal.photoFile = null;
                    this.editModal.photoPreview = null;
                    this.editModal.removePhoto = false;

                    try {
                        const res = await fetch(`/admin/employees/${empId}/edit`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await res.json();
                        if (data.success && data.employee) {
                            const emp = data.employee;
                            if (data.managers) {
                                this.allManagers = data.managers;
                            }
                            this.editModal.photoPreview = emp.profile_image_url || null;
                            this.editModal.removePhoto = false;
                            this.editModal.form = {
                                name: emp.name || '',
                                email: emp.email || '',
                                phone: emp.phone || '',
                                employee_id: emp.employee_id || '',
                                position: emp.position || '',
                                department: emp.department || '',
                                salary: emp.salary || '',
                                reporting_to: emp.reporting_to || '',
                                password: '',
                                is_active: Boolean(emp.is_active),
                                role: (Array.isArray(emp.roles) && emp.roles.length > 0) ? emp.roles[0] : (emp.role_name || ''),
                                roles: Array.isArray(emp.roles) ? [...emp.roles] : [],
                            };
                        } else {
                            this.toast.show('Failed to fetch employee details.', 'error');
                            this.editModal.open = false;
                        }
                    } catch (e) {
                        console.error(e);
                        this.toast.show('Network error loading employee details.', 'error');
                        this.editModal.open = false;
                    } finally {
                        this.editModal.loading = false;
                    }
                },

                handleEditPhotoChange(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.editModal.photoFile = file;
                        this.editModal.photoPreview = URL.createObjectURL(file);
                        this.editModal.removePhoto = false;
                    }
                },

                removeEditPhoto() {
                    this.editModal.photoFile = null;
                    this.editModal.photoPreview = null;
                    this.editModal.removePhoto = true;
                },

                async saveEmployeeEdit() {
                    this.editModal.saving = true;
                    this.editModal.errors = {};

                    try {
                        const formData = new FormData();
                        formData.append('_method', 'PUT');

                        for (const [key, val] of Object.entries(this.editModal.form)) {
                            if (key === 'roles' && Array.isArray(val)) {
                                val.forEach(r => formData.append('roles[]', r));
                            } else if (key === 'parties' && Array.isArray(val)) {
                                val.forEach(p => formData.append('parties[]', p));
                            } else if (key === 'password') {
                                if (val && String(val).trim().length > 0) {
                                    formData.append('password', val);
                                }
                            } else if (key === 'is_active') {
                                formData.append('is_active', val ? '1' : '0');
                            } else if (val !== null && val !== undefined && val !== '') {
                                formData.append(key, val);
                            }
                        }

                        if (this.editModal.form.role && (!Array.isArray(this.editModal.form.roles) || !this.editModal.form.roles.length)) {
                            formData.append('roles[]', this.editModal.form.role);
                        }

                        if (this.editModal.photoFile) {
                            formData.append('profile_image', this.editModal.photoFile);
                        }

                        if (this.editModal.removePhoto) {
                            formData.append('remove_profile_image', '1');
                        }

                        const res = await fetch(`/admin/employees/${this.editModal.employeeId}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.toast.show(data.message || 'Employee updated successfully!', 'success');
                            this.updateRowInDOM(data.employee);
                            this.editModal.open = false;
                        } else if (res.status === 422 && data.errors) {
                            this.editModal.errors = data.errors;
                            const firstMsg = Object.values(data.errors).flat()[0];
                            this.toast.show(firstMsg || data.message || 'Please fix validation errors.', 'error');
                        } else {
                            this.toast.show(data.message || 'Failed to update employee.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        this.toast.show('Network error saving employee.', 'error');
                    } finally {
                        this.editModal.saving = false;
                    }
                },

                updateRowInDOM(emp) {
                    if (!emp || !emp.id) return;

                    const nameEl = document.getElementById(`name-${emp.id}`);
                    if (nameEl) nameEl.textContent = emp.name;

                    const avatarImgEl = document.getElementById(`avatar-img-${emp.id}`);
                    const avatarEl = document.getElementById(`avatar-${emp.id}`);
                    if (emp.profile_image_url) {
                        if (avatarImgEl) {
                            avatarImgEl.src = emp.profile_image_url;
                            avatarImgEl.classList.remove('hidden');
                        }
                        if (avatarEl) {
                            avatarEl.classList.add('hidden');
                            avatarEl.classList.remove('flex');
                        }
                    } else {
                        if (avatarImgEl) {
                            avatarImgEl.classList.add('hidden');
                        }
                        if (avatarEl && emp.name) {
                            avatarEl.textContent = emp.name.substring(0, 2).toUpperCase();
                            avatarEl.classList.remove('hidden');
                            avatarEl.classList.add('flex');
                        }
                    }

                    const codeEl = document.getElementById(`code-${emp.id}`);
                    if (codeEl) codeEl.textContent = emp.employee_id || `#${emp.id}`;

                    const emailEl = document.getElementById(`email-${emp.id}`);
                    if (emailEl) emailEl.textContent = emp.email;

                    const phoneEl = document.getElementById(`phone-${emp.id}`);
                    if (phoneEl) phoneEl.textContent = emp.phone || '—';

                    const posEl = document.getElementById(`pos-${emp.id}`);
                    if (posEl) posEl.textContent = emp.position || '—';

                    const managerEl = document.getElementById(`manager-${emp.id}`);
                    if (managerEl) managerEl.textContent = emp.reporting_to_name || '—';

                    // Roles
                    const rolesEl = document.getElementById(`roles-${emp.id}`);
                    if (rolesEl && Array.isArray(emp.roles)) {
                        if (emp.roles.length > 0) {
                            rolesEl.innerHTML = emp.roles.map(r => `<span class="badge badge-info">${r}</span>`).join(' ');
                        } else {
                            rolesEl.innerHTML = `<span class="badge badge-neutral">No role</span>`;
                        }
                    }

                    // Status
                    this.updateStatusBadge(emp.id, emp.is_active);
                },

                // =========================================================================
                // STATUS TOGGLE (AJAX)
                // =========================================================================
                async toggleStatus(empId, empName) {
                    try {
                        const res = await fetch(`/admin/employees/${empId}/toggle-status`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.toast.show(data.message || 'Status updated!', 'success');
                            this.updateStatusBadge(empId, data.is_active);
                        } else {
                            this.toast.show(data.message || 'Failed to update status.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        this.toast.show('Network error updating status.', 'error');
                    }
                },

                updateStatusBadge(empId, isActive) {
                    const badgeContainer = document.getElementById(`status-badge-${empId}`);
                    if (badgeContainer) {
                        if (isActive) {
                            badgeContainer.innerHTML = `<span class="badge badge-success inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Active</span>`;
                        } else {
                            badgeContainer.innerHTML = `<span class="badge badge-danger inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Inactive</span>`;
                        }
                    }

                    const toggleLabel = document.getElementById(`toggle-label-${empId}`);
                    if (toggleLabel) {
                        toggleLabel.textContent = isActive ? 'Deactivate' : 'Activate';
                    }

                    const toggleBtn = document.getElementById(`toggle-btn-${empId}`);
                    if (toggleBtn) {
                        if (isActive) {
                            toggleBtn.className = 'flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-amber-600 hover:bg-amber-50 transition-colors';
                        } else {
                            toggleBtn.className = 'flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 transition-colors';
                        }
                    }
                },

                // =========================================================================
                // DELETE EMPLOYEE (AJAX)
                // =========================================================================
                async deleteEmployee(empId, empName) {
                    if (!confirm(`Are you sure you want to delete employee '${empName}'? This action cannot be undone.`)) {
                        return;
                    }

                    try {
                        const res = await fetch(`/admin/employees/${empId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.toast.show(data.message || 'Employee deleted successfully.', 'success');
                            const numId = parseInt(empId);
                            this.allManagers = this.allManagers.filter(m => m.id !== numId);
                            window.dispatchEvent(new CustomEvent('employee-deleted', { detail: { id: numId } }));
                            const row = document.getElementById(`employee-row-${empId}`);
                            if (row && window.employeesTable) {
                                window.employeesTable.row(row).remove().draw(false);
                            }
                        } else {
                            this.toast.show(data.message || 'Failed to delete employee.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        this.toast.show('Network error while deleting employee.', 'error');
                    }
                },

                // =========================================================================
                // CREATE EMPLOYEE (AJAX)
                // =========================================================================
                async openCreateModal() {
                    this.createModal.form = {
                        name: '',
                        email: '',
                        phone: '',
                        employee_id: '',
                        position: '',
                        department: '',
                        salary: '',
                        reporting_to: '',
                        password: '',
                        is_active: true,
                        role: '',
                        roles: [],
                    };
                    this.createModal.photoFile = null;
                    this.createModal.photoPreview = null;
                    this.createModal.errors = {};
                    this.createModal.open = true;

                    // Dynamically fetch latest active managers on open
                    try {
                        const res = await fetch('/admin/employees/active-dropdown', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await res.json();
                        if (data.success && data.managers) {
                            this.allManagers = data.managers;
                        }
                    } catch (e) {
                        console.error('Failed to fetch active managers', e);
                    }
                },

                handleCreatePhotoChange(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.createModal.photoFile = file;
                        this.createModal.photoPreview = URL.createObjectURL(file);
                    }
                },

                removeCreatePhoto() {
                    this.createModal.photoFile = null;
                    this.createModal.photoPreview = null;
                },

                async saveEmployeeCreate() {
                    this.createModal.saving = true;
                    this.createModal.errors = {};

                    try {
                        const formData = new FormData();
                        for (const [key, val] of Object.entries(this.createModal.form)) {
                            if (key === 'roles' && Array.isArray(val)) {
                                val.forEach(r => formData.append('roles[]', r));
                            } else if (key === 'parties' && Array.isArray(val)) {
                                val.forEach(p => formData.append('parties[]', p));
                            } else if (key === 'is_active') {
                                formData.append('is_active', val ? '1' : '0');
                            } else if (val !== null && val !== undefined && val !== '') {
                                formData.append(key, val);
                            }
                        }

                        if (this.createModal.form.role && (!Array.isArray(this.createModal.form.roles) || !this.createModal.form.roles.length)) {
                            formData.append('roles[]', this.createModal.form.role);
                        }

                        if (this.createModal.photoFile) {
                            formData.append('profile_image', this.createModal.photoFile);
                        }

                        const res = await fetch(`{{ route('admin.employees.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.toast.show(data.message || 'Employee created successfully!', 'success');
                            this.createModal.open = false;
                            setTimeout(() => { window.location.reload(); }, 600);
                        } else if (res.status === 422 && data.errors) {
                            this.createModal.errors = data.errors;
                            const firstMsg = Object.values(data.errors).flat()[0];
                            this.toast.show(firstMsg || data.message || 'Please fix validation errors.', 'error');
                        } else {
                            this.toast.show(data.message || 'Failed to create employee.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        this.toast.show('Network error creating employee.', 'error');
                    } finally {
                        this.createModal.saving = false;
                    }
                }
            };
        }
    </script>
@endpush