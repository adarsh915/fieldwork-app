@extends('admin.layouts.app')

@section('title', 'Users')
@section('breadcrumb', 'ADMIN / USERS')
@section('page_title', 'Users')

@section('content')
<div class="panel">
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">User Directory</h2>
                <p class="text-caption mt-1">Review accounts and maintain role-based access.</p>
            </div>
        </div>

        {{-- Search bar --}}
        <form method="GET" action="{{ route('admin.users.index') }}" class="mt-4 flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                <input
                    name="q"
                    value="{{ $search }}"
                    class="form-input pl-9"
                    placeholder="Search name or email…"
                >
            </div>
            <x-button type="submit" variant="primary">Search</x-button>
            @if ($search)
                <x-button href="{{ route('admin.users.index') }}" variant="secondary">Reset</x-button>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Joined</th>
                    @can('users.manage')
                        <th class="text-right">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                                <div>
                                    <div class="text-body-emphasis">{{ $user->name }} ({{ $user->role_name }})</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-body">{{ $user->email }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($user->roles as $role)
                                    <span class="badge badge-info">{{ $role->name }}</span>
                                @empty
                                    <span class="badge badge-neutral">No role</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="text-caption">{{ $user->created_at?->format('d M Y') }}</td>

                        @can('users.manage')
                            <td>
                                <div class="flex justify-end">
                                    <div x-data="{ open: false }" class="relative">
                                        <x-button variant="secondary" @click="open = !open" @click.stop>
                                            <x-lucide-shield-check class="w-4 h-4" />
                                            Manage Roles
                                            <x-lucide-chevron-down class="w-3 h-3" />
                                        </x-button>

                                        <div
                                            x-show="open"
                                            @click.outside="open = false"
                                            x-transition
                                            class="absolute right-0 top-full mt-1 w-64 bg-white border border-surface-border rounded-xl shadow-xl z-20 p-4"
                                            style="display: none;"
                                        >
                                            <form method="POST" action="{{ route('admin.users.roles.update', $user) }}" class="space-y-3">
                                                @csrf
                                                <div class="text-xs font-semibold text-ink-500 mb-1">Select Role</div>
                                                <div class="max-h-48 overflow-y-auto space-y-1.5">
                                                    @foreach ($roles as $role)
                                                        <label class="flex items-center gap-2 rounded-lg hover:bg-surface-muted px-2 py-1.5 cursor-pointer transition-colors">
                                                            <input
                                                                type="radio"
                                                                name="role"
                                                                value="{{ $role->name }}"
                                                                @checked($user->hasRole($role->name))
                                                                class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                                                            >
                                                            <span class="text-sm font-medium text-ink-900 capitalize">{{ $role->name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <x-button type="submit" variant="primary" class="w-full justify-center">Save Changes</x-button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td class="py-16 text-center" colspan="{{ auth()->user()->can('users.manage') ? 5 : 4 }}">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-inbox class="w-10 h-10" />
                                <p class="text-sm font-medium">No users found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($users->hasPages())
        <div class="border-t border-surface-border px-6 py-4">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
