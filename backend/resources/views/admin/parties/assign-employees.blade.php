@extends('admin.layouts.app')

@section('title', 'Assign Employees – ' . $party->name)
@section('breadcrumb', 'Admin / Parties / Assign Employees')
@section('page_title', 'Assign Employees to Party')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.parties.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            ← Back to Parties
        </a>
    </div>

    <section class="panel max-w-3xl">
        <div class="panel-header">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">{{ $party->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Choose which employees can see and use this party in their Day Start tour plans.
                    </p>
                </div>
                <div class="text-right text-xs text-slate-400">
                    <div>ID #{{ $party->id }}</div>
                    @if($party->mobile)
                        <div>📞 {{ $party->mobile }}</div>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.parties.save-employees', $party->id) }}" class="p-6">
            @csrf

            {{-- Stats Bar --}}
            <div class="mb-6 flex flex-wrap gap-4">
                <div class="rounded-lg bg-violet-50 border border-violet-200 px-4 py-3 text-center min-w-[120px]">
                    <div class="text-2xl font-bold text-violet-700" id="assigned-count">{{ count($assignedEmployeeIds) }}</div>
                    <div class="text-xs text-violet-600 mt-0.5">Currently Assigned</div>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-center min-w-[120px]">
                    <div class="text-2xl font-bold text-slate-700">{{ $employees->count() }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Total Employees</div>
                </div>
            </div>

            {{-- Select All / None --}}
            <div class="mb-3 flex items-center gap-3">
                <input type="text" id="emp-assign-search" class="form-input max-w-sm"
                       placeholder="Search employees…">
                <button type="button" id="btn-select-all"
                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline">
                    Select All
                </button>
                <button type="button" id="btn-clear-all"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-800 underline">
                    Clear All
                </button>
            </div>

            {{-- Employee List --}}
            <div class="max-h-80 overflow-y-auto rounded-lg border border-slate-200 p-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2" id="emp-assign-list">
                    @forelse ($employees as $emp)
                        <label class="emp-assign-item flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-violet-300 hover:bg-violet-50 cursor-pointer"
                               data-name="{{ strtolower($emp->name) }}">
                            <input
                                type="checkbox"
                                name="employee_ids[]"
                                value="{{ $emp->id }}"
                                @checked(in_array($emp->id, old('employee_ids', $assignedEmployeeIds)))
                                class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-400"
                            >
                            <div>
                                <div>{{ $emp->name }}</div>
                                @if($emp->employee_id)
                                    <div class="text-xs text-slate-400">{{ $emp->employee_id }}</div>
                                @elseif($emp->email)
                                    <div class="text-xs text-slate-400">{{ $emp->email }}</div>
                                @endif
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-slate-500 col-span-2 py-4 text-center">No employees found.</p>
                    @endforelse
                </div>
            </div>

            @error('employee_ids')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-slate-200 mt-6 pt-6">
                <button type="submit" class="btn-primary">Save Assignment</button>
                <a href="{{ route('admin.parties.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Live search
    const searchInput = document.getElementById('emp-assign-search');
    const items       = document.querySelectorAll('.emp-assign-item');

    searchInput?.addEventListener('input', function () {
        const term = this.value.toLowerCase();
        items.forEach(function (item) {
            const name = item.getAttribute('data-name') || '';
            item.classList.toggle('hidden', term !== '' && !name.includes(term));
        });
    });

    // Select All / Clear All
    document.getElementById('btn-select-all')?.addEventListener('click', function () {
        items.forEach(item => {
            if (!item.classList.contains('hidden')) {
                item.querySelector('input[type=checkbox]').checked = true;
            }
        });
        updateCount();
    });

    document.getElementById('btn-clear-all')?.addEventListener('click', function () {
        items.forEach(item => {
            item.querySelector('input[type=checkbox]').checked = false;
        });
        updateCount();
    });

    // Live assigned count update
    function updateCount() {
        const count = document.querySelectorAll('.emp-assign-item input[type=checkbox]:checked').length;
        const el = document.getElementById('assigned-count');
        if (el) el.textContent = count;
    }

    items.forEach(item => {
        item.querySelector('input[type=checkbox]')?.addEventListener('change', updateCount);
    });
});
</script>
@endpush
