@extends('admin.layouts.app')

@section('title', 'Products')
@section('breadcrumb', 'ADMIN / PRODUCTS')
@section('page_title', 'Product Master')

@section('content')
<div x-data="productManager({
    initialProducts: {{ Js::from($formattedProducts) }},
    initialStats: {
        total: {{ $totalCount }},
        active: {{ $activeCount }},
        inactive: {{ $inactiveCount }}
    },
    initialPagination: {
        total: {{ $products->total() }},
        current_page: {{ $products->currentPage() }},
        last_page: {{ $products->lastPage() }},
        per_page: {{ $products->perPage() }},
        from: {{ $products->firstItem() ?? 0 }},
        to: {{ $products->lastItem() ?? 0 }}
    },
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Header & Stats Summary --}}
    <div class="panel p-6 border border-surface-border shadow-sm rounded-2xl">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h2 class="text-base font-bold text-ink-900">All Products Directory</h2>
                <p class="text-caption mt-0.5 text-xs text-ink-500">Manage catalog products, multi-unit pricing, and active selling statuses.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                {{-- Live Search --}}
                <div class="relative w-full sm:w-60">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text"
                           x-model="search"
                           @input.debounce.300ms="fetchProducts(1)"
                           placeholder="Search name, code…"
                           class="form-input text-xs pl-9 w-full">
                </div>

                {{-- Status Filter --}}
                <select x-model="statusFilter" @change="fetchProducts(1)" class="form-input text-xs w-auto">
                    <option value="">All Status (<span x-text="stats.total"></span>)</option>
                    <option value="active">Active Only (<span x-text="stats.active"></span>)</option>
                    <option value="inactive">Inactive Only (<span x-text="stats.inactive"></span>)</option>
                </select>

                {{-- Add Product Button --}}
                <button type="button"
                        @click="openCreateModal()"
                        class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-2"
                        style="background-color: #4f46e5; color: #ffffff;">
                    <x-lucide-plus class="w-4 h-4" />
                    Add Product
                </button>
            </div>
        </div>
    </div>

    {{-- Data Table Panel --}}
    <div class="panel overflow-hidden border border-surface-border shadow-sm rounded-2xl relative">
        
        {{-- Loading Overlay --}}
        <div x-show="loading"
             x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-white/75 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center gap-3">
            <svg class="animate-spin h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-bold text-ink-600">Updating product catalog...</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Code</th>
                        <th>Units &amp; Prices</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border">
                    <template x-for="product in products" :key="product.id">
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-xs font-bold text-white shadow-xs"
                                          x-text="product.name.slice(0, 2).toUpperCase()"></span>
                                    <div>
                                        <div class="font-bold text-ink-900 text-xs" x-text="product.name"></div>
                                        <template x-if="product.description">
                                            <div class="text-[11px] text-ink-500 line-clamp-1 mt-0.5" x-text="product.description"></div>
                                        </template>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="font-mono text-xs font-bold text-ink-700" x-text="product.code || '—'"></span>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template x-for="unit in product.units" :key="unit.id || unit.unit_name">
                                        <div class="inline-flex items-center gap-1 bg-surface-muted px-2 py-1 rounded-lg border border-surface-border text-xs">
                                            <span class="font-semibold text-ink-700" x-text="unit.unit_name"></span>
                                            <span class="font-bold text-ink-900 font-mono" x-text="`₹${unit.price_formatted || Number(unit.price).toFixed(2)}`"></span>
                                            <template x-if="unit.is_default">
                                                <span class="px-1 py-0.2 rounded text-[9px] font-extrabold bg-indigo-100 text-indigo-800">default</span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td>
                                <button type="button"
                                        @click="toggleStatus(product)"
                                        class="cursor-pointer transition-transform active:scale-95"
                                        :title="product.status === 'active' ? 'Click to Deactivate' : 'Click to Activate'">
                                    <span x-show="product.status === 'active'" class="badge badge-success text-xs inline-flex items-center gap-1 hover:opacity-80">
                                        ● Active
                                    </span>
                                    <span x-show="product.status !== 'active'" class="badge badge-danger text-xs inline-flex items-center gap-1 hover:opacity-80">
                                        ○ Inactive
                                    </span>
                                </button>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    {{-- Edit --}}
                                    <button type="button"
                                            @click="openEditModal(product)"
                                            class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-600 hover:bg-surface-muted hover:text-ink-900 transition-colors shadow-xs"
                                            title="Edit Product & Pricing">
                                        <x-lucide-pencil class="w-3.5 h-3.5" />
                                    </button>

                                    {{-- Delete --}}
                                    <button type="button"
                                            @click="deleteProduct(product)"
                                            class="p-1.5 rounded-lg border border-red-200 bg-white text-rose-600 hover:bg-rose-50 transition-colors shadow-xs"
                                            title="Delete Product">
                                        <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty State --}}
                    <tr x-show="!loading && products.length === 0">
                        <td colspan="5" class="text-center py-16 text-ink-400">
                            <div class="flex flex-col items-center gap-2">
                                <x-lucide-package-x class="w-10 h-10 stroke-1 text-ink-300" />
                                <p class="text-xs font-semibold text-ink-600">No products matching filter criteria.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div x-show="pagination.last_page > 1" class="p-4 border-t border-surface-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-ink-600">
            <div>
                Showing <span class="font-bold text-ink-900" x-text="pagination.from || 0"></span> to
                <span class="font-bold text-ink-900" x-text="pagination.to || 0"></span> of
                <span class="font-bold text-ink-900" x-text="pagination.total"></span> products
            </div>

            <div class="inline-flex items-center gap-1.5">
                <button type="button"
                        @click="fetchProducts(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-700 hover:bg-surface-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <x-lucide-chevron-left class="w-4 h-4" />
                </button>

                <span class="px-2.5 py-1 font-bold text-ink-900 bg-surface-muted rounded-lg"
                      x-text="`Page ${pagination.current_page} of ${pagination.last_page}`"></span>

                <button type="button"
                        @click="fetchProducts(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-700 hover:bg-surface-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <x-lucide-chevron-right class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: CREATE PRODUCT (AJAX)                                              --}}
    {{-- ========================================================================= --}}
    <div x-show="createModal.open" x-cloak style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="createModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div x-show="createModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="createModal.open = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

            {{-- Dialog --}}
            <div x-show="createModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-xl w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                        <x-lucide-package-plus class="w-5 h-5 text-indigo-600" />
                        Add New Product
                    </h3>
                    <button type="button" @click="createModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate()" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div class="sm:col-span-2">
                            <label class="form-label text-xs">Product Name <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   x-model="createModal.form.name"
                                   required
                                   placeholder="e.g. Industrial Lubricant 5L"
                                   class="form-input text-xs w-full"
                                   :class="createModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                            <template x-if="createModal.errors.name">
                                <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="createModal.errors.name[0]"></p>
                            </template>
                        </div>

                        {{-- Code / SKU --}}
                        <div>
                            <label class="form-label text-xs">Product Code / SKU</label>
                            <input type="text"
                                   x-model="createModal.form.code"
                                   placeholder="e.g. LUB-5001"
                                   class="form-input text-xs font-mono uppercase w-full"
                                   :class="createModal.errors.code ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                            <template x-if="createModal.errors.code">
                                <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="createModal.errors.code[0]"></p>
                            </template>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="form-label text-xs">Status <span class="text-rose-500">*</span></label>
                            <select x-model="createModal.form.status" required class="form-input text-xs w-full">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        {{-- Description --}}
                        <div class="sm:col-span-2">
                            <label class="form-label text-xs">Description</label>
                            <textarea x-model="createModal.form.description"
                                      rows="2"
                                      placeholder="Optional product description..."
                                      class="form-input text-xs w-full"></textarea>
                        </div>
                    </div>

                    {{-- Multi-Units & Pricing Repeater --}}
                    <div class="pt-3 border-t border-surface-border">
                        <div class="flex items-center justify-between mb-2">
                            <label class="form-label text-xs font-bold mb-0">Pricing Units <span class="text-rose-500">*</span></label>
                            <button type="button"
                                    @click="addCreateUnit()"
                                    class="text-xs text-indigo-600 font-bold hover:underline inline-flex items-center gap-1">
                                <x-lucide-plus class="w-3.5 h-3.5" /> Add Another Unit
                            </button>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <template x-for="(u, index) in createModal.form.units" :key="index">
                                <div class="flex items-center gap-2 p-2 bg-surface-muted rounded-xl border border-surface-border">
                                    <div class="flex-1">
                                        <input type="text"
                                               x-model="u.unit_name"
                                               required
                                               placeholder="Unit Name (e.g. Box, Piece, Kg)"
                                               class="form-input text-xs w-full py-1.5">
                                    </div>
                                    <div class="w-28">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               x-model.number="u.price"
                                               required
                                               placeholder="Price ₹"
                                               class="form-input text-xs font-mono w-full py-1.5">
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2">
                                        <input type="radio"
                                               :name="'default_unit_create'"
                                               :checked="createModal.form.default_unit_index === index"
                                               @change="createModal.form.default_unit_index = index"
                                               class="rounded-full text-indigo-600">
                                        <span class="text-[10px] font-bold text-ink-600">Default</span>
                                    </div>
                                    <button type="button"
                                            @click="removeCreateUnit(index)"
                                            :disabled="createModal.form.units.length <= 1"
                                            class="p-1 text-rose-500 hover:bg-rose-50 rounded disabled:opacity-30">
                                        <x-lucide-trash class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-surface-border">
                        <button type="button" @click="createModal.open = false" :disabled="createModal.saving" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="submit" :disabled="createModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="createModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="createModal.saving ? 'Saving...' : 'Save Product'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: EDIT PRODUCT (AJAX)                                                --}}
    {{-- ========================================================================= --}}
    <div x-show="editModal.open" x-cloak style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="editModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div x-show="editModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="editModal.open = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

            {{-- Dialog --}}
            <div x-show="editModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-xl w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                        <x-lucide-pencil class="w-4 h-4 text-indigo-600" />
                        Edit Product &amp; Pricing
                    </h3>
                    <button type="button" @click="editModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit()" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div class="sm:col-span-2">
                            <label class="form-label text-xs">Product Name <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   x-model="editModal.form.name"
                                   required
                                   class="form-input text-xs w-full"
                                   :class="editModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                            <template x-if="editModal.errors.name">
                                <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.name[0]"></p>
                            </template>
                        </div>

                        {{-- Code / SKU --}}
                        <div>
                            <label class="form-label text-xs">Product Code / SKU</label>
                            <input type="text"
                                   x-model="editModal.form.code"
                                   class="form-input text-xs font-mono uppercase w-full"
                                   :class="editModal.errors.code ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                            <template x-if="editModal.errors.code">
                                <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.code[0]"></p>
                            </template>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="form-label text-xs">Status <span class="text-rose-500">*</span></label>
                            <select x-model="editModal.form.status" required class="form-input text-xs w-full">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        {{-- Description --}}
                        <div class="sm:col-span-2">
                            <label class="form-label text-xs">Description</label>
                            <textarea x-model="editModal.form.description"
                                      rows="2"
                                      class="form-input text-xs w-full"></textarea>
                        </div>
                    </div>

                    {{-- Multi-Units & Pricing Repeater --}}
                    <div class="pt-3 border-t border-surface-border">
                        <div class="flex items-center justify-between mb-2">
                            <label class="form-label text-xs font-bold mb-0">Pricing Units <span class="text-rose-500">*</span></label>
                            <button type="button"
                                    @click="addEditUnit()"
                                    class="text-xs text-indigo-600 font-bold hover:underline inline-flex items-center gap-1">
                                <x-lucide-plus class="w-3.5 h-3.5" /> Add Another Unit
                            </button>
                        </div>

                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <template x-for="(u, index) in editModal.form.units" :key="index">
                                <div class="flex items-center gap-2 p-2 bg-surface-muted rounded-xl border border-surface-border">
                                    <div class="flex-1">
                                        <input type="text"
                                               x-model="u.unit_name"
                                               required
                                               placeholder="Unit Name"
                                               class="form-input text-xs w-full py-1.5">
                                    </div>
                                    <div class="w-28">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               x-model.number="u.price"
                                               required
                                               placeholder="Price ₹"
                                               class="form-input text-xs font-mono w-full py-1.5">
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2">
                                        <input type="radio"
                                               :name="'default_unit_edit'"
                                               :checked="editModal.form.default_unit_index === index"
                                               @change="editModal.form.default_unit_index = index"
                                               class="rounded-full text-indigo-600">
                                        <span class="text-[10px] font-bold text-ink-600">Default</span>
                                    </div>
                                    <button type="button"
                                            @click="removeEditUnit(index)"
                                            :disabled="editModal.form.units.length <= 1"
                                            class="p-1 text-rose-500 hover:bg-rose-50 rounded disabled:opacity-30">
                                        <x-lucide-trash class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-surface-border">
                        <button type="button" @click="editModal.open = false" :disabled="editModal.saving" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="editModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="editModal.saving ? 'Saving...' : 'Update Product'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function productManager(config) {
    return {
        products: config.initialProducts || [],
        stats: config.initialStats || { total: 0, active: 0, inactive: 0 },
        pagination: config.initialPagination || {},
        csrfToken: config.csrfToken,

        search: '',
        statusFilter: '',
        loading: false,

        createModal: {
            open: false,
            saving: false,
            form: {
                name: '',
                code: '',
                status: 'active',
                description: '',
                units: [{ unit_name: 'Piece', price: 0, is_default: true }],
                default_unit_index: 0
            },
            errors: {}
        },

        editModal: {
            open: false,
            id: null,
            saving: false,
            form: {
                name: '',
                code: '',
                status: 'active',
                description: '',
                units: [],
                default_unit_index: 0
            },
            errors: {}
        },

        async fetchProducts(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page });
                if (this.search) params.append('q', this.search);
                if (this.statusFilter) params.append('status', this.statusFilter);

                const url = `/admin/products?${params.toString()}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.products = data.products || [];
                    this.stats = data.stats || this.stats;
                    this.pagination = data.pagination || {};
                    window.history.pushState(null, '', url);
                } else {
                    window.toast('Failed to load products.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error loading product catalog.', 'error');
            } finally {
                this.loading = false;
            }
        },

        openCreateModal() {
            this.createModal.form = {
                name: '',
                code: '',
                status: 'active',
                description: '',
                units: [{ unit_name: 'Piece', price: 0, is_default: true }],
                default_unit_index: 0
            };
            this.createModal.errors = {};
            this.createModal.open = true;
        },

        addCreateUnit() {
            this.createModal.form.units.push({
                unit_name: '',
                price: 0,
                is_default: false
            });
        },

        removeCreateUnit(index) {
            if (this.createModal.form.units.length > 1) {
                this.createModal.form.units.splice(index, 1);
                if (this.createModal.form.default_unit_index >= this.createModal.form.units.length) {
                    this.createModal.form.default_unit_index = 0;
                }
            }
        },

        async submitCreate() {
            this.createModal.saving = true;
            this.createModal.errors = {};

            // Ensure is_default flag is synchronized
            this.createModal.form.units.forEach((u, i) => {
                u.is_default = (i === this.createModal.form.default_unit_index);
            });

            try {
                const res = await fetch(`{{ route('admin.products.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.createModal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.products.unshift(data.product);
                    this.stats.total++;
                    if (data.product.status === 'active') this.stats.active++;
                    else this.stats.inactive++;
                    this.createModal.open = false;
                    window.toast(data.message || 'Product created successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.createModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to create product.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error saving product.', 'error');
            } finally {
                this.createModal.saving = false;
            }
        },

        openEditModal(product) {
            this.editModal.id = product.id;
            const units = (product.units && product.units.length > 0)
                ? product.units.map(u => ({ unit_name: u.unit_name, price: u.price, is_default: Boolean(u.is_default) }))
                : [{ unit_name: 'Piece', price: 0, is_default: true }];

            const defaultIdx = units.findIndex(u => u.is_default);

            this.editModal.form = {
                name: product.name,
                code: product.code || '',
                status: product.status || 'active',
                description: product.description || '',
                units: units,
                default_unit_index: defaultIdx > -1 ? defaultIdx : 0
            };
            this.editModal.errors = {};
            this.editModal.open = true;
        },

        addEditUnit() {
            this.editModal.form.units.push({
                unit_name: '',
                price: 0,
                is_default: false
            });
        },

        removeEditUnit(index) {
            if (this.editModal.form.units.length > 1) {
                this.editModal.form.units.splice(index, 1);
                if (this.editModal.form.default_unit_index >= this.editModal.form.units.length) {
                    this.editModal.form.default_unit_index = 0;
                }
            }
        },

        async submitEdit() {
            this.editModal.saving = true;
            this.editModal.errors = {};

            this.editModal.form.units.forEach((u, i) => {
                u.is_default = (i === this.editModal.form.default_unit_index);
            });

            try {
                const res = await fetch(`/admin/products/${this.editModal.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.editModal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.products.findIndex(p => p.id === this.editModal.id);
                    if (idx > -1 && data.product) {
                        this.products[idx] = data.product;
                    }
                    this.editModal.open = false;
                    window.toast(data.message || 'Product updated successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.editModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to update product.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error updating product.', 'error');
            } finally {
                this.editModal.saving = false;
            }
        },

        async toggleStatus(product) {
            try {
                const res = await fetch(`/admin/products/${product.id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.products.findIndex(p => p.id === product.id);
                    if (idx > -1 && data.product) {
                        const oldStatus = this.products[idx].status;
                        this.products[idx] = data.product;
                        if (oldStatus === 'active' && data.product.status === 'inactive') {
                            this.stats.active--;
                            this.stats.inactive++;
                        } else if (oldStatus === 'inactive' && data.product.status === 'active') {
                            this.stats.active++;
                            this.stats.inactive--;
                        }
                    }
                    window.toast(data.message || 'Status updated.', 'success');
                } else {
                    window.toast(data.message || 'Failed to toggle product status.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error toggling status.', 'error');
            }
        },

        async deleteProduct(product) {
            if (!confirm(`Are you sure you want to delete product '${product.name}'?`)) {
                return;
            }

            try {
                const res = await fetch(`/admin/products/${product.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.products = this.products.filter(p => p.id !== product.id);
                    this.stats.total--;
                    if (product.status === 'active') this.stats.active--;
                    else this.stats.inactive--;
                    window.toast(data.message || 'Product deleted.', 'success');
                } else {
                    window.toast(data.message || 'Failed to delete product.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error deleting product.', 'error');
            }
        }
    };
}
</script>
@endpush
