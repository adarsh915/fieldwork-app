@extends('admin.layouts.app')

@section('title', 'Employees')
@section('breadcrumb', 'ADMIN / EMPLOYEES')
@section('page_title', 'Employee Management')

@section('content')
<div class="panel">
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">All Employees</h2>
                <p class="text-caption mt-1">View, search, and manage all employees in the system.</p>
            </div>
            @can('employees.create')
                <x-button href="{{ route('admin.employees.create') }}" variant="primary">
                    <x-lucide-plus class="w-4 h-4" />
                    Create Employee
                </x-button>
            @endcan
        </div>

        {{-- Filters --}}
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="relative flex-1 max-w-sm">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                <input id="dt-search" class="form-input pl-9" placeholder="Search employees…">
            </div>
            <select id="roleFilter" class="form-input w-auto">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
            <select id="statusFilter" class="form-input w-auto">
                <option value="">All Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <button id="resetFilters" class="btn-secondary">Reset</button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrap" x-data="{ loading: true }" x-init="$nextTick(() => { loading = false })">
        <table id="employeesTable" class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Contact</th>
                    <th>Position</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Hired</th>
                    @canany(['employees.edit', 'employees.delete'])
                        <th class="text-right">Actions</th>
                    @endcanany
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $employee)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-ink-900 text-sm font-bold text-white">
                                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                                </span>
                                <div>
                                    <div class="text-body-emphasis">{{ $employee->name }}</div>
                                    <div class="text-caption">{{ $employee->employee_id ?? '#' . $employee->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-body">{{ $employee->email }}</div>
                            @if($employee->phone)
                                <div class="text-caption">{{ $employee->phone }}</div>
                            @endif
                        </td>
                        <td class="text-body">{{ $employee->position ?? '—' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($employee->roles as $role)
                                    <span class="badge badge-info">{{ $role->name }}</span>
                                @empty
                                    <span class="badge badge-neutral">No role</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            @if($employee->is_active)
                                <span class="badge badge-success"><x-lucide-check-circle class="w-3 h-3" /> Active</span>
                            @else
                                <span class="badge badge-danger"><x-lucide-x-circle class="w-3 h-3" /> Inactive</span>
                            @endif
                        </td>
                        <td class="text-caption">
                            {{ $employee->hire_date ? $employee->hire_date->format('d M Y') : '—' }}
                        </td>

                        @canany(['employees.edit', 'employees.delete'])
                            <td>
                                <x-row-actions :actions="array_filter([
                                    auth()->user()->can('employees.view') ? [
                                        'type'  => 'link',
                                        'label' => 'View Profile',
                                        'icon'  => 'eye',
                                        'href'  => route('admin.employees.profile', $employee->id),
                                    ] : null,
                                    auth()->user()->can('employees.edit') ? [
                                        'type'  => 'link',
                                        'label' => 'Edit',
                                        'icon'  => 'pencil',
                                        'href'  => route('admin.employees.edit', $employee->id),
                                    ] : null,
                                    auth()->user()->can('employees.edit') ? [
                                        'type'  => 'link',
                                        'label' => 'Parties',
                                        'icon'  => 'building',
                                        'href'  => route('admin.employees.parties', $employee->id),
                                    ] : null,
                                    auth()->user()->can('employees.edit') ? [
                                        'type'    => 'form',
                                        'label'   => $employee->is_active ? 'Deactivate' : 'Activate',
                                        'icon'    => 'power',
                                        'action'  => route('admin.employees.toggleStatus', $employee->id),
                                        'method'  => 'PATCH',
                                        'variant' => $employee->is_active ? 'danger' : 'default',
                                    ] : null,
                                    (auth()->user()->can('employees.delete') && auth()->id() !== $employee->id) ? [
                                        'type'    => 'form',
                                        'label'   => 'Delete',
                                        'icon'    => 'trash-2',
                                        'action'  => route('admin.employees.destroy', $employee->id),
                                        'method'  => 'DELETE',
                                        'confirm' => 'Delete employee ' . $employee->name . '? This cannot be undone.',
                                        'variant' => 'danger',
                                    ] : null,
                                ])" />
                            </td>
                        @endcanany
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = new DataTable('#employeesTable', {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        order: [[0, 'asc']],
        language: {
            emptyTable: 'No employees found.',
            zeroRecords: 'No matching employees.',
        },
        columnDefs: [{ targets: -1, orderable: false, searchable: false }],
        dom: '<"flex items-center justify-between px-6 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>rt',
    });

    document.getElementById('dt-search').addEventListener('input', function () {
        table.search(this.value).draw();
    });

    document.getElementById('roleFilter').addEventListener('change', function () {
        table.column(3).search(this.value).draw();
    });

    document.getElementById('statusFilter').addEventListener('change', function () {
        table.column(4).search(this.value).draw();
    });

    document.getElementById('resetFilters').addEventListener('click', function () {
        document.getElementById('dt-search').value = '';
        document.getElementById('roleFilter').value = '';
        document.getElementById('statusFilter').value = '';
        table.search('').columns().search('').draw();
    });
});
</script>
@endpush
