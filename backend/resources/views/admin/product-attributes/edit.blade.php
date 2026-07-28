@extends('admin.layouts.app')

@section('title', 'Edit Product Attribute — ' . $attribute->name)
@section('breadcrumb', 'Admin / Product Attributes / Edit')
@section('page_title', 'Edit Attribute')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.product-attributes.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950 transition">
        ← Back to Attributes
    </a>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    {{-- Left: Attribute Details --}}
    <div class="xl:col-span-2 space-y-6">
        <form method="POST" action="{{ route('admin.product-attributes.update', $attribute->id) }}">
            @csrf
            @method('PUT')

            <section class="panel">
                <div class="panel-header">
                    <h2 class="font-semibold text-slate-950">Attribute Details</h2>
                    <p class="mt-1 text-sm text-slate-500">Update the attribute information.</p>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="form-label">Attribute Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $attribute->name) }}"
                               class="form-input @error('name') border-red-400 @enderror"
                               placeholder="e.g., Color, Size, Material" required>
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="form-label">Slug <span class="text-ink-400">(Read-only)</span></label>
                        <input type="text" value="{{ $attribute->slug }}" class="form-input bg-surface-muted" readonly>
                    </div>

                    <div>
                        <label class="form-label">Input Type <span class="text-red-500">*</span></label>
                        <select name="input_type" class="form-input @error('input_type') border-red-400 @enderror" required>
                            @foreach($inputTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('input_type', $attribute->input_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('input_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-input @error('description') border-red-400 @enderror"
                                  placeholder="What is this attribute used for?">{{ old('description', $attribute->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $attribute->is_active))
                               class="rounded border-slate-300 text-slate-950 focus:ring-slate-300">
                        <label for="is_active" class="text-sm font-medium text-slate-900">Active</label>
                    </div>

                    <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                        <button type="submit" class="btn-primary">Update Attribute</button>
                        <a href="{{ route('admin.product-attributes.index') }}" class="btn-secondary">Cancel</a>
                    </div>
                </div>
            </section>
        </form>
    </div>

    {{-- Right: Attribute Values --}}
    <div class="space-y-6">
        <section class="panel">
            <div class="panel-header flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-slate-950">Values</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $attribute->values->count() }} values</p>
                </div>
            </div>

            <div class="p-6 space-y-4">
                {{-- Existing Values --}}
                @forelse($attribute->values as $value)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50">
                        <div class="flex items-center gap-3">
                            @if($attribute->input_type === 'color' && $value->color_code)
                                <div class="w-6 h-6 rounded-full border border-slate-300" style="background-color: {{ $value->color_code }}"></div>
                            @endif
                            <span class="text-sm font-medium text-slate-900">{{ $value->value }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.product-attributes.delete-value', $value->id) }}" onsubmit="return confirm('Delete this value?')" class="contents">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700">
                                <x-lucide-trash-2 class="w-4 h-4" />
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-4">No values yet</p>
                @endforelse

                {{-- Add New Value --}}
                <form method="POST" action="{{ route('admin.product-attributes.add-value', $attribute->id) }}" class="space-y-3 pt-4 border-t border-slate-200">
                    @csrf
                    <div>
                        <label class="form-label text-xs">Add Value</label>
                        <input type="text" name="value" placeholder="e.g., Red" class="form-input"
                               required>
                    </div>

                    @if($attribute->input_type === 'color')
                        <div>
                            <label class="form-label text-xs">Color Code</label>
                            <input type="color" name="color_code" class="w-full h-10 rounded border border-slate-300 cursor-pointer">
                        </div>
                    @endif

                    <button type="submit" class="w-full btn-primary text-sm">+ Add Value</button>
                </form>
            </div>
        </section>

        {{-- Delete Attribute --}}
        <form method="POST" action="{{ route('admin.product-attributes.destroy', $attribute->id) }}" onsubmit="return confirm('Delete this attribute and all its values?')" class="contents">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full px-4 py-2 text-sm font-bold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition">
                Delete Attribute
            </button>
        </form>
    </div>
</div>

@endsection
