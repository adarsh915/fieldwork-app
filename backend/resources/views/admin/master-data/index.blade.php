@extends('admin.layouts.app')

@section('title', $config['label'])
@section('breadcrumb', 'ADMIN / MASTER DATA')
@section('page_title', 'Master Data Management')

@section('content')
<div x-data="masterDataManager({
    initialType: '{{ $type }}',
    initialConfig: {{ Js::from($config) }},
    initialRecords: {{ Js::from($records) }},
    typesConfig: {{ Js::from($types) }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Tab navigation with zero-reload AJAX switching --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-border pb-4">
        <div class="flex flex-wrap gap-2">
            <template x-for="(tConfig, tKey) in typesConfig" :key="tKey">
                <button type="button"
                        @click="switchType(tKey)"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition-all duration-200 inline-flex items-center gap-2"
                        :class="currentType === tKey
                            ? 'bg-brand-600 text-white shadow-md shadow-brand-500/20'
                            : 'bg-white border border-surface-border text-ink-600 hover:bg-surface-muted hover:text-ink-900'">
                    <span x-text="tConfig.label"></span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold"
                          :class="currentType === tKey ? 'bg-white/20 text-white' : 'bg-surface-muted text-ink-500'"
                          x-text="currentType === tKey ? records.length : ''"></span>
                </button>
            </template>
        </div>

        {{-- Live Search Filter --}}
        <div class="relative w-full sm:w-64">
            <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
            <input type="text"
                   x-model="search"
                   placeholder="Search records..."
                   class="form-input text-xs pl-9 w-full">
        </div>
    </div>

    {{-- Main Layout Grid --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Add Record Form (AJAX) --}}
        <div class="panel p-6 h-fit border border-surface-border shadow-sm rounded-2xl">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                <h3 class="text-sm font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-plus-circle class="w-4 h-4 text-brand-600" />
                    <span>Add <span x-text="singularLabel()"></span></span>
                </h3>
            </div>

            <form @submit.prevent="submitAdd()" class="space-y-4">
                {{-- Name --}}
                <div>
                    <label class="form-label text-xs">Name <span class="text-rose-500">*</span></label>
                    <input type="text"
                           x-model="addForm.name"
                           required
                           :placeholder="'Enter ' + singularLabel().toLowerCase() + ' name'"
                           class="form-input text-xs w-full"
                           :class="addErrors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                    <template x-if="addErrors.name">
                        <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="addErrors.name[0]"></p>
                    </template>
                </div>

                {{-- Dynamic Boolean Checkbox --}}
                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-surface-border bg-slate-50/50 hover:bg-slate-50 cursor-pointer transition">
                    <input type="checkbox"
                           x-model="addForm.boolean_val"
                           class="h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500">
                    <span class="text-xs font-semibold text-ink-800" x-text="config.boolean_label"></span>
                </label>

                {{-- Rate per KM (for vehicle types) --}}
                <div x-show="config.has_rate" x-transition>
                    <label class="form-label text-xs">Rate per KM (₹) <span class="text-rose-500">*</span></label>
                    <input type="number"
                           step="0.01"
                           x-model="addForm.rate_per_km"
                           placeholder="0.00"
                           class="form-input text-xs w-full">
                </div>

                {{-- Active Status --}}
                <label class="flex items-center justify-between p-3 rounded-xl border border-surface-border bg-slate-50/50 cursor-pointer">
                    <span class="text-xs font-semibold text-ink-800">Active Status</span>
                    <input type="checkbox"
                           x-model="addForm.is_active"
                           class="h-4 w-4 rounded border-surface-border text-emerald-600 focus:ring-emerald-500">
                </label>

                <button type="submit"
                        :disabled="adding"
                        class="btn-primary w-full justify-center text-xs py-2.5"
                        style="background-color: #4f46e5; color: #ffffff;">
                    <svg x-show="adding" class="animate-spin h-3.5 w-3.5 text-white mr-1.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="adding ? 'Saving...' : 'Add Record'"></span>
                </button>
            </form>
        </div>

        {{-- Records Table (AJAX) --}}
        <div class="xl:col-span-2 panel overflow-hidden border border-surface-border shadow-sm rounded-2xl">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th x-text="config.boolean_label"></th>
                            <th>Status</th>
                            <th x-show="config.has_rate">Rate (₹/km)</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border">
                        {{-- Loading Skeleton --}}
                        <template x-if="loading">
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <svg class="animate-spin h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-xs text-ink-500">Loading records...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        {{-- Records List --}}
                        <template x-for="record in filteredRecords()" :key="record.id">
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                <td class="text-body-emphasis font-bold text-ink-900" x-text="record.name"></td>
                                <td>
                                    <span x-show="record[config.boolean_field]" class="badge badge-success inline-flex items-center gap-1">
                                        <x-lucide-check-circle class="w-3 h-3" /> Yes
                                    </span>
                                    <span x-show="!record[config.boolean_field]" class="badge badge-neutral">
                                        No
                                    </span>
                                </td>
                                <td>
                                    <span x-show="record.is_active" class="badge badge-success inline-flex items-center gap-1">
                                        <x-lucide-check-circle class="w-3 h-3" /> Active
                                    </span>
                                    <span x-show="!record.is_active" class="badge badge-danger inline-flex items-center gap-1">
                                        <x-lucide-x-circle class="w-3 h-3" /> Inactive
                                    </span>
                                </td>
                                <td x-show="config.has_rate" class="font-bold text-indigo-600" x-text="'₹' + Number(record.rate_per_km || 0).toFixed(2)"></td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                                @click="openEditModal(record)"
                                                class="p-1.5 rounded-lg border border-surface-border bg-white text-ink-600 hover:bg-surface-muted hover:text-ink-900 transition-colors shadow-xs"
                                                title="Edit">
                                            <x-lucide-pencil class="w-3.5 h-3.5" />
                                        </button>
                                        <button type="button"
                                                @click="deleteRecord(record)"
                                                class="p-1.5 rounded-lg border border-red-200 bg-white text-rose-600 hover:bg-rose-50 transition-colors shadow-xs"
                                                title="Delete">
                                            <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        {{-- Empty State --}}
                        <tr x-show="!loading && filteredRecords().length === 0">
                            <td colspan="5" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2 text-ink-400">
                                    <x-lucide-inbox class="w-10 h-10 stroke-1" />
                                    <p class="text-xs font-semibold text-ink-500">No records found matching your query.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: EDIT RECORD (AJAX)                                                 --}}
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
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 my-8 flex flex-col border border-surface-border">
                
                {{-- Header --}}
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <div>
                        <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                            <x-lucide-pencil class="w-4 h-4 text-indigo-600" />
                            <span>Edit <span x-text="singularLabel()"></span></span>
                        </h3>
                    </div>
                    <button @click="editModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Edit Form --}}
                <form @submit.prevent="submitEdit()" class="space-y-4">
                    <div>
                        <label class="form-label text-xs">Name <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="editModal.form.name"
                               required
                               class="form-input text-xs w-full"
                               :class="editModal.errors.name ? 'border-rose-500 ring-1 ring-rose-200' : ''">
                        <template x-if="editModal.errors.name">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="editModal.errors.name[0]"></p>
                        </template>
                    </div>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border border-surface-border bg-slate-50 cursor-pointer">
                        <input type="checkbox"
                               x-model="editModal.form.boolean_val"
                               class="h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500">
                        <span class="text-xs font-semibold text-ink-800" x-text="config.boolean_label"></span>
                    </label>

                    <div x-show="config.has_rate">
                        <label class="form-label text-xs">Rate per KM (₹) <span class="text-rose-500">*</span></label>
                        <input type="number"
                               step="0.01"
                               x-model="editModal.form.rate_per_km"
                               class="form-input text-xs w-full">
                    </div>

                    <label class="flex items-center justify-between p-3 rounded-xl border border-surface-border bg-slate-50 cursor-pointer">
                        <span class="text-xs font-semibold text-ink-800">Active Status</span>
                        <input type="checkbox"
                               x-model="editModal.form.is_active"
                               class="h-4 w-4 rounded border-surface-border text-emerald-600 focus:ring-emerald-500">
                    </label>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-border">
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
                            <span x-text="editModal.saving ? 'Saving...' : 'Update Record'"></span>
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
function masterDataManager(params) {
    return {
        currentType: params.initialType,
        config: params.initialConfig,
        records: params.initialRecords || [],
        typesConfig: params.typesConfig || {},
        csrfToken: params.csrfToken,

        search: '',
        loading: false,
        adding: false,

        addForm: {
            name: '',
            boolean_val: false,
            is_active: true,
            rate_per_km: '0',
        },
        addErrors: {},

        editModal: {
            open: false,
            id: null,
            form: {
                name: '',
                boolean_val: false,
                is_active: true,
                rate_per_km: '0',
            },
            errors: {},
            saving: false,
        },

        singularLabel() {
            const label = this.config.label || '';
            return label.endsWith('s') ? label.slice(0, -1) : label;
        },

        filteredRecords() {
            const q = this.search.toLowerCase().trim();
            if (!q) return this.records;
            return this.records.filter(r => (r.name && r.name.toLowerCase().includes(q)));
        },

        async switchType(typeKey) {
            if (this.currentType === typeKey) return;
            this.currentType = typeKey;
            this.config = this.typesConfig[typeKey];
            this.loading = true;
            this.search = '';
            this.addForm.name = '';
            this.addForm.boolean_val = false;
            this.addForm.is_active = true;
            this.addForm.rate_per_km = '0';
            this.addErrors = {};

            // Update browser URL without reload
            window.history.pushState({}, '', `/admin/master-data/${typeKey}`);

            try {
                const res = await fetch(`/admin/master-data/${typeKey}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.records = data.records || [];
                    this.config = data.config;
                } else {
                    window.toast('Failed to load master data records.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error while switching tabs.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async submitAdd() {
            this.adding = true;
            this.addErrors = {};

            const payload = {
                name: this.addForm.name,
                is_active: this.addForm.is_active,
                [this.config.boolean_field]: this.addForm.boolean_val
            };
            if (this.config.has_rate) {
                payload.rate_per_km = this.addForm.rate_per_km;
            }

            try {
                const res = await fetch(`/admin/master-data/${this.currentType}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.records.unshift(data.record);
                    this.addForm.name = '';
                    this.addForm.boolean_val = false;
                    this.addForm.is_active = true;
                    this.addForm.rate_per_km = '0';
                    window.toast(data.message || 'Record created successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.addErrors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to add record.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error adding record.', 'error');
            } finally {
                this.adding = false;
            }
        },

        openEditModal(record) {
            this.editModal.id = record.id;
            this.editModal.errors = {};
            this.editModal.form = {
                name: record.name,
                boolean_val: Boolean(record[this.config.boolean_field]),
                is_active: Boolean(record.is_active),
                rate_per_km: record.rate_per_km || '0',
            };
            this.editModal.open = true;
        },

        async submitEdit() {
            this.editModal.saving = true;
            this.editModal.errors = {};

            const payload = {
                name: this.editModal.form.name,
                is_active: this.editModal.form.is_active,
                [this.config.boolean_field]: this.editModal.form.boolean_val
            };
            if (this.config.has_rate) {
                payload.rate_per_km = this.editModal.form.rate_per_km;
            }

            try {
                const res = await fetch(`/admin/master-data/${this.currentType}/${this.editModal.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const idx = this.records.findIndex(r => r.id === this.editModal.id);
                    if (idx > -1) {
                        this.records[idx] = data.record;
                    }
                    this.editModal.open = false;
                    window.toast(data.message || 'Record updated successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.editModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to update record.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error saving record.', 'error');
            } finally {
                this.editModal.saving = false;
            }
        },

        async deleteRecord(record) {
            if (!confirm(`Are you sure you want to delete '${record.name}'?`)) return;

            try {
                const res = await fetch(`/admin/master-data/${this.currentType}/${record.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.records = this.records.filter(r => r.id !== record.id);
                    window.toast(data.message || 'Record deleted successfully.', 'success');
                } else {
                    window.toast(data.message || 'Failed to delete record.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error deleting record.', 'error');
            }
        }
    };
}
</script>
@endpush
