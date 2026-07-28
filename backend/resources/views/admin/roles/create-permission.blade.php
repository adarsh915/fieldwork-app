@extends('admin.layouts.app')

@section('title', 'Create Permission')
@section('breadcrumb', 'Admin / Permissions / Create')
@section('page_title', 'Create New Permission')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            ← Back to Roles & Permissions
        </a>
    </div>

    <section class="panel max-w-2xl">
        <div class="panel-header">
            <h2 class="font-semibold text-slate-950">Permission Information</h2>
            <p class="mt-1 text-sm text-slate-500">Create a new permission that can be assigned to roles.</p>
        </div>

        <form method="POST" action="{{ route('admin.permissions.store') }}" class="p-6">
            @csrf

            {{-- Permission Name --}}
            <div class="mb-6">
                <label class="form-label">Permission Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 @enderror" placeholder="e.g., posts.create" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Naming Convention Info --}}
            <div class="mb-6 rounded-lg bg-blue-50 p-4">
                <h4 class="text-sm font-bold text-blue-900">💡 Permission Naming Convention</h4>
                <p class="mt-2 text-sm text-blue-800">Use the format: <code class="rounded bg-blue-100 px-2 py-0.5 font-mono text-blue-900">resource.action</code></p>
                
                <div class="mt-3 space-y-2 text-xs text-blue-700">
                    <div class="flex items-start gap-2">
                        <span class="font-bold">Common Actions:</span>
                        <span>view, create, edit, update, delete, manage</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-bold">Examples:</span>
                        <div class="flex flex-col gap-1">
                            <code class="rounded bg-blue-100 px-2 py-0.5 font-mono">users.view</code>
                            <code class="rounded bg-blue-100 px-2 py-0.5 font-mono">users.manage</code>
                            <code class="rounded bg-blue-100 px-2 py-0.5 font-mono">posts.create</code>
                            <code class="rounded bg-blue-100 px-2 py-0.5 font-mono">posts.delete</code>
                            <code class="rounded bg-blue-100 px-2 py-0.5 font-mono">reports.export</code>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                <button type="submit" class="btn-primary">
                    Create Permission
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection
