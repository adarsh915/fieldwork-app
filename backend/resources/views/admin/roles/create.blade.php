@extends('admin.layouts.app')

@section('title', 'Create Role')
@section('breadcrumb', 'Admin / Roles / Create')
@section('page_title', 'Create New Role')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.roles.index') }}"
            class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            ← Back to Roles & Permissions
        </a>
    </div>

    <section class="panel max-w-3xl">
        <div class="panel-header">
            <h2 class="font-semibold text-slate-950">Role Information</h2>
            <p class="mt-1 text-sm text-slate-500">Create a new role and assign permissions.</p>
        </div>

        <form method="POST" action="{{ route('admin.roles.store') }}" class="p-6">
            @csrf

            {{-- Role Name --}}
            <div class="mb-6">
                <label class="form-label">Role Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-input @error('name') border-red-500 @enderror"
                    placeholder="e.g., Customer Support, Content Manager" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-500">Use a descriptive name (e.g., "Manager", "Editor", "Viewer")</p>
            </div>

            {{-- Permissions --}}
            <div class="mb-6">
                <label class="form-label">Assign Permissions</label>
                <p class="mb-3 text-sm text-slate-500">Select which permissions this role should have</p>

                @if($permissions->count() > 0)
                    @php
                        // Group permissions by module
                        $groupedPermissions = [];
                        foreach ($permissions as $permission) {
                            $parts = explode('.', $permission->name);
                            $module = $parts[0] ?? 'general';
                            if (!isset($groupedPermissions[$module])) {
                                $groupedPermissions[$module] = [];
                            }
                            $groupedPermissions[$module][] = $permission;
                        }
                        ksort($groupedPermissions);
                    @endphp

                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <button type="button"
                            onclick="document.querySelectorAll('input[name=\'permissions[]\']').forEach(el => el.checked = true)"
                            class="rounded bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            Select All
                        </button>
                        <button type="button"
                            onclick="document.querySelectorAll('input[name=\'permissions[]\']').forEach(el => el.checked = false)"
                            class="rounded bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            Deselect All
                        </button>
                        <button type="button"
                            onclick="document.querySelectorAll('input[name=\'permissions[]\']').forEach(el => { if (el.value.includes('.view-team')) el.checked = true })"
                            class="rounded bg-amber-50 hover:bg-amber-100 border border-amber-200 px-2.5 py-1 text-xs font-semibold text-amber-800">
                            + Select All Team Scopes
                        </button>
                        <button type="button"
                            onclick="document.querySelectorAll('input[name=\'permissions[]\']').forEach(el => { if (el.value.includes('.view-own')) el.checked = true })"
                            class="rounded bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                            + Select All Own Scopes
                        </button>
                        <button type="button"
                            onclick="document.querySelectorAll('input[name=\'permissions[]\']').forEach(el => { if (el.value.endsWith('.view') || el.value.includes('.view-all')) el.checked = true })"
                            class="rounded bg-blue-50 hover:bg-blue-100 border border-blue-200 px-2.5 py-1 text-xs font-semibold text-blue-800">
                            + Select All View Permissions
                        </button>
                    </div>

                    <div class="max-h-[500px] overflow-y-auto rounded-lg border border-slate-200 p-4">
                        @foreach($groupedPermissions as $module => $modulePermissions)
                            <div class="mb-5 last:mb-0 border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
                                <div class="mb-2 flex items-center justify-between">
                                    <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                        {{ ucfirst($module) }}
                                    </h4>
                                    <button type="button"
                                        onclick="document.querySelectorAll('.perm-mod-{{ $module }}').forEach(el => el.checked = !el.checked)"
                                        class="text-xs font-semibold text-brand-600 hover:text-brand-800">
                                        Toggle All
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($modulePermissions as $permission)
                                        @php
                                            $badgeColor = 'bg-slate-100 text-slate-700';
                                            $badgeLabel = '';
                                            if (str_ends_with($permission->name, '.view-all')) {
                                                $badgeColor = 'bg-blue-100 text-blue-800 border-blue-200';
                                                $badgeLabel = 'All Scope';
                                            } elseif (str_ends_with($permission->name, '.view-team')) {
                                                $badgeColor = 'bg-amber-100 text-amber-800 border-amber-200';
                                                $badgeLabel = 'Team Scope';
                                            } elseif (str_ends_with($permission->name, '.view-own')) {
                                                $badgeColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                                $badgeLabel = 'Own Scope';
                                            } elseif (str_contains($permission->name, 'approve')) {
                                                $badgeColor = 'bg-purple-100 text-purple-800 border-purple-200';
                                                $badgeLabel = 'Approval';
                                            }
                                        @endphp
                                        <label
                                            class="flex items-center justify-between gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 cursor-pointer">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                    @checked(in_array($permission->name, old('permissions', [])))
                                                    class="perm-mod-{{ $module }} rounded border-slate-300 text-slate-950 focus:ring-slate-300">
                                                <span class="text-xs truncate"
                                                    title="{{ $permission->name }}">{{ $permission->name }}</span>
                                            </div>
                                            @if($badgeLabel)
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border {{ $badgeColor }} shrink-0">
                                                    {{ $badgeLabel }}
                                                </span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <div class="font-bold">⚠️ No Permissions Available</div>
                        <div class="mt-1">Create some permissions first before creating roles.</div>
                        <a href="{{ route('admin.permissions.create') }}"
                            class="mt-2 inline-block font-bold text-amber-900 underline">
                            Create Permission →
                        </a>
                    </div>
                @endif

                @error('permissions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                <button type="submit" class="btn-primary">
                    Create Role
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection