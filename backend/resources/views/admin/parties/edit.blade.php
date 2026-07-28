@extends('admin.layouts.app')

@section('title', 'Edit Party')
@section('breadcrumb', 'Admin / Parties / Edit')
@section('page_title', 'Edit Party')

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
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Edit Party</h2>
                    <p class="mt-1 text-sm text-slate-500">Update party details and employee assignments.</p>
                </div>
                <span class="text-xs text-slate-400">ID #{{ $party->id }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.parties.update', $party->id) }}" class="p-6 space-y-8">
            @csrf
            @method('PUT')

            {{-- Party Details --}}
            <div>
                <h3 class="mb-4 text-lg font-bold text-slate-950">Party Details</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="form-label">Party Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $party->name) }}"
                               class="form-input @error('name') border-red-500 ring-2 ring-red-200 @enderror"
                               placeholder="e.g., ABC Traders" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Mobile</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $party->mobile) }}"
                               class="form-input @error('mobile') border-red-500 @enderror"
                               placeholder="+91 9876543210">
                        @error('mobile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $party->email) }}"
                               class="form-input @error('email') border-red-500 @enderror"
                               placeholder="party@example.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3"
                                  class="form-input @error('address') border-red-500 @enderror"
                                  placeholder="Street, City, State…">{{ old('address', $party->address) }}</textarea>
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
                    <input type="text" id="emp-search-edit" class="form-input max-w-md"
                           placeholder="Search employees…">
                </div>

                <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 p-3">
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2" id="emp-list-edit">
                        @forelse ($employees as $emp)
                            <label class="emp-item flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 cursor-pointer"
                                   data-name="{{ strtolower($emp->name) }}">
                                <input
                                    type="checkbox"
                                    name="employee_ids[]"
                                    value="{{ $emp->id }}"
                                    @checked(in_array($emp->id, old('employee_ids', $assignedEmployeeIds)))
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-300"
                                >
                                <span>{{ $emp->name }}</span>
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
                <button type="submit" class="btn-primary">Update Party</button>
                <a href="{{ route('admin.parties.index') }}" class="btn-secondary">Cancel</a>

                <form method="POST" action="{{ route('admin.parties.destroy', $party->id) }}"
                      onsubmit="return confirm('Delete party \'{{ addslashes($party->name) }}\'? This cannot be undone.')"
                      class="ml-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-md bg-red-50 px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-100">
                        Delete Party
                    </button>
                </form>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('emp-search-edit');
    if (!search) return;
    search.addEventListener('input', function () {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#emp-list-edit .emp-item').forEach(function (item) {
            const name = item.getAttribute('data-name') || '';
            item.classList.toggle('hidden', term !== '' && !name.includes(term));
        });
    });
});
</script>
@endpush
