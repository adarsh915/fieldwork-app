@extends('admin.layouts.app')

@section('title', 'Create Role')
@section('breadcrumb', 'Admin / Roles / Create')
@section('page_title', 'Create New Role')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
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
                <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 @enderror" placeholder="e.g., Customer Support, Content Manager" required>
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

                    <div class="max-h-[500px] overflow-y-auto rounded-lg border border-slate-200 p-4">
                        @foreach($groupedPermissions as $module => $modulePermissions)
                            <div class="mb-5 last:mb-0">
                                <h4 class="mb-2 text-sm font-bold text-slate-900 uppercase tracking-wide">
                                    {{ ucfirst($module) }}
                                </h4>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($modulePermissions as $permission)
                                        <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"
                                                @checked(in_array($permission->name, old('permissions', [])))
                                                class="rounded border-slate-300 text-slate-950 focus:ring-slate-300"
                                            >
                                            <span class="text-xs">{{ $permission->name }}</span>
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
                        <a href="{{ route('admin.permissions.create') }}" class="mt-2 inline-block font-bold text-amber-900 underline">
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
