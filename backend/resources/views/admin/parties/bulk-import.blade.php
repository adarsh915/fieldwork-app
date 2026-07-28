@extends('admin.layouts.app')

@section('title', 'Bulk Import Parties')
@section('breadcrumb', 'Admin / Parties / Bulk Import')
@section('page_title', 'Bulk Import Parties')

@section('content')

    {{-- Back --}}
    <div class="mb-5">
        <a href="{{ route('admin.parties.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950 transition">
            ← Back to Parties
        </a>
    </div>

    {{-- Import Result Banner --}}
    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 text-2xl">✅</span>
                <div class="flex-1">
                    <p class="font-bold text-emerald-800 text-base">Import Successful!</p>
                    <div class="mt-3 grid grid-cols-3 gap-3 max-w-sm">
                        <div class="rounded-lg bg-white border border-emerald-200 p-3 text-center">
                            <div class="text-2xl font-black text-emerald-700">{{ $result['created'] }}</div>
                            <div class="text-xs font-semibold text-emerald-600 mt-0.5">Created</div>
                        </div>
                        <div class="rounded-lg bg-white border border-amber-200 p-3 text-center">
                            <div class="text-2xl font-black text-amber-700">{{ $result['updated'] }}</div>
                            <div class="text-xs font-semibold text-amber-600 mt-0.5">Updated</div>
                        </div>
                        <div class="rounded-lg bg-white border border-slate-200 p-3 text-center">
                            <div class="text-2xl font-black text-slate-700">{{ $result['skipped'] }}</div>
                            <div class="text-xs font-semibold text-slate-500 mt-0.5">Skipped</div>
                        </div>
                    </div>

                    @if(!empty($result['errors']))
                        <div class="mt-4">
                            <p class="text-sm font-semibold text-amber-700 mb-1">⚠️ Warnings ({{ count($result['errors']) }} rows):</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($result['errors'] as $err)
                                    <li class="text-xs text-amber-700">{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('admin.parties.index') }}" class="btn-primary text-sm">
                            View All Parties →
                        </a>
                        <a href="{{ route('admin.parties.bulk-import') }}" class="btn-secondary text-sm">
                            Import More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ── LEFT: Upload Form ── --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Upload Card --}}
            <section class="panel">
                <div class="panel-header">
                    <h2 class="font-semibold text-slate-950">📤 Upload CSV File</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Upload a CSV file containing party details. All existing fields from the Add Party form
                        are supported — including optional employee assignment per row.
                    </p>
                </div>

                <form method="POST"
                      action="{{ route('admin.parties.bulk-import.process') }}"
                      enctype="multipart/form-data"
                      class="p-6 space-y-6"
                      id="importForm">
                    @csrf

                    {{-- File Upload Drop Zone --}}
                    <div>
                        <label class="form-label">
                            CSV File <span class="text-red-500">*</span>
                        </label>
                        <div id="dropZone"
                             class="mt-1 flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-indigo-400 hover:bg-indigo-50 cursor-pointer">
                            <span class="text-4xl">📄</span>
                            <div>
                                <p class="font-semibold text-slate-700">Drag & drop your CSV here</p>
                                <p class="mt-1 text-sm text-slate-500">or click to browse</p>
                            </div>
                            <input type="file"
                                   id="csv_file"
                                   name="csv_file"
                                   accept=".csv,.txt"
                                   class="hidden"
                                   required>
                            <div id="fileInfo" class="hidden mt-1">
                                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">
                                    📎 <span id="fileName"></span>
                                    <button type="button" id="clearFile" class="ml-1 text-indigo-500 hover:text-red-500 font-black">✕</button>
                                </span>
                            </div>
                        </div>
                        @error('csv_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-slate-400">Accepted: .csv — Max size: 2 MB</p>
                    </div>

                    {{-- Duplicate Handling --}}
                    <div>
                        <label class="form-label">When a party with the same name already exists</label>
                        <div class="mt-2 flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="duplicate_handling" value="skip" checked
                                       class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-slate-700">
                                    <span class="font-bold">Skip</span>
                                    <span class="text-slate-500">— keep existing record, only update employee assignment</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="duplicate_handling" value="update"
                                       class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-slate-700">
                                    <span class="font-bold">Update</span>
                                    <span class="text-slate-500">— overwrite mobile, email, address</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Assign All to Employee --}}
                    <div>
                        <label class="form-label" for="assign_employee_id">
                            Assign All Imported Parties to Employee
                            <span class="ml-1 text-slate-400 font-normal">(optional)</span>
                        </label>
                        <select name="assign_employee_id" id="assign_employee_id" class="form-input mt-1">
                            <option value="">— Do not assign globally —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->name }}
                                    @if($emp->email)
                                        ({{ $emp->email }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-400">
                            This assigns every imported party to the selected employee.
                            You can also assign individual rows using the <code class="bg-slate-100 px-1 rounded">employee_email</code> column in your CSV.
                        </p>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
                        <button type="submit" id="submitBtn" class="btn-primary gap-2">
                            <span id="submitLabel">⬆️ Import Parties</span>
                        </button>
                        <a href="{{ route('admin.parties.index') }}" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </section>

        </div>

        {{-- ── RIGHT: Instructions + Template ── --}}
        <div class="space-y-5">

            {{-- Download Template --}}
            <section class="panel">
                <div class="panel-header">
                    <h3 class="font-semibold text-slate-950">📥 Download Template</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Start with our ready-made CSV template. Open in Excel or Google Sheets, fill in your data, then save as CSV.
                    </p>
                </div>
                <div class="p-5">
                    <a href="{{ route('admin.parties.download-template') }}"
                       class="btn-primary w-full justify-center">
                        ⬇️ Download CSV Template
                    </a>
                </div>
            </section>

            {{-- Column Reference --}}
            <section class="panel">
                <div class="panel-header">
                    <h3 class="font-semibold text-slate-950">📋 Column Reference</h3>
                    <p class="mt-1 text-sm text-slate-500">First row must be the header. Column order doesn't matter.</p>
                </div>
                <div class="p-5">
                    <div class="space-y-3">

                        <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3">
                            <span class="mt-0.5 rounded bg-red-100 px-1.5 py-0.5 text-xs font-bold text-red-700">required</span>
                            <div>
                                <code class="text-xs font-bold text-slate-800">name</code>
                                <p class="mt-0.5 text-xs text-slate-500">Party / customer name. Used to detect duplicates.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3">
                            <span class="mt-0.5 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-500">optional</span>
                            <div>
                                <code class="text-xs font-bold text-slate-800">mobile</code>
                                <p class="mt-0.5 text-xs text-slate-500">Contact mobile number.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3">
                            <span class="mt-0.5 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-500">optional</span>
                            <div>
                                <code class="text-xs font-bold text-slate-800">email</code>
                                <p class="mt-0.5 text-xs text-slate-500">Contact email address.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3">
                            <span class="mt-0.5 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-500">optional</span>
                            <div>
                                <code class="text-xs font-bold text-slate-800">address</code>
                                <p class="mt-0.5 text-xs text-slate-500">Full address. Can include commas if quoted.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border border-indigo-200 bg-indigo-50 p-3">
                            <span class="mt-0.5 rounded bg-indigo-100 px-1.5 py-0.5 text-xs font-semibold text-indigo-700">optional</span>
                            <div>
                                <code class="text-xs font-bold text-indigo-800">employee_email</code>
                                <p class="mt-0.5 text-xs text-indigo-700">
                                    Assign this party to a specific employee.
                                    Must match the employee's login email exactly.
                                    Leave blank to use only the global selection above.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {{-- Example Preview --}}
            <section class="panel">
                <div class="panel-header">
                    <h3 class="font-semibold text-slate-950">👀 Example CSV</h3>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-200 px-2 py-1 text-left font-bold text-slate-700">name</th>
                                <th class="border border-slate-200 px-2 py-1 text-left font-bold text-slate-700">mobile</th>
                                <th class="border border-slate-200 px-2 py-1 text-left font-bold text-slate-700">email</th>
                                <th class="border border-slate-200 px-2 py-1 text-left font-bold text-slate-700">address</th>
                                <th class="border border-slate-200 px-2 py-1 text-left font-bold text-indigo-700">employee_email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-slate-200 px-2 py-1 text-slate-700">ABC Traders</td>
                                <td class="border border-slate-200 px-2 py-1 text-slate-500">9876543210</td>
                                <td class="border border-slate-200 px-2 py-1 text-slate-500">abc@ex.com</td>
                                <td class="border border-slate-200 px-2 py-1 text-slate-500">Mumbai</td>
                                <td class="border border-slate-200 px-2 py-1 text-indigo-600">emp@co.com</td>
                            </tr>
                            <tr class="bg-slate-50">
                                <td class="border border-slate-200 px-2 py-1 text-slate-700">XYZ Corp</td>
                                <td class="border border-slate-200 px-2 py-1 text-slate-500">9123456780</td>
                                <td class="border border-slate-200 px-2 py-1 text-slate-400 italic">—</td>
                                <td class="border border-slate-200 px-2 py-1 text-slate-500">Pune</td>
                                <td class="border border-slate-200 px-2 py-1 text-slate-400 italic">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('csv_file');
    const fileInfo  = document.getElementById('fileInfo');
    const fileName  = document.getElementById('fileName');
    const clearBtn  = document.getElementById('clearFile');
    const submitBtn = document.getElementById('submitBtn');
    const submitLbl = document.getElementById('submitLabel');

    // Click drop zone → open file picker
    dropZone.addEventListener('click', function (e) {
        if (e.target === clearBtn || clearBtn.contains(e.target)) return;
        fileInput.click();
    });

    // Drag-over styling
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
    });
    dropZone.addEventListener('dragleave', function () {
        dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
    });

    // Drop file
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
        const dt = e.dataTransfer;
        if (dt.files && dt.files.length > 0) {
            fileInput.files = dt.files;
            showFile(dt.files[0].name);
        }
    });

    // File picker change
    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            showFile(this.files[0].name);
        }
    });

    // Clear file
    clearBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        dropZone.classList.remove('border-indigo-500');
    });

    function showFile(name) {
        fileName.textContent = name;
        fileInfo.classList.remove('hidden');
        dropZone.classList.add('border-indigo-500');
    }

    // Show spinner on submit
    document.getElementById('importForm').addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitLbl.textContent = '⏳ Importing… please wait';
        submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
    });
});
</script>
@endpush
