@extends('admin.layouts.app')

@section('title', 'Edit Role')
@section('breadcrumb', 'Admin / Roles / Edit')
@section('page_title', 'Edit Role')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            ← Back to Roles & Permissions
        </a>
    </div>

    <section class="panel max-w-3xl">
        <div class="panel-header">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Edit Role: {{ $role->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Update role name and permissions.</p>
                </div>
                @if(in_array($role->name, ['Admin', 'Super Admin']))
                    <span class="badge badge-danger">System Role</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('admin.roles.update', $role->id) }}" class="p-6">
            @csrf
            @method('PUT')

            {{-- Role Name --}}
            <div class="mb-6">
                <label class="form-label">Role Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-input @error('name') border-red-500 @enderror" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                                                @checked(in_array($permission->name, old('permissions', $role->permissions->pluck('name')->toArray())))
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
                        No permissions available in the system.
                    </div>
                @endif

                @error('permissions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Role Info --}}
            <div class="mb-6 rounded-lg bg-slate-50 p-4">
                <h4 class="text-sm font-bold text-slate-700">Role Information</h4>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-slate-500">Users with this role:</span>
                        <span class="font-medium text-slate-900">{{ $role->users()->count() }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Guard:</span>
                        <span class="font-medium text-slate-900">{{ $role->guard_name }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                <button type="submit" class="btn-primary">
                    Update Role
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn-secondary">
                    Cancel
                </a>

                @unless(in_array($role->name, ['Admin', 'Super Admin']))
                    <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" 
                          onsubmit="return confirm('Delete this role? Users with this role will lose these permissions.')" 
                          class="ml-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md bg-red-50 px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-100">
                            Delete Role
                        </button>
                    </form>
                @endunless
            </div>
        </form>
    </section>
@endsection
