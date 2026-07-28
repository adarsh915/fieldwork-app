@extends('admin.layouts.app')

@section('title', 'Add Product')
@section('breadcrumb', 'Admin / Products / Create')
@section('page_title', 'Add New Product')

@section('content')

    <div class="mb-5">
        <a href="{{ route('admin.products.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950 transition">
            ← Back to Products
        </a>
    </div>

    <form method="POST" action="{{ route('admin.products.store') }}" id="productForm">
        @csrf

        {{-- hidden: which unit row index is the default --}}
        <input type="hidden" name="default_unit_index" id="defaultUnitIndex" value="0">

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- ── LEFT: Product Info ── --}}
            <div class="xl:col-span-2 space-y-6">

                <section class="panel">
                    <div class="panel-header">
                        <h2 class="font-semibold text-slate-950">Product Information</h2>
                        <p class="mt-1 text-sm text-slate-500">Fill in basic product details.</p>
                    </div>
                    <div class="p-6 space-y-5">

                        {{-- Name --}}
                        <div>
                            <label class="form-label">Product Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-input @error('name') border-red-400 @enderror"
                                   placeholder="e.g., Amul Butter" required>
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            {{-- Code / SKU --}}
                            <div>
                                <label class="form-label">Product Code / SKU</label>
                                <input type="text" name="code" value="{{ old('code') }}"
                                       class="form-input @error('code') border-red-400 @enderror"
                                       placeholder="e.g., AMB-100">
                                @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Description with Rich Text Editor --}}
                        <div>
                            <label class="form-label">Description</label>
                            <div id="editor-description" style="height: 300px;" class="rounded-lg border border-slate-300"></div>
                            <textarea name="description" id="description-input" style="display:none;" class="@error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </section>

                {{-- ── Units & Prices ── --}}
                <section class="panel">
                    <div class="panel-header flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-slate-950">Units &amp; Prices</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Add one or more selling units. Mark one as <strong>Default</strong>.
                            </p>
                        </div>
                        <button type="button" id="addUnitBtn"
                                class="inline-flex items-center gap-1.5 rounded-md bg-violet-50 px-3 py-2 text-sm font-bold text-violet-700 hover:bg-violet-100 transition border border-violet-200">
                            + Add Unit
                        </button>
                    </div>

                    @error('units')<p class="px-5 pt-3 text-sm text-red-600">{{ $message }}</p>@enderror

                    {{-- Units table --}}
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
                                {{-- JS will populate rows --}}
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
                                       @checked(old('status', 'active') === 'active')
                                       class="text-violet-600">
                                <span class="text-sm font-semibold text-emerald-700">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="inactive"
                                       @checked(old('status') === 'inactive')
                                       class="text-violet-600">
                                <span class="text-sm font-semibold text-slate-500">Inactive</span>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- Common Units hint --}}
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
                    <p class="px-4 pb-3 text-xs text-slate-400">Click a unit name to quickly add it as a new row.</p>
                </section>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn-primary w-full justify-center">
                        💾 Save Product
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn-secondary w-full justify-center">
                        Cancel
                    </a>
                </div>

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
});

(function () {
    'use strict';

    // ── Datalist for unit names ──────────────────────────────────────────────
    const COMMON_UNITS = ['Piece','Box','Carton','Kg','Gram','Liter','ML','Pack','Dozen','Set','Pair','Roll','Sheet','Bag','Can','Bottle'];

    const body          = document.getElementById('unitsBody');
    const addBtn        = document.getElementById('addUnitBtn');
    const noUnitsMsg    = document.getElementById('noUnitsMsg');
    const defIndexInput = document.getElementById('defaultUnitIndex');

    let rowIndex = 0;

    // ── Add a unit row ────────────────────────────────────────────────────────
    function addRow(unitName = '', price = '', isDefault = false) {
        const idx = rowIndex++;
        const tr  = document.createElement('tr');
        tr.className = 'unit-row hover:bg-slate-50 transition';
        tr.dataset.idx = idx;

        const unitOptions = COMMON_UNITS.map(u => `<option value="${u}">`).join('');

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

        // Set as default
        tr.querySelector('.default-btn').addEventListener('click', function () {
            setDefault(idx);
        });

        // Remove row
        tr.querySelector('.remove-btn').addEventListener('click', function () {
            const wasDefault = (parseInt(defIndexInput.value) === idx);
            tr.remove();
            syncNoUnitsMsg();
            if (wasDefault) {
                // Auto-set first remaining row as default
                const firstRow = body.querySelector('.unit-row');
                if (firstRow) setDefault(parseInt(firstRow.dataset.idx));
            }
        });

        if (isDefault) {
            defIndexInput.value = idx;
        }

        body.appendChild(tr);
        syncNoUnitsMsg();
    }

    // ── Set a row as the default ───────────────────────────────────────────
    function setDefault(targetIdx) {
        defIndexInput.value = targetIdx;
        body.querySelectorAll('.unit-row').forEach(function (row) {
            const btn = row.querySelector('.default-btn');
            if (parseInt(row.dataset.idx) === targetIdx) {
                btn.classList.add('border-violet-600', 'bg-violet-600', 'text-white');
                btn.classList.remove('border-slate-300', 'bg-white', 'text-transparent');
            } else {
                btn.classList.remove('border-violet-600', 'bg-violet-600', 'text-white');
                btn.classList.add('border-slate-300', 'bg-white', 'text-transparent');
            }
        });
    }

    // ── Show/hide no-units message ────────────────────────────────────────
    function syncNoUnitsMsg() {
        const hasRows = body.querySelectorAll('.unit-row').length > 0;
        document.getElementById('noUnitsMsg').classList.toggle('hidden', hasRows);
        document.getElementById('unitsTable').classList.toggle('hidden', !hasRows);
    }

    // ── Escape HTML ────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Datalist element (shared) ─────────────────────────────────────────
    (function buildDatalist() {
        const dl = document.createElement('datalist');
        dl.id = 'unitDatalist';
        COMMON_UNITS.forEach(u => { const opt = document.createElement('option'); opt.value = u; dl.appendChild(opt); });
        document.body.appendChild(dl);
    })();

    // ── Wire events ───────────────────────────────────────────────────────
    addBtn.addEventListener('click', () => addRow('', '', rowIndex === 0));

    document.querySelectorAll('.unit-hint').forEach(function (btn) {
        btn.addEventListener('click', function () {
            addRow(this.dataset.unit, '', rowIndex === 0);
        });
    });

    // ── Initialise: restore old() values OR add first blank row ──────────
    @if(old('units'))
        @foreach(old('units') as $i => $u)
            addRow(
                '{{ addslashes($u['unit_name'] ?? '') }}',
                '{{ $u['price'] ?? '' }}',
                {{ old('default_unit_index', 0) == $i ? 'true' : 'false' }}
            );
        @endforeach
    @else
        addRow('Piece', '', true);   // sensible default first row
    @endif

    syncNoUnitsMsg();

})();
</script>
@endpush
