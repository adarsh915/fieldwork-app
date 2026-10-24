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
        <form method="POST" action="{{ route('admin.product-attributes.update', $attribute->id) }}" id="updateAttributeForm">
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
                    <p class="mt-1 text-sm text-slate-500" id="valuesCountHeader">{{ $attribute->values->count() }} values</p>
                </div>
            </div>

            <div class="p-6 space-y-4">
                {{-- Existing Values Container --}}
                <div id="valuesList" class="space-y-2">
                    @forelse($attribute->values as $value)
                        <div class="value-item flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50" data-value-id="{{ $value->id }}">
                            <div class="flex items-center gap-3">
                                @if($attribute->input_type === 'color' && $value->color_code)
                                    <div class="w-6 h-6 rounded-full border border-slate-300" style="background-color: {{ $value->color_code }}"></div>
                                @endif
                                <span class="text-sm font-medium text-slate-900">{{ $value->value }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.product-attributes.delete-value', $value->id) }}" class="delete-value-form contents">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-700 p-1" title="Delete Value">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p id="noValuesMsg" class="text-sm text-slate-500 text-center py-4">No values yet</p>
                    @endforelse
                </div>

                {{-- Add New Value --}}
                <form method="POST" action="{{ route('admin.product-attributes.add-value', $attribute->id) }}" id="addValueForm" class="space-y-3 pt-4 border-t border-slate-200">
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
        <form method="POST" action="{{ route('admin.product-attributes.destroy', $attribute->id) }}" id="deleteAttributeForm" class="contents">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full px-4 py-2 text-sm font-bold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition">
                Delete Attribute
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Update Attribute Form
    const updateForm = document.getElementById('updateAttributeForm');
    updateForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = updateForm.querySelector('button[type="submit"]');
        const origText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">↻</span> Updating...';

        try {
            const res = await fetch(updateForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(updateForm)
            });
            const data = await res.json();
            if (res.ok && (data.success || res.status === 200)) {
                if (window.toast) window.toast(data.message || 'Attribute updated successfully', 'success');
            } else {
                let msg = data.message || 'Validation error';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                if (window.toast) window.toast(msg, 'error');
            }
        } catch (err) {
            console.error(err);
            if (window.toast) window.toast('Network error. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
        }
    });

    // 2. Add Value Form
    const addValForm = document.getElementById('addValueForm');
    const valuesList = document.getElementById('valuesList');
    const noValuesMsg = document.getElementById('noValuesMsg');
    const countHeader = document.getElementById('valuesCountHeader');

    addValForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = addValForm.querySelector('button[type="submit"]');
        const origText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">↻</span> Adding...';

        try {
            const formData = new FormData(addValForm);
            const res = await fetch(addValForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && (data.success || res.status === 200)) {
                if (window.toast) window.toast(data.message || 'Value added successfully', 'success');
                if (noValuesMsg) noValuesMsg.remove();
                
                // Append new value item dynamically
                const val = data.value;
                const div = document.createElement('div');
                div.className = 'value-item flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50';
                div.dataset.valueId = val.id;
                
                let colorBadge = '';
                if (val.color_code) {
                    colorBadge = `<div class="w-6 h-6 rounded-full border border-slate-300" style="background-color: ${val.color_code}"></div>`;
                }

                div.innerHTML = `
                    <div class="flex items-center gap-3">
                        ${colorBadge}
                        <span class="text-sm font-medium text-slate-900">${val.value}</span>
                    </div>
                    <form method="POST" action="/admin/product-attributes/values/${val.id}" class="delete-value-form contents">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-red-600 hover:text-red-700 p-1" title="Delete Value">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                        </button>
                    </form>
                `;
                valuesList.appendChild(div);
                addValForm.reset();
                updateValuesCount();
            } else {
                let msg = data.message || 'Error adding value';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                if (window.toast) window.toast(msg, 'error');
            }
        } catch (err) {
            console.error(err);
            if (window.toast) window.toast('Network error.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
        }
    });

    // 3. Delete Value Delegated Listener
    valuesList.addEventListener('submit', async function (e) {
        const form = e.target.closest('.delete-value-form');
        if (!form) return;
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this value?')) return;

        const row = form.closest('.value-item');
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });
            const data = await res.json();
            if (res.ok && (data.success || res.status === 200)) {
                if (window.toast) window.toast(data.message || 'Value deleted', 'success');
                row.remove();
                updateValuesCount();
            } else {
                if (window.toast) window.toast(data.message || 'Failed to delete value', 'error');
            }
        } catch (err) {
            console.error(err);
            if (window.toast) window.toast('Network error.', 'error');
        }
    });

    function updateValuesCount() {
        const count = valuesList.querySelectorAll('.value-item').length;
        if (countHeader) countHeader.textContent = `${count} values`;
    }

    // 4. Delete Attribute Form
    const delAttrForm = document.getElementById('deleteAttributeForm');
    if (delAttrForm) {
        delAttrForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!confirm('Delete this attribute and all associated values?')) return;
            const submitBtn = delAttrForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">↻</span> Deleting...';

            try {
                const res = await fetch(delAttrForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(delAttrForm)
                });
                const data = await res.json();
                if (res.ok && (data.success || res.status === 200)) {
                    if (window.toast) window.toast(data.message || 'Attribute deleted', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '{{ route('admin.product-attributes.index') }}';
                    }, 600);
                } else {
                    if (window.toast) window.toast(data.message || 'Delete failed', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Delete Attribute';
                }
            } catch (err) {
                console.error(err);
                if (window.toast) window.toast('Network error.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Delete Attribute';
            }
        });
    }
});
</script>
@endpush
