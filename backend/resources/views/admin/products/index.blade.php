@extends('admin.layouts.app')

@section('title', 'Products')
@section('breadcrumb', 'ADMIN / PRODUCTS')
@section('page_title', 'Product Master')

@section('content')

@if(session('status'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('status') }}</p>
    </div>
@endif

<div class="panel">
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">All Products</h2>
                <p class="text-caption mt-1">Manage products with multiple units and prices.</p>
            </div>
            <x-button href="{{ route('admin.products.create') }}" variant="primary">
                <x-lucide-plus class="w-4 h-4" />
                Add Product
            </x-button>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.products.index') }}" class="mt-4 flex flex-wrap gap-3 items-end">
            <div class="relative flex-1 min-w-[200px]">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                <input name="q" value="{{ $search }}" class="form-input pl-9" placeholder="Search name, code…">
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
                <x-button href="{{ route('admin.products.index') }}" variant="secondary">Reset</x-button>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Units &amp; Prices</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $index => $product)
                    <tr>
                        <td class="text-caption">{{ $products->firstItem() + $index }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">
                                    {{ strtoupper(substr($product->name, 0, 2)) }}
                                </span>
                                <div>
                                    <div class="text-body-emphasis">{{ $product->name }}</div>
                                    @if($product->code)
                                        <div class="font-mono text-caption">{{ $product->code }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-body">{{ $product->category ?? '—' }}</td>
                        <td>
                            @forelse($product->units as $unit)
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="badge badge-neutral text-xs">{{ $unit->unit_name }}</span>
                                    <span class="text-sm font-bold text-ink-900 tabular-nums">₹{{ number_format($unit->price, 2) }}</span>
                                    @if($unit->is_default)
                                        <span class="badge badge-info text-[10px]">default</span>
                                    @endif
                                </div>
                            @empty
                                <span class="text-caption italic">No units</span>
                            @endforelse
                        </td>
                        <td>
                            <x-status-badge :status="$product->status" />
                        </td>
                        <td>
                            <x-row-actions :actions="[
                                [
                                    'type'  => 'link',
                                    'label' => 'Edit',
                                    'icon'  => 'pencil',
                                    'href'  => route('admin.products.edit', $product->id),
                                ],
                                [
                                    'type'    => 'form',
                                    'label'   => $product->status === 'active' ? 'Deactivate' : 'Activate',
                                    'icon'    => 'power',
                                    'action'  => route('admin.products.toggleStatus', $product->id),
                                    'method'  => 'PATCH',
                                    'variant' => $product->status === 'active' ? 'danger' : 'default',
                                ],
                                ['divider' => true, 'type' => 'link', 'label' => ''],
                                [
                                    'type'    => 'form',
                                    'label'   => 'Delete',
                                    'icon'    => 'trash-2',
                                    'action'  => route('admin.products.destroy', $product->id),
                                    'method'  => 'DELETE',
                                    'confirm' => 'Delete product ' . addslashes($product->name) . '?',
                                    'variant' => 'danger',
                                ],
                            ]" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-package class="w-10 h-10" />
                                <p class="text-sm font-medium">No products found.</p>
                                <x-button href="{{ route('admin.products.create') }}" variant="primary" class="mt-2">
                                    <x-lucide-plus class="w-4 h-4" />
                                    Add First Product
                                </x-button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
        <div class="border-t border-surface-border px-6 py-4">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
