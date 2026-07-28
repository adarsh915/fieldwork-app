@extends('admin.layouts.app')

@section('title', 'Debug Permissions')
@section('breadcrumb', 'Admin / Debug')
@section('page_title', 'Permission Debug Page')

@section('content')
    <div class="space-y-6">
        {{-- Current User Info --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">Current User Information</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">ID</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">{{ auth()->id() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Name</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">{{ auth()->user()->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Email</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">{{ auth()->user()->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Status</dt>
                        <dd class="mt-1">
                            @if(auth()->user()->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Roles --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">Assigned Roles</h2>
            </div>
            <div class="p-6">
                @if(auth()->user()->roles->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach(auth()->user()->roles as $role)
                            <span class="badge badge-info text-sm">{{ $role->name }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-500">No roles assigned</p>
                @endif
            </div>
        </section>

        {{-- Permissions --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">All Permissions</h2>
            </div>
            <div class="p-6">
                @php
                    $permissions = auth()->user()->getAllPermissions();
                    $grouped = $permissions->groupBy(function($permission) {
                        return explode('.', $permission->name)[0];
                    });
                @endphp

                @if($grouped->count() > 0)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($grouped as $group => $perms)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <h3 class="mb-2 font-bold text-slate-900">{{ ucfirst($group) }}</h3>
                                <ul class="space-y-1">
                                    @foreach($perms as $perm)
                                        <li class="flex items-center gap-2 text-sm text-slate-700">
                                            <span class="text-green-500">✓</span>
                                            {{ $perm->name }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-500">No permissions assigned</p>
                @endif
            </div>
        </section>

        {{-- Permission Checks --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">Permission Checks</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Permission</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Has Permission?</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Can Access?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @php
                                $checks = [
                                    'employees.view' => 'View Employees List',
                                    'employees.create' => 'Create Employee',
                                    'employees.edit' => 'Edit Employee',
                                    'employees.delete' => 'Delete Employee',
                                    'employees.manage' => 'Manage Employees',
                                    'users.view' => 'View Users',
                                    'users.manage' => 'Manage Users',
                                    'roles.view' => 'View Roles',
                                    'roles.manage' => 'Manage Roles',
                                ];
                            @endphp
                            @foreach($checks as $permission => $label)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $label }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <code class="rounded bg-slate-100 px-2 py-1 text-xs">{{ $permission }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        @can($permission)
                                            <span class="badge badge-success">✓ YES</span>
                                        @else
                                            <span class="badge badge-danger">✗ NO</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Role Checks --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">Role Checks</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Has Role?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @php
                                $rolesToCheck = ['Admin', 'Manager', 'Finance', 'Employee', 'field_worker'];
                            @endphp
                            @foreach($rolesToCheck as $roleName)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $roleName }}</td>
                                    <td class="px-4 py-3">
                                        @if(auth()->user()->hasRole($roleName))
                                            <span class="badge badge-success">✓ YES</span>
                                        @else
                                            <span class="badge badge-neutral">✗ NO</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Test Links --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">Test Navigation Links</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <a href="{{ route('admin.employees.index') }}" class="rounded-lg border-2 border-blue-500 bg-blue-50 px-4 py-3 text-center font-bold text-blue-700 transition hover:bg-blue-100">
                        Go to Employee List
                    </a>
                    <a href="{{ route('admin.employees.create') }}" class="rounded-lg border-2 border-green-500 bg-green-50 px-4 py-3 text-center font-bold text-green-700 transition hover:bg-green-100">
                        Go to Create Employee
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="rounded-lg border-2 border-purple-500 bg-purple-50 px-4 py-3 text-center font-bold text-purple-700 transition hover:bg-purple-100">
                        Go to Roles & Permissions
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg border-2 border-slate-500 bg-slate-50 px-4 py-3 text-center font-bold text-slate-700 transition hover:bg-slate-100">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
