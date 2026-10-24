@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')
@section('breadcrumb', 'ADMIN / ROLES & PERMISSIONS')
@section('page_title', 'Roles & Permissions')

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_380px]">

        {{-- ── Roles ── --}}
        <div class="panel">
            <div class="panel-header flex items-center justify-between">
                <div>
                    <h2 class="text-section-title">Roles</h2>
                    <p class="text-caption mt-1">Manage user roles and their permissions.</p>
                </div>
                @can('roles.manage')
                    <x-button href="{{ route('admin.roles.create') }}" variant="primary">
                        <x-lucide-plus class="w-4 h-4" />
                        Create Role
                    </x-button>
                @endcan
            </div>

            <div class="divide-y divide-surface-border">
                @forelse ($roles as $role)
                    <article class="px-6 py-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-body-emphasis text-base font-bold">{{ $role->name }}</h3>
                                    @if(in_array($role->name, ['Admin', 'Super Admin']))
                                        <span class="badge badge-danger">System Role</span>
                                    @endif
                                </div>
                                <p class="text-caption mt-1">
                                    {{ $role->permissions->count() }} permissions &middot; {{ $role->users_count }} users
                                </p>
                            </div>

                            @can('roles.manage')
                                                <x-row-actions :actions="array_filter([
                                    [
                                        'type' => 'link',
                                        'label' => 'Edit Role',
                                        'icon' => 'pencil',
                                        'href' => route('admin.roles.edit', $role->id),
                                    ],
                                    !in_array($role->name, ['Admin', 'Super Admin']) ? [
                                        'type' => 'form',
                                        'label' => 'Delete Role',
                                        'icon' => 'trash-2',
                                        'action' => route('admin.roles.destroy', $role->id),
                                        'method' => 'DELETE',
                                        'confirm' => 'Delete role ' . $role->name . '? Users will lose these permissions.',
                                        'variant' => 'danger',
                                    ] : null,
                                ])" />
                            @endcan
                        </div>

                        <div class="mt-3">
                            <p class="text-caption mb-2">Permissions:</p>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($role->permissions as $permission)
                                    <span class="badge badge-neutral">{{ $permission->name }}</span>
                                @empty
                                    <span class="text-caption italic">No permissions assigned</span>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-2 text-ink-400">
                            <x-lucide-shield-check class="w-10 h-10" />
                            <p class="text-sm">No roles found. Create your first role.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Permissions ── --}}
        <div class="panel">
            <div class="panel-header flex items-center justify-between">
                <div>
                    <h2 class="text-section-title">Permissions</h2>
                    <p class="text-caption mt-1">All available permissions</p>
                </div>
                @can('roles.manage')
                    <x-button href="{{ route('admin.permissions.create') }}" variant="primary">
                        <x-lucide-plus class="w-4 h-4" />
                        Add
                    </x-button>
                @endcan
            </div>

            <div class="p-6">
                <div class="space-y-1.5 max-h-[60vh] overflow-y-auto">
                    @forelse ($permissions as $permission)
                        <div class="flex items-center justify-between rounded-lg border border-surface-border bg-surface-muted px-3 py-2">
                            <span class="text-sm font-medium text-ink-900">{{ $permission->name }}</span>
                            @can('roles.manage')
                                <form method="POST" action="{{ route('admin.permissions.destroy', $permission->id) }}"
                                      onsubmit="return confirm('Delete this permission? It will be removed from all roles.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-danger-text hover:text-red-800 transition-colors">
                                        Delete
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="text-center text-sm text-ink-400 py-6">No permissions found.</div>
                    @endforelse
                </div>

                @can('roles.manage')
                    <div class="mt-5 rounded-xl bg-info-bg border border-blue-200 p-4">
                        <p class="text-sm font-semibold text-info-text">💡 Naming Convention</p>
                        <p class="text-xs text-info-text mt-1">Use format: <code class="rounded bg-blue-100 px-1">resource.action</code></p>
                        <p class="text-xs text-blue-500 mt-1">e.g. users.view, posts.create, reports.export</p>
                    </div>
                @endcan
            </div>
        </div>

    </div>
@endsection
