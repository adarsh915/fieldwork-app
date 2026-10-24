@extends('admin.layouts.app')

@section('title', 'Add Party')
@section('breadcrumb', 'Admin / Parties / Create')
@section('page_title', 'Add New Party')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.parties.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            ← Back to Parties
        </a>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3">
            <div class="flex items-start gap-3">
                <span class="text-red-600">⚠️</span>
                <div>
                    <p class="font-semibold text-red-800">Please fix the following errors:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <section class="panel max-w-3xl">
        <div class="panel-header">
            <h2 class="font-semibold text-slate-950">Party Information</h2>
            <p class="mt-1 text-sm text-slate-500">Fill in the details to create a new party and optionally assign it to employees.</p>
        </div>

        <form method="POST" action="{{ route('admin.parties.store') }}" id="createPartyForm" class="p-6 space-y-8">
            @csrf

            {{-- Party Details --}}
            <div>
                <h3 class="mb-4 text-lg font-bold text-slate-950">Party Details</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="form-label">Party Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-input @error('name') border-red-500 ring-2 ring-red-200 @enderror"
                               placeholder="e.g., ABC Traders" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Mobile</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}"
                               class="form-input @error('mobile') border-red-500 @enderror"
                               placeholder="+91 9876543210">
                        @error('mobile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-input @error('email') border-red-500 @enderror"
                               placeholder="party@example.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Status</label>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   @checked(old('is_active', true))
                                   class="rounded border-slate-300 text-slate-950 focus:ring-slate-300">
                            <label for="is_active" class="text-sm font-medium text-slate-700 cursor-pointer">Active</label>
                        </div>
                        @error('is_active')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{
                        districts: {{ Js::from($districts ?? []) }},
                        selectedDistrictId: '{{ old('district_id') }}',
                        selectedAreaId: '{{ old('area_id') }}',
                        get areas() {
                            const d = this.districts.find(item => String(item.id) === String(this.selectedDistrictId));
                            return d ? (d.areas || []) : [];
                        }
                    }" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="form-label">District / City</label>
                            <select name="district_id" x-model="selectedDistrictId" class="form-input">
                                <option value="">Select District</option>
                                <template x-for="d in districts" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Area / Town / Beat</label>
                            <select name="area_id" x-model="selectedAreaId" class="form-input" :disabled="!selectedDistrictId">
                                <option value="">Select Area</option>
                                <template x-for="a in areas" :key="a.id">
                                    <option :value="a.id" x-text="a.name + (a.pincode ? ' (' + a.pincode + ')' : '')"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3"
                                  class="form-input @error('address') border-red-500 @enderror"
                                  placeholder="Street, City, State…">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Assign to Employees --}}
            <div>
                <h3 class="mb-2 text-lg font-bold text-slate-950">Assign to Employees</h3>
                <p class="mb-4 text-sm text-slate-500">
                    Select which employees can see and use this party during Day Start / tour planning.
                </p>

                <div class="mb-3">
                    <input type="text" id="emp-search-create" class="form-input max-w-md"
                           placeholder="Search employees…">
                </div>

                <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 p-3">
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2" id="emp-list-create">
                        @forelse ($employees as $emp)
                            <label class="emp-item flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 cursor-pointer"
                                   data-name="{{ strtolower($emp->name) }}">
                                <input
                                    type="checkbox"
                                    name="employee_ids[]"
                                    value="{{ $emp->id }}"
                                    @checked(in_array($emp->id, old('employee_ids', [])))
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-300"
                                >
                                <span>{{ $emp->name_with_role }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500 col-span-2">No employees found.</p>
                        @endforelse
                    </div>
                </div>

                @error('employee_ids')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                <button type="submit" class="btn-primary">Create Party</button>
                <a href="{{ route('admin.parties.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('emp-search-create');
    if (search) {
        search.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#emp-list-create .emp-item').forEach(function (item) {
                const name = item.getAttribute('data-name') || '';
                item.classList.toggle('hidden', term !== '' && !name.includes(term));
            });
        });
    }

    const form = document.getElementById('createPartyForm');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const origText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">↻</span> Saving...';

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
                    if (window.toast) window.toast(data.message || 'Party created successfully.', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '{{ route('admin.parties.index') }}';
                    }, 600);
                } else {
                    let msg = data.message || 'Validation error';
                    if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                    if (window.toast) window.toast(msg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origText;
                }
            } catch (err) {
                console.error(err);
                if (window.toast) window.toast('Network error. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
            }
        });
    }
});
</script>
@endpush
