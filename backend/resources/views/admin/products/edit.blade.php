@extends('admin.layouts.app')

@section('title', 'Edit Product — ' . $product->name)
@section('breadcrumb', 'Admin / Products / Edit')
@section('page_title', 'Edit Product')

@section('content')

    <div class="mb-5">
        <a href="{{ route('admin.products.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950 transition">
            ← Back to Products
        </a>
    </div>

    <form method="POST" action="{{ route('admin.products.update', $product->id) }}" id="productForm">
        @csrf
        @method('PUT')

        {{-- hidden: which unit row index is the default --}}
        <input type="hidden" name="default_unit_index" id="defaultUnitIndex" value="0">

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- ── LEFT: Product Info ── --}}
            <div class="xl:col-span-2 space-y-6">

                <section class="panel">
                    <div class="panel-header">
                        <h2 class="font-semibold text-slate-950">Product Information</h2>
                        <p class="mt-1 text-sm text-slate-500">Update product details below.</p>
                    </div>
                    <div class="p-6 space-y-5">

                        {{-- Name --}}
                        <div>
                            <label class="form-label">Product Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name"
                                   value="{{ old('name', $product->name) }}"
                                   class="form-input @error('name') border-red-400 @enderror"
                                   placeholder="e.g., Amul Butter" required>
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            {{-- Code / SKU --}}
                            <div>
                                <label class="form-label">Product Code / SKU</label>
                                <input type="text" name="code"
                                       value="{{ old('code', $product->code) }}"
                                       class="form-input @error('code') border-red-400 @enderror"
                                       placeholder="e.g., AMB-100">
                                @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Description with Rich Text Editor --}}
                        <div>
                            <label class="form-label">Description</label>
                            <div id="editor-description" style="height: 300px;" class="rounded-lg border border-slate-300"></div>
                            <textarea name="description" id="description-input" style="display:none;" class="@error('description') border-red-400 @enderror">{{ old('description', $product->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </section>

                {{-- ── Images & Gallery ── --}}
                <section class="panel">
                    <div class="panel-header">
                        <h2 class="font-semibold text-slate-950">Product Images</h2>
                        <p class="mt-1 text-sm text-slate-500">Upload and manage product gallery</p>
                    </div>
                    <div class="p-6">
                        {{-- Upload Area --}}
                        <div class="mb-6">
                            <div id="dropzone" class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center cursor-pointer hover:border-slate-400 hover:bg-slate-50 transition">
                                <x-lucide-image class="w-12 h-12 mx-auto text-slate-400 mb-3" />
                                <p class="text-sm font-semibold text-slate-900">Drop images here or click to upload</p>
                                <p class="text-xs text-slate-500 mt-1">PNG, JPG, GIF up to 5MB</p>
                                <input type="file" id="imageInput" multiple accept="image/*" style="display: none;">
                            </div>
                            <div id="uploadProgress" style="display: none;" class="mt-4">
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <div id="progressBar" class="bg-violet-600 h-2 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Gallery --}}
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 mb-3">Gallery</h3>
                            <div id="gallery" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @forelse($product->images as $image)
                                    <div class="relative group image-item" data-image-id="{{ $image->id }}">
                                        <img src="{{ $image->url }}" alt="{{ $image->alt_text }}" class="w-full h-32 object-cover rounded-lg border border-slate-200">
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 rounded-lg transition flex items-center justify-center gap-2">
                                            @if($image->is_featured)
                                                <span class="bg-violet-600 text-white text-xs px-2 py-1 rounded">Featured</span>
                                            @else
                                                <button type="button" class="featured-btn bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs" data-image-id="{{ $image->id }}">Set Featured</button>
                                            @endif
                                            <button type="button" class="delete-btn bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs" data-image-id="{{ $image->id }}">Delete</button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="col-span-full text-center text-sm text-slate-500 py-8">No images yet. Upload some to get started!</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ── Units & Prices ── --}}
                <section class="panel">
                    <div class="panel-header flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-slate-950">Units &amp; Prices</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Edit units and prices. All existing units will be replaced on save.
                            </p>
                        </div>
                        <button type="button" id="addUnitBtn"
                                class="inline-flex items-center gap-1.5 rounded-md bg-violet-50 px-3 py-2 text-sm font-bold text-violet-700 hover:bg-violet-100 transition border border-violet-200">
                            + Add Unit
                        </button>
                    </div>

                    @error('units')<p class="px-5 pt-3 text-sm text-red-600">{{ $message }}</p>@enderror

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm" id="unitsTable">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 text-left">Unit Name <span class="text-red-500">*</span></th>
                                    <th class="px-5 py-3 text-left">Price (₹) <span class="text-red-500">*</span></th>
                                    <th class="px-5 py-3 text-center">Default</th>
                                    <th class="px-5 py-3 text-center">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="unitsBody" class="divide-y divide-slate-100">
                            </tbody>
                        </table>
                    </div>

                    <div id="noUnitsMsg" class="hidden py-10 text-center text-sm text-slate-400">
                        No units added yet. Click <strong>+ Add Unit</strong> to start.
                    </div>
                </section>

            </div>

            {{-- ── RIGHT: Status + Submit ── --}}
            <div class="space-y-5">

                <section class="panel">
                    <div class="panel-header">
                        <h3 class="font-semibold text-slate-950">Status</h3>
                    </div>
                    <div class="p-5">
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="active"
                                       @checked(old('status', $product->status) === 'active')
                                       class="text-violet-600">
                                <span class="text-sm font-semibold text-emerald-700">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="inactive"
                                       @checked(old('status', $product->status) === 'inactive')
                                       class="text-violet-600">
                                <span class="text-sm font-semibold text-slate-500">Inactive</span>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- Quick-add unit hints --}}
                <section class="panel">
                    <div class="panel-header">
                        <h3 class="font-semibold text-slate-950">💡 Common Units</h3>
                    </div>
                    <div class="p-4 flex flex-wrap gap-2">
                        @foreach(['Piece','Box','Carton','Kg','Gram','Liter','ML','Pack','Dozen','Set','Pair','Roll','Sheet','Bag','Can','Bottle'] as $u)
                            <button type="button"
                                    class="unit-hint rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 hover:bg-violet-100 transition"
                                    data-unit="{{ $u }}">
                                {{ $u }}
                            </button>
                        @endforeach
                    </div>
                    <p class="px-4 pb-3 text-xs text-slate-400">Click a unit to add it as a new row.</p>
                </section>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn-primary w-full justify-center">
                        💾 Update Product
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn-secondary w-full justify-center">
                        Cancel
                    </a>
                </div>

            </div>
        </div>

        {{-- ── Attributes & Variants Section ── --}}
        <div class="mt-6">
            <section class="panel">
                <div class="panel-header flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-950">Attributes &amp; Variants</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Create product variations based on attributes like color and size.
                        </p>
                    </div>
                    <a href="{{ route('admin.product-attributes.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-100 transition border border-amber-200">
                        ⚙ Manage Attributes
                    </a>
                </div>
                <div class="p-6 space-y-6">

                    {{-- Applied Attributes Selection --}}
                    <div>
                        <label class="form-label">Product Attributes</label>
                        <p class="text-xs text-slate-500 mb-3">Select which attributes apply to this product</p>
                        @php
                            $allAttributes = \App\Models\ProductAttribute::where('is_active', true)->get();
                            $appliedAttrIds = $product->attributes->pluck('id')->toArray();
                        @endphp
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                            @forelse($allAttributes as $attr)
                                <label class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 p-2 rounded">
                                    <input type="checkbox" name="attributes[]" value="{{ $attr->id }}"
                                           @checked(in_array($attr->id, $appliedAttrIds))
                                           class="w-4 h-4 text-violet-600 rounded">
                                    <span class="text-sm font-medium text-slate-900">{{ $attr->name }}</span>
                                    <span class="text-xs text-slate-500">({{ $attr->values->count() }} values)</span>
                                </label>
                            @empty
                                <p class="text-sm text-slate-500 py-3">No attributes available. <a href="{{ route('admin.product-attributes.create') }}" class="text-violet-600 hover:underline">Create one first</a></p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Existing Variants --}}
                    @if($product->variants->count() > 0)
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 mb-3">Existing Variants</h3>
                            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700">Attributes</th>
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700">SKU</th>
                                            <th class="px-4 py-2 text-center font-semibold text-slate-700">Price</th>
                                            <th class="px-4 py-2 text-center font-semibold text-slate-700">Stock</th>
                                            <th class="px-4 py-2 text-center font-semibold text-slate-700">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach($product->variants as $variant)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap gap-1">
                                                        @forelse($variant->variantAttributes as $va)
                                                            @php
                                                                $attr = $va->attribute;
                                                                $value = $va->productAttributeValue;
                                                            @endphp
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-violet-100 text-violet-700">
                                                                {{ $attr->name }}: <strong>{{ $value->value }}</strong>
                                                            </span>
                                                        @empty
                                                            <span class="text-xs text-slate-500">No attributes</span>
                                                        @endforelse
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-900 font-mono text-xs">{{ $variant->sku ?? '—' }}</td>
                                                <td class="px-4 py-3 text-center">₹{{ number_format($variant->price ?? 0, 2) }}</td>
                                                <td class="px-4 py-3 text-center">{{ $variant->stock_quantity ?? 0 }}</td>
                                                <td class="px-4 py-3 text-center flex items-center justify-center gap-2">
                                                    <button type="button" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Edit</button>
                                                    <button type="button" class="text-red-600 hover:text-red-800 text-xs font-medium" onclick="return confirm('Delete this variant?')">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Generate Variants Helper --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-900 mb-2">
                            <strong>💡 Tip:</strong> After selecting attributes above, you can bulk-generate all possible variant combinations.
                        </p>
                        <button type="button" id="generateVariantsBtn"
                                class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-bold text-white hover:bg-blue-700 transition">
                            🔄 Generate Variants
                        </button>
                    </div>

                </div>
            </section>
        </div>

                {{-- Danger zone --}}
                <section class="panel border-red-200">
                    <div class="panel-header border-red-100">
                        <h3 class="font-semibold text-red-700">Danger Zone</h3>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="{{ route('admin.products.destroy', $product->id) }}"
                              onsubmit="return confirm('Delete this product permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger w-full justify-center">
                                🗑 Delete Product
                            </button>
                        </form>
                    </div>
                </section>

            </div>
        </div>
    </form>

@endsection

@push('scripts')
<script>
// Initialize Quill Editor for Description
document.addEventListener('DOMContentLoaded', function () {
    var quill = new Quill('#editor-description', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'image'],
                [{ 'header': [1, 2, 3, false] }]
            ]
        },
        placeholder: 'Enter product description...'
    });

    // Load existing description into editor
    var descriptionText = document.getElementById('description-input').value;
    if (descriptionText) {
        quill.root.innerHTML = descriptionText;
    }

    // Save editor content to textarea before form submit
    document.getElementById('productForm').addEventListener('submit', function() {
        document.getElementById('description-input').value = quill.root.innerHTML;
    });

    // ── Image Upload & Gallery ──────────────────────────────────────────
    const productId = {{ $product->id }};
    const dropzone = document.getElementById('dropzone');
    const imageInput = document.getElementById('imageInput');
    const gallery = document.getElementById('gallery');
    const progressBar = document.getElementById('progressBar');
    const uploadProgress = document.getElementById('uploadProgress');

    // Click to upload
    dropzone.addEventListener('click', () => imageInput.click());

    // Drag and drop
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
        handleFiles(e.dataTransfer.files);
    });

    imageInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        Array.from(files).forEach(file => uploadImage(file));
    }

    function uploadImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        uploadProgress.style.display = 'block';
        progressBar.style.width = '0%';

        fetch(`/admin/products/${productId}/images`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                addImageToGallery(data.image, data.url);
                progressBar.style.width = '100%';
                setTimeout(() => uploadProgress.style.display = 'none', 500);
            }
        })
        .catch(err => console.error(err));
    }

    function addImageToGallery(image, url) {
        const div = document.createElement('div');
        div.className = 'relative group image-item';
        div.dataset.imageId = image.id;
        div.innerHTML = `
            <img src="${url}" class="w-full h-32 object-cover rounded-lg border border-slate-200">
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 rounded-lg transition flex items-center justify-center gap-2">
                <button type="button" class="featured-btn bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs" data-image-id="${image.id}">Set Featured</button>
                <button type="button" class="delete-btn bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs" data-image-id="${image.id}">Delete</button>
            </div>
        `;
        gallery.appendChild(div);
        attachImageEvents(div);
    }

    function attachImageEvents(item) {
        item.querySelector('.featured-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            const imageId = this.dataset.imageId;
            fetch(`/admin/products/images/${imageId}/featured`, {
                method: 'PATCH',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
            });
        });

        item.querySelector('.delete-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Delete this image?')) return;
            const imageId = this.dataset.imageId;
            fetch(`/admin/products/images/${imageId}`, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) item.remove();
            });
        });
    }

    // Attach events to existing images
    document.querySelectorAll('.image-item').forEach(attachImageEvents);
});

(function () {
    'use strict';

    const COMMON_UNITS  = ['Piece','Box','Carton','Kg','Gram','Liter','ML','Pack','Dozen','Set','Pair','Roll','Sheet','Bag','Can','Bottle'];
    const body          = document.getElementById('unitsBody');
    const addBtn        = document.getElementById('addUnitBtn');
    const defIndexInput = document.getElementById('defaultUnitIndex');

    let rowIndex = 0;

    function addRow(unitName, price, isDefault) {
        const idx = rowIndex++;
        const tr  = document.createElement('tr');
        tr.className = 'unit-row hover:bg-slate-50 transition';
        tr.dataset.idx = idx;

        tr.innerHTML = `
            <td class="px-4 py-3">
                <input type="text"
                       name="units[${idx}][unit_name]"
                       value="${escHtml(unitName)}"
                       class="form-input"
                       placeholder="e.g., Box"
                       list="unitDatalist"
                       required>
            </td>
            <td class="px-4 py-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm font-semibold">₹</span>
                    <input type="number"
                           name="units[${idx}][price]"
                           value="${escHtml(price)}"
                           class="form-input pl-7"
                           placeholder="0.00"
                           step="0.01"
                           min="0"
                           required>
                </div>
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button"
                        class="default-btn inline-flex h-7 w-7 items-center justify-center rounded-full border-2 transition
                               ${isDefault ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-300 bg-white text-transparent'}"
                        title="Set as default">
                    ✓
                </button>
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button"
                        class="remove-btn inline-flex h-7 w-7 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition text-xs font-bold">
                    ✕
                </button>
            </td>
        `;

        tr.querySelector('.default-btn').addEventListener('click', () => setDefault(idx));
        tr.querySelector('.remove-btn').addEventListener('click', function () {
            const wasDefault = (parseInt(defIndexInput.value) === idx);
            tr.remove();
            syncNoUnitsMsg();
            if (wasDefault) {
                const first = body.querySelector('.unit-row');
                if (first) setDefault(parseInt(first.dataset.idx));
            }
        });

        if (isDefault) defIndexInput.value = idx;
        body.appendChild(tr);
        syncNoUnitsMsg();
    }

    function setDefault(targetIdx) {
        defIndexInput.value = targetIdx;
        body.querySelectorAll('.unit-row').forEach(row => {
            const btn = row.querySelector('.default-btn');
            const isTarget = parseInt(row.dataset.idx) === targetIdx;
            btn.classList.toggle('border-violet-600', isTarget);
            btn.classList.toggle('bg-violet-600',    isTarget);
            btn.classList.toggle('text-white',        isTarget);
            btn.classList.toggle('border-slate-300', !isTarget);
            btn.classList.toggle('bg-white',          !isTarget);
            btn.classList.toggle('text-transparent',  !isTarget);
        });
    }

    function syncNoUnitsMsg() {
        const hasRows = body.querySelectorAll('.unit-row').length > 0;
        document.getElementById('noUnitsMsg').classList.toggle('hidden', hasRows);
        document.getElementById('unitsTable').classList.toggle('hidden', !hasRows);
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Datalist
    (function () {
        const dl = document.createElement('datalist');
        dl.id = 'unitDatalist';
        COMMON_UNITS.forEach(u => { const o = document.createElement('option'); o.value = u; dl.appendChild(o); });
        document.body.appendChild(dl);
    })();

    addBtn.addEventListener('click', () => addRow('', '', rowIndex === 0));

    document.querySelectorAll('.unit-hint').forEach(btn => {
        btn.addEventListener('click', function () { addRow(this.dataset.unit, '', rowIndex === 0); });
    });

    // ── Load existing units from server ──────────────────────────────────
    @if(old('units'))
        @foreach(old('units') as $i => $u)
            addRow('{{ addslashes($u['unit_name'] ?? '') }}', '{{ $u['price'] ?? '' }}', {{ old('default_unit_index', 0) == $i ? 'true' : 'false' }});
        @endforeach
    @else
        @foreach($product->units as $i => $unit)
            addRow(
                '{{ addslashes($unit->unit_name) }}',
                '{{ $unit->price }}',
                {{ $unit->is_default ? 'true' : 'false' }}
            );
        @endforeach
    @endif

    syncNoUnitsMsg();

})();
</script>
@endpush
