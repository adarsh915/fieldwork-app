@extends('admin.layouts.app')

@section('title', 'Districts & Areas')
@section('breadcrumb', 'ADMIN / LOCATIONS')
@section('page_title', 'Territory & Tour Planning Master')

@section('content')
<div x-data="locationManager({
    districts: {{ Js::from($districts) }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Top Bar: Summary & Quick Actions --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-surface-border shadow-xs">
        <div>
            <h2 class="text-base font-bold text-ink-900 flex items-center gap-2">
                <x-lucide-map-pin class="w-5 h-5 text-brand-600" />
                <span>Territory & Location Management</span>
            </h2>
            <p class="text-xs text-ink-500 mt-1">
                Configure 2-tier location hierarchy (District → Area/Town/Beat) for mobile tour planning & party registration.
            </p>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button type="button"
                    @click="openAddDistrictModal()"
                    class="btn-primary text-xs flex items-center justify-center gap-1.5 py-2 px-3.5 shadow-xs">
                <x-lucide-plus class="w-4 h-4" />
                <span>Add District</span>
            </button>
        </div>
    </div>

    {{-- 2-Column Responsive Layout: Left = Districts list, Right = Areas list for selected District --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Left Column: Districts (5 cols) --}}
        <div class="lg:col-span-5 space-y-3">
            <div class="bg-white rounded-2xl border border-surface-border p-4 shadow-xs">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-ink-700 flex items-center gap-1.5">
                        <x-lucide-compass class="w-4 h-4 text-brand-600" />
                        <span>Districts (<span x-text="districts.length"></span>)</span>
                    </h3>
                    <div class="relative w-44">
                        <x-lucide-search class="w-3.5 h-3.5 text-ink-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input type="text"
                               x-model="districtSearch"
                               placeholder="Search district..."
                               class="form-input text-xs pl-8 py-1 w-full rounded-lg">
                    </div>
                </div>

                {{-- District List --}}
                <div class="space-y-2 max-h-[600px] overflow-y-auto pr-1">
                    <template x-for="dist in filteredDistricts" :key="dist.id">
                        <div @click="selectDistrict(dist)"
                             class="p-3.5 rounded-xl border transition-all cursor-pointer flex items-center justify-between"
                             :class="selectedDistrict?.id === dist.id 
                                ? 'border-brand-500 bg-brand-50/50 shadow-xs ring-1 ring-brand-500/30' 
                                : 'border-surface-border bg-white hover:border-slate-300 hover:bg-slate-50/70'">
                            <div class="flex-1 min-w-0 pr-2">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-xs font-bold text-ink-900 truncate" x-text="dist.name"></h4>
                                    <template x-if="!dist.is_active">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-600 border border-rose-200">Inactive</span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-ink-500 mt-1">
                                    <span x-text="dist.state || 'Gujarat'"></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="font-medium text-brand-700" x-text="(dist.areas?.length || 0) + ' Areas'"></span>
                                    <span class="text-slate-300">•</span>
                                    <span x-text="(dist.customers_count || 0) + ' Parties'"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button"
                                        @click.stop="openEditDistrictModal(dist)"
                                        class="p-1.5 text-ink-400 hover:text-brand-600 rounded-lg hover:bg-white transition"
                                        title="Edit District">
                                    <x-lucide-pencil class="w-3.5 h-3.5" />
                                </button>
                                <button type="button"
                                        @click.stop="deleteDistrict(dist)"
                                        class="p-1.5 text-ink-400 hover:text-rose-600 rounded-lg hover:bg-white transition"
                                        title="Delete District">
                                    <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="filteredDistricts.length === 0">
                        <div class="text-center py-8 text-xs text-ink-400">
                            No districts found matching your search.
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Right Column: Areas for Selected District (7 cols) --}}
        <div class="lg:col-span-7 space-y-3">
            <div class="bg-white rounded-2xl border border-surface-border p-5 shadow-xs">
                <template x-if="selectedDistrict">
                    <div>
                        {{-- Header with District info & Add Area Button --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-4 border-b border-surface-border gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-ink-900" x-text="selectedDistrict.name"></h3>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-brand-50 text-brand-700 border border-brand-200"
                                          x-text="(selectedDistrict.areas?.length || 0) + ' registered areas'"></span>
                                </div>
                                <p class="text-xs text-ink-500 mt-0.5">
                                    Areas/Towns displayed to field reps when visiting <span class="font-medium text-ink-700" x-text="selectedDistrict.name"></span>.
                                </p>
                            </div>

                            <button type="button"
                                    @click="openAddAreaModal()"
                                    class="btn-primary text-xs flex items-center justify-center gap-1.5 py-1.5 px-3">
                                <x-lucide-plus class="w-3.5 h-3.5" />
                                <span>Add Area</span>
                            </button>
                        </div>

                        {{-- Area Search Bar --}}
                        <div class="relative mb-3">
                            <x-lucide-search class="w-3.5 h-3.5 text-ink-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input type="text"
                                   x-model="areaSearch"
                                   placeholder="Filter areas by name or pincode..."
                                   class="form-input text-xs pl-9 py-1.5 w-full rounded-xl">
                        </div>

                        {{-- Areas Table / List --}}
                        <div class="border border-surface-border rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-ink-600 font-semibold border-b border-surface-border">
                                    <tr>
                                        <th class="py-2.5 px-3.5">Area / Town Name</th>
                                        <th class="py-2.5 px-3.5">Pincode</th>
                                        <th class="py-2.5 px-3.5 text-center">Status</th>
                                        <th class="py-2.5 px-3.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-border">
                                    <template x-for="area in filteredAreas" :key="area.id">
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="py-2.5 px-3.5 font-medium text-ink-900" x-text="area.name"></td>
                                            <td class="py-2.5 px-3.5 text-ink-500 font-mono" x-text="area.pincode || '—'"></td>
                                            <td class="py-2.5 px-3.5 text-center">
                                                <button type="button"
                                                        @click="toggleAreaStatus(area)"
                                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition"
                                                        :class="area.is_active 
                                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' 
                                                            : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                                        x-text="area.is_active ? 'Active' : 'Inactive'">
                                                </button>
                                            </td>
                                            <td class="py-2.5 px-3.5 text-right space-x-1">
                                                <button type="button"
                                                        @click="openEditAreaModal(area)"
                                                        class="p-1 text-ink-400 hover:text-brand-600 rounded transition"
                                                        title="Edit Area">
                                                    <x-lucide-pencil class="w-3.5 h-3.5 inline" />
                                                </button>
                                                <button type="button"
                                                        @click="deleteArea(area)"
                                                        class="p-1 text-ink-400 hover:text-rose-600 rounded transition"
                                                        title="Delete Area">
                                                    <x-lucide-trash-2 class="w-3.5 h-3.5 inline" />
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="filteredAreas.length === 0">
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-ink-400">
                                                No areas found for this district. Click "Add Area" above to create one.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <template x-if="!selectedDistrict">
                    <div class="py-16 text-center">
                        <x-lucide-map class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                        <h4 class="text-xs font-bold text-ink-800">No District Selected</h4>
                        <p class="text-xs text-ink-400 mt-1">Select a district from the left panel to manage its sub-areas.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- District Modal (Add / Edit) --}}
    <div x-show="districtModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         @keydown.escape.window="districtModalOpen = false">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-surface-border"
             @click.away="districtModalOpen = false">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                <h3 class="text-sm font-bold text-ink-900" x-text="districtModalMode === 'edit' ? 'Edit District' : 'Add New District'"></h3>
                <button type="button" @click="districtModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <form @submit.prevent="saveDistrict()" class="space-y-4">
                <div>
                    <label class="form-label text-xs">District Name <span class="text-rose-500">*</span></label>
                    <input type="text"
                           x-model="districtForm.name"
                           required
                           placeholder="e.g. Ahmedabad"
                           class="form-input text-xs w-full">
                </div>

                <div>
                    <label class="form-label text-xs">State</label>
                    <input type="text"
                           x-model="districtForm.state"
                           placeholder="Gujarat"
                           class="form-input text-xs w-full">
                </div>

                <div>
                    <label class="form-label text-xs">Code / Short ID</label>
                    <input type="text"
                           x-model="districtForm.code"
                           placeholder="e.g. AHD"
                           class="form-input text-xs w-full">
                </div>

                <label class="flex items-center justify-between p-3 rounded-xl border border-surface-border bg-slate-50/50 cursor-pointer">
                    <span class="text-xs font-semibold text-ink-800">Active in Mobile App</span>
                    <input type="checkbox"
                           x-model="districtForm.is_active"
                           class="h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500">
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="districtModalOpen = false" class="btn-secondary text-xs py-2 px-3">Cancel</button>
                    <button type="submit" :disabled="districtSaving" class="btn-primary text-xs py-2 px-4">
                        <span x-text="districtSaving ? 'Saving...' : 'Save District'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Area Modal (Add / Edit) --}}
    <div x-show="areaModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         @keydown.escape.window="areaModalOpen = false">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-surface-border"
             @click.away="areaModalOpen = false">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                <h3 class="text-sm font-bold text-ink-900" x-text="areaModalMode === 'edit' ? 'Edit Area / Town' : 'Add New Area / Beat'"></h3>
                <button type="button" @click="areaModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <form @submit.prevent="saveArea()" class="space-y-4">
                <div>
                    <label class="form-label text-xs">Parent District</label>
                    <input type="text"
                           :value="selectedDistrict?.name"
                           disabled
                           class="form-input text-xs w-full bg-slate-100 text-slate-500">
                </div>

                <div>
                    <label class="form-label text-xs">Area / Town Name <span class="text-rose-500">*</span></label>
                    <input type="text"
                           x-model="areaForm.name"
                           required
                           placeholder="e.g. Naroda GIDC / Sanand / Navrangpura"
                           class="form-input text-xs w-full">
                </div>

                <div>
                    <label class="form-label text-xs">Pincode (Optional)</label>
                    <input type="text"
                           x-model="areaForm.pincode"
                           maxlength="6"
                           placeholder="e.g. 382330"
                           class="form-input text-xs w-full">
                </div>

                <label class="flex items-center justify-between p-3 rounded-xl border border-surface-border bg-slate-50/50 cursor-pointer">
                    <span class="text-xs font-semibold text-ink-800">Active</span>
                    <input type="checkbox"
                           x-model="areaForm.is_active"
                           class="h-4 w-4 rounded border-surface-border text-brand-600 focus:ring-brand-500">
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="areaModalOpen = false" class="btn-secondary text-xs py-2 px-3">Cancel</button>
                    <button type="submit" :disabled="areaSaving" class="btn-primary text-xs py-2 px-4">
                        <span x-text="areaSaving ? 'Saving...' : 'Save Area'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function locationManager(config) {
    return {
        districts: config.districts || [],
        csrfToken: config.csrfToken,
        selectedDistrict: null,
        districtSearch: '',
        areaSearch: '',

        // District Modal State
        districtModalOpen: false,
        districtModalMode: 'create',
        districtSaving: false,
        districtForm: { id: null, name: '', state: 'Gujarat', code: '', is_active: true },

        // Area Modal State
        areaModalOpen: false,
        areaModalMode: 'create',
        areaSaving: false,
        areaForm: { id: null, district_id: null, name: '', pincode: '', is_active: true },

        init() {
            if (this.districts.length > 0) {
                this.selectedDistrict = this.districts[0];
            }
        },

        get filteredDistricts() {
            const q = this.districtSearch.trim().toLowerCase();
            if (!q) return this.districts;
            return this.districts.filter(d => 
                d.name.toLowerCase().includes(q) || 
                (d.code && d.code.toLowerCase().includes(q))
            );
        },

        get filteredAreas() {
            if (!this.selectedDistrict || !this.selectedDistrict.areas) return [];
            const q = this.areaSearch.trim().toLowerCase();
            if (!q) return this.selectedDistrict.areas;
            return this.selectedDistrict.areas.filter(a => 
                a.name.toLowerCase().includes(q) || 
                (a.pincode && a.pincode.includes(q))
            );
        },

        selectDistrict(dist) {
            this.selectedDistrict = dist;
            this.areaSearch = '';
        },

        openAddDistrictModal() {
            this.districtModalMode = 'create';
            this.districtForm = { id: null, name: '', state: 'Gujarat', code: '', is_active: true };
            this.districtModalOpen = true;
        },

        openEditDistrictModal(dist) {
            this.districtModalMode = 'edit';
            this.districtForm = {
                id: dist.id,
                name: dist.name,
                state: dist.state || 'Gujarat',
                code: dist.code || '',
                is_active: Boolean(dist.is_active)
            };
            this.districtModalOpen = true;
        },

        async saveDistrict() {
            this.districtSaving = true;
            try {
                const isEdit = this.districtModalMode === 'edit';
                const url = isEdit 
                    ? `/admin/locations/districts/${this.districtForm.id}`
                    : '/admin/locations/districts';
                const method = isEdit ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.districtForm)
                });

                const data = await res.json();
                if (data.success) {
                    if (isEdit) {
                        const idx = this.districts.findIndex(d => d.id === this.districtForm.id);
                        if (idx !== -1) {
                            data.district.areas = this.districts[idx].areas || [];
                            this.districts[idx] = data.district;
                            if (this.selectedDistrict?.id === this.districtForm.id) {
                                this.selectedDistrict = this.districts[idx];
                            }
                        }
                    } else {
                        data.district.areas = [];
                        this.districts.push(data.district);
                        this.selectedDistrict = data.district;
                    }
                    this.districtModalOpen = false;
                } else {
                    alert(data.message || 'Error saving district');
                }
            } catch (err) {
                alert('Connection error');
            } finally {
                this.districtSaving = false;
            }
        },

        async deleteDistrict(dist) {
            if (!confirm(`Are you sure you want to remove district '${dist.name}'?`)) return;

            try {
                const res = await fetch(`/admin/locations/districts/${dist.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.districts = this.districts.filter(d => d.id !== dist.id);
                    if (this.selectedDistrict?.id === dist.id) {
                        this.selectedDistrict = this.districts[0] || null;
                    }
                } else {
                    alert(data.message || 'Error deleting district');
                }
            } catch (err) {
                alert('Connection error');
            }
        },

        openAddAreaModal() {
            if (!this.selectedDistrict) return;
            this.areaModalMode = 'create';
            this.areaForm = {
                id: null,
                district_id: this.selectedDistrict.id,
                name: '',
                pincode: '',
                is_active: true
            };
            this.areaModalOpen = true;
        },

        openEditAreaModal(area) {
            this.areaModalMode = 'edit';
            this.areaForm = {
                id: area.id,
                district_id: area.district_id,
                name: area.name,
                pincode: area.pincode || '',
                is_active: Boolean(area.is_active)
            };
            this.areaModalOpen = true;
        },

        async saveArea() {
            this.areaSaving = true;
            try {
                const isEdit = this.areaModalMode === 'edit';
                const url = isEdit
                    ? `/admin/locations/areas/${this.areaForm.id}`
                    : '/admin/locations/areas';
                const method = isEdit ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.areaForm)
                });

                const data = await res.json();
                if (data.success) {
                    if (!this.selectedDistrict.areas) this.selectedDistrict.areas = [];
                    if (isEdit) {
                        const idx = this.selectedDistrict.areas.findIndex(a => a.id === this.areaForm.id);
                        if (idx !== -1) this.selectedDistrict.areas[idx] = data.area;
                    } else {
                        this.selectedDistrict.areas.push(data.area);
                    }
                    this.areaModalOpen = false;
                } else {
                    alert(data.message || 'Error saving area');
                }
            } catch (err) {
                alert('Connection error');
            } finally {
                this.areaSaving = false;
            }
        },

        async toggleAreaStatus(area) {
            try {
                const res = await fetch(`/admin/locations/areas/${area.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ is_active: !area.is_active })
                });
                const data = await res.json();
                if (data.success) {
                    area.is_active = !area.is_active;
                }
            } catch (err) {
                alert('Connection error');
            }
        },

        async deleteArea(area) {
            if (!confirm(`Delete area '${area.name}'?`)) return;

            try {
                const res = await fetch(`/admin/locations/areas/${area.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedDistrict.areas = this.selectedDistrict.areas.filter(a => a.id !== area.id);
                } else {
                    alert(data.message || 'Error deleting area');
                }
            } catch (err) {
                alert('Connection error');
            }
        }
    };
}
</script>
@endsection
