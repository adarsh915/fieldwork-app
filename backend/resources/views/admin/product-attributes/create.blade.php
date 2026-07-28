@extends('admin.layouts.app')

@section('title', 'Create Product Attribute')
@section('breadcrumb', 'Admin / Product Attributes / Create')
@section('page_title', 'Create Product Attribute')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.product-attributes.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950 transition">
        ← Back to Attributes
    </a>
</div>

<form method="POST" action="{{ route('admin.product-attributes.store') }}" class="max-w-2xl">
    @csrf

    <div class="space-y-6">
        {{-- Attribute Name --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">Attribute Details</h2>
                <p class="mt-1 text-sm text-slate-500">Define the attribute that will be used across products.</p>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="form-label">Attribute Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-input @error('name') border-red-400 @enderror"
                           placeholder="e.g., Color, Size, Material" required autofocus>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="form-label">Input Type <span class="text-red-500">*</span></label>
                    <select name="input_type" class="form-input @error('input_type') border-red-400 @enderror" required>
                        @foreach($inputTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('input_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('input_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3"
                              class="form-input @error('description') border-red-400 @enderror"
                              placeholder="What is this attribute used for?">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"  @checked(old('is_active', true))
                           class="rounded border-slate-300 text-slate-950 focus:ring-slate-300">
                    <label for="is_active" class="text-sm font-medium text-slate-900">Active</label>
                </div>
            </div>
        </section>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">Create Attribute</button>
            <a href="{{ route('admin.product-attributes.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </div>
</form>

@endsection
