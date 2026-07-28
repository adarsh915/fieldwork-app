{{-- Assign parties (customers) to an employee for Day Start party dropdown --}}
<div class="mb-8">
    <h3 class="mb-2 text-lg font-bold text-slate-950">Assigned Parties</h3>
    <p class="mb-4 text-sm text-slate-500">
        Select which parties this employee can choose when submitting a Day Start tour plan.
        Only assigned parties appear in their party dropdown.
    </p>

    <div class="mb-3">
        <input type="text" id="party-assignment-search" class="form-input max-w-md" placeholder="Search parties...">
    </div>

    <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 p-3">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2" id="party-assignment-list">
            @forelse ($customers as $customer)
                <label class="party-assignment-item flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                       data-party-name="{{ strtolower($customer->name) }}">
                    <input
                        type="checkbox"
                        name="parties[]"
                        value="{{ $customer->id }}"
                        @checked(in_array($customer->id, old('parties', $assignedPartyIds ?? [])))
                        class="rounded border-slate-300 text-slate-950 focus:ring-slate-300"
                    >
                    <span>{{ $customer->name }}</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No parties found. Add customers first under Management → Customers.</p>
            @endforelse
        </div>
    </div>

    @error('parties')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('parties.*')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const search = document.getElementById('party-assignment-search');
            if (!search) return;

            search.addEventListener('input', function () {
                const term = this.value.toLowerCase();
                document.querySelectorAll('.party-assignment-item').forEach(function (item) {
                    const name = item.getAttribute('data-party-name') || '';
                    item.classList.toggle('hidden', term !== '' && !name.includes(term));
                });
            });
        });
    </script>
    @endpush
@endonce
