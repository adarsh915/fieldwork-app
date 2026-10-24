@extends('admin.layouts.app')

@section('title', 'Product Attributes')
@section('breadcrumb', 'ADMIN / PRODUCT ATTRIBUTES')
@section('page_title', 'Product Attributes')

@section('content')

@if(session('status'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('status') }}</p>
    </div>
@endif

<div class="panel">
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">Product Attributes</h2>
                <p class="text-caption mt-1">Manage product attributes like Color, Size, Material, etc.</p>
            </div>
            <x-button href="{{ route('admin.product-attributes.create') }}" variant="primary">
                <x-lucide-plus class="w-4 h-4" />
                Add Attribute
            </x-button>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.product-attributes.index') }}" class="mt-4 flex flex-wrap gap-3 items-end">
            <div class="relative flex-1 min-w-[200px]">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                <input name="q" value="{{ $search }}" class="form-input pl-9" placeholder="Search attributes…">
            </div>
            <select name="status" class="form-input w-auto">
                <option value="">All Status</option>
                <option value="active"   @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
            <x-button type="submit" variant="primary">
                <x-lucide-filter class="w-4 h-4" />
                Search
            </x-button>
            @if($search || $status)
                <x-button href="{{ route('admin.product-attributes.index') }}" variant="secondary">Reset</x-button>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Attribute Name</th>
                    <th>Slug</th>
                    <th>Type</th>
                    <th>Values</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attributes as $attr)
                    <tr>
                        <td>
                            <div class="text-body-emphasis">{{ $attr->name }}</div>
                            @if($attr->description)
                                <div class="text-caption">{{ Str::limit($attr->description, 50) }}</div>
                            @endif
                        </td>
                        <td><code class="text-xs bg-surface-muted px-2 py-1 rounded">{{ $attr->slug }}</code></td>
                        <td><span class="text-caption font-medium">{{ $attr->getInputTypeLabel() }}</span></td>
                        <td>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-info-bg text-info-text">
                                {{ $attr->values_count }}
                            </span>
                        </td>
                        <td>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-brand-50 text-brand-700">
                                {{ $attr->products_count }}
                            </span>
                        </td>
                        <td>
                            @if($attr->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success-text">Active</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="relative inline-block" x-data="{ open: false }">
                                <button @click="open = !open" @click.stop class="p-1.5 rounded-lg hover:bg-surface-muted transition-colors">
                                    <x-lucide-more-vertical class="w-4 h-4 text-ink-600" />
                                </button>
                                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-1 w-40 bg-white border border-surface-border rounded-xl shadow-lg z-20 py-1 top-full" style="display: none;">
                                    <a href="{{ route('admin.product-attributes.edit', $attr->id) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-900 hover:bg-surface-muted">
                                        <x-lucide-pencil class="w-4 h-4" />
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.product-attributes.destroy', $attr->id) }}" class="delete-attr-form contents">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-danger-text hover:bg-danger-bg">
                                            <x-lucide-trash-2 class="w-4 h-4" />
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-inbox class="w-12 h-12" />
                                <p class="text-sm font-medium">No attributes found</p>
                                <x-button href="{{ route('admin.product-attributes.create') }}" variant="primary" class="mt-3">
                                    <x-lucide-plus class="w-4 h-4" />
                                    Create First Attribute
                                </x-button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($attributes->hasPages())
        <div class="border-t border-surface-border px-6 py-4">
            {{ $attributes->links() }}
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('submit', async function (e) {
        const form = e.target.closest('.delete-attr-form');
        if (!form) return;
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this attribute and all its values?')) return;

        const row = form.closest('tr');
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
                if (window.toast) window.toast(data.message || 'Attribute deleted', 'success');
                if (row) row.remove();
            } else {
                if (window.toast) window.toast(data.message || 'Failed to delete attribute', 'error');
            }
        } catch (err) {
            console.error(err);
            if (window.toast) window.toast('Network error.', 'error');
        }
    });
});
</script>
@endpush
