@extends('admin.layouts.app')

@section('title', 'Holidays Management')
@section('breadcrumb', 'ADMIN / HOLIDAYS')
@section('page_title', 'Declared Holidays')

@section('content')
<div x-data="holidayManager({
    initialYear: {{ (int)$year }},
    initialHolidays: {{ Js::from($holidays->map(function ($h) {
        return [
            'id' => $h->id,
            'date' => $h->date->format('Y-m-d'),
            'date_formatted' => $h->date->format('d M Y'),
            'day_name' => $h->date->format('l'),
            'name' => $h->name,
            'type' => $h->type,
            'type_label' => $h->type === 'national' ? 'National Holiday' : 'Company Holiday',
            'created_by_name' => $h->createdBy->name ?? 'Admin',
        ];
    })) }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Header Action Bar --}}
    <div class="panel p-6 border border-surface-border shadow-sm rounded-2xl">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            
            {{-- Year Selector & Live Search --}}
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center gap-2">
                    <label class="form-label text-xs font-bold mb-0 whitespace-nowrap text-ink-700">Year:</label>
                    <select x-model="year" @change="switchYear($event.target.value)" class="form-input text-xs w-auto min-w-[120px]">
                        @for($y = now()->year - 2; $y <= now()->year + 3; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Live Search --}}
                <div class="relative w-full sm:w-60">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text"
                           x-model="search"
                           placeholder="Filter holidays…"
                           class="form-input text-xs pl-9 w-full">
                </div>
            </div>

            {{-- Declare Holiday Button --}}
            <button type="button"
                    @click="openDeclareModal()"
                    class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-2"
                    style="background-color: #4f46e5; color: #ffffff;">
                <x-lucide-plus class="w-4 h-4" />
                Declare Holiday
            </button>
        </div>
    </div>

    {{-- Data Table Panel --}}
    <div class="panel overflow-hidden border border-surface-border shadow-sm rounded-2xl">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Holiday Name</th>
                        <th>Type</th>
                        <th>Declared By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border">
                    {{-- Loading Indicator --}}
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="animate-spin h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs text-ink-500">Loading holidays for <span x-text="year"></span>...</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Records --}}
                    <template x-for="holiday in filteredHolidays()" :key="holiday.id">
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="font-bold text-ink-900 whitespace-nowrap text-xs" x-text="holiday.date_formatted"></td>
                            <td class="text-ink-600 whitespace-nowrap text-xs" x-text="holiday.day_name"></td>
                            <td class="font-bold text-ink-900 text-xs" x-text="holiday.name"></td>
                            <td>
                                <span x-show="holiday.type === 'national'" class="badge badge-info text-xs inline-flex items-center gap-1">
                                    <x-lucide-flag class="w-3 h-3" /> National Holiday
                                </span>
                                <span x-show="holiday.type !== 'national'" class="badge badge-success text-xs inline-flex items-center gap-1">
                                    <x-lucide-building class="w-3 h-3" /> Company Holiday
                                </span>
                            </td>
                            <td class="text-xs text-ink-600" x-text="holiday.created_by_name"></td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEditModal(holiday)"
                                            class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-600 hover:bg-surface-muted hover:text-ink-900 transition-colors shadow-xs"
                                            title="Edit Holiday">
                                        <x-lucide-pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button"
                                            @click="deleteHoliday(holiday)"
                                            class="p-1.5 rounded-lg border border-red-200 bg-white text-rose-600 hover:bg-rose-50 transition-colors shadow-xs"
                                            title="Delete Holiday">
                                        <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty State --}}
                    <tr x-show="!loading && filteredHolidays().length === 0">
                        <td colspan="6" class="text-center py-16 text-ink-400">
                            <div class="flex flex-col items-center gap-2">
                                <x-lucide-calendar-off class="w-10 h-10 stroke-1 text-ink-300" />
                                <p class="text-xs font-semibold text-ink-600">No holidays declared for <span x-text="year"></span>.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: DECLARE NEW HOLIDAY (AJAX)                                         --}}
    {{-- ========================================================================= --}}
    <div x-show="declareModal.open" x-cloak style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="declareModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div x-show="declareModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="declareModal.open = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

            {{-- Dialog --}}
            <div x-show="declareModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                        <x-lucide-calendar-plus class="w-5 h-5 text-indigo-600" />
                        Declare New Holiday
                    </h3>
                    <button type="button" @click="declareModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitDeclare()" class="space-y-4">
                    {{-- Date --}}
                    <div>
                        <label class="form-label text-xs">Holiday Date <span class="text-rose-500">*</span></label>
                        <input type="date"
                               x-model="declareModal.form.date"
                               required
                               class="form-input text-xs w-full"
                               :class="declareModal.errors.date ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="declareModal.errors.date">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="declareModal.errors.date[0]"></p>
                        </template>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="form-label text-xs">Holiday Name <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="declareModal.form.name"
                               required
                               placeholder="e.g. Diwali, Independence Day"
                               class="form-input text-xs w-full"
                               :class="declareModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="declareModal.errors.name">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="declareModal.errors.name[0]"></p>
                        </template>
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="form-label text-xs">Holiday Type <span class="text-rose-500">*</span></label>
                        <select x-model="declareModal.form.type" required class="form-input text-xs w-full">
                            <option value="company">Company Holiday</option>
                            <option value="national">National Holiday</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-surface-border">
                        <button type="button" @click="declareModal.open = false" :disabled="declareModal.saving" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="submit" :disabled="declareModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="declareModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="declareModal.saving ? 'Saving...' : 'Save Holiday'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: EDIT HOLIDAY (AJAX)                                                --}}
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
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                        <x-lucide-pencil class="w-4 h-4 text-indigo-600" />
                        Edit Holiday
                    </h3>
                    <button type="button" @click="editModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit()" class="space-y-4">
                    {{-- Date --}}
                    <div>
                        <label class="form-label text-xs">Holiday Date <span class="text-rose-500">*</span></label>
                        <input type="date"
                               x-model="editModal.form.date"
                               required
                               class="form-input text-xs w-full"
                               :class="editModal.errors.date ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="editModal.errors.date">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.date[0]"></p>
                        </template>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="form-label text-xs">Holiday Name <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="editModal.form.name"
                               required
                               class="form-input text-xs w-full"
                               :class="editModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="editModal.errors.name">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.name[0]"></p>
                        </template>
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="form-label text-xs">Holiday Type <span class="text-rose-500">*</span></label>
                        <select x-model="editModal.form.type" required class="form-input text-xs w-full">
                            <option value="company">Company Holiday</option>
                            <option value="national">National Holiday</option>
                        </select>
                    </div>

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
                            <span x-text="editModal.saving ? 'Saving...' : 'Update Holiday'"></span>
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
function holidayManager(config) {
    return {
        year: config.initialYear,
        holidays: config.initialHolidays || [],
        csrfToken: config.csrfToken,

        search: '',
        loading: false,

        declareModal: {
            open: false,
            saving: false,
            form: {
                date: '',
                name: '',
                type: 'company',
            },
            errors: {},
        },

        editModal: {
            open: false,
            id: null,
            saving: false,
            form: {
                date: '',
                name: '',
                type: 'company',
            },
            errors: {},
        },

        filteredHolidays() {
            const q = this.search.toLowerCase().trim();
            if (!q) return this.holidays;
            return this.holidays.filter(h =>
                (h.name && h.name.toLowerCase().includes(q)) ||
                (h.day_name && h.day_name.toLowerCase().includes(q)) ||
                (h.date_formatted && h.date_formatted.toLowerCase().includes(q)) ||
                (h.type_label && h.type_label.toLowerCase().includes(q))
            );
        },

        async switchYear(newYear) {
            this.year = parseInt(newYear);
            this.loading = true;

            try {
                const res = await fetch(`/admin/holidays?year=${this.year}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.holidays = data.holidays || [];
                } else {
                    window.toast('Failed to load holidays.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error loading holidays.', 'error');
            } finally {
                this.loading = false;
            }
        },

        openDeclareModal() {
            const defaultMonth = String(new Date().getMonth() + 1).padStart(2, '0');
            const defaultDay = String(new Date().getDate()).padStart(2, '0');
            this.declareModal.form = {
                date: `${this.year}-${defaultMonth}-${defaultDay}`,
                name: '',
                type: 'company',
            };
            this.declareModal.errors = {};
            this.declareModal.open = true;
        },

        async submitDeclare() {
            this.declareModal.saving = true;
            this.declareModal.errors = {};

            try {
                const res = await fetch(`{{ route('admin.holidays.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.declareModal.form)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.holidays.push(data.holiday);
                    this.holidays.sort((a, b) => new Date(a.date) - new Date(b.date));
                    this.declareModal.open = false;
                    window.toast(data.message || 'Holiday declared successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.declareModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to declare holiday.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error saving holiday.', 'error');
            } finally {
                this.declareModal.saving = false;
            }
        },

        openEditModal(holiday) {
            this.editModal.id = holiday.id;
            this.editModal.form = {
                date: holiday.date,
                name: holiday.name,
                type: holiday.type,
            };
            this.editModal.errors = {};
            this.editModal.open = true;
        },

        async submitEdit() {
            this.editModal.saving = true;
            this.editModal.errors = {};

            try {
                const res = await fetch(`/admin/holidays/${this.editModal.id}`, {
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
                    const idx = this.holidays.findIndex(h => h.id === this.editModal.id);
                    if (idx > -1) {
                        this.holidays[idx] = data.holiday;
                        this.holidays.sort((a, b) => new Date(a.date) - new Date(b.date));
                    }
                    this.editModal.open = false;
                    window.toast(data.message || 'Holiday updated successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.editModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to update holiday.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error updating holiday.', 'error');
            } finally {
                this.editModal.saving = false;
            }
        },

        async deleteHoliday(holiday) {
            if (!confirm(`Are you sure you want to delete holiday '${holiday.name}' on ${holiday.date_formatted}?`)) {
                return;
            }

            try {
                const res = await fetch(`/admin/holidays/${holiday.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.holidays = this.holidays.filter(h => h.id !== holiday.id);
                    window.toast(data.message || 'Holiday deleted successfully.', 'success');
                } else {
                    window.toast(data.message || 'Failed to delete holiday.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error deleting holiday.', 'error');
            }
        }
    };
}
</script>
@endpush
