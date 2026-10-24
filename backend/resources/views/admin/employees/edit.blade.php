@extends('admin.layouts.app')

@section('title', 'Edit Employee')
@section('breadcrumb', 'Admin / Employees / Edit')
@section('page_title', 'Edit Employee — ' . $employee->name)

@section('content')
<div x-data="editEmployeeForm({
    updateUrl: '{{ route('admin.employees.update', $employee->id) }}',
    deleteUrl: '{{ route('admin.employees.destroy', $employee->id) }}',
    indexUrl: '{{ route('admin.employees.index') }}',
    csrfToken: '{{ csrf_token() }}'
})" class="max-w-4xl space-y-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-ink-600 hover:text-ink-900 transition">
            <x-lucide-arrow-left class="w-4 h-4" /> Back to All Employees
        </a>
    </div>

    {{-- Notification Banner --}}
    <div x-show="bannerMessage" x-cloak class="flex items-center gap-3 rounded-xl border px-4 py-3 transition-all"
         :class="bannerType === 'success' ? 'border-green-200 bg-success-bg text-success-text' : 'border-red-200 bg-danger-bg text-danger-text'">
        <template x-if="bannerType === 'success'">
            <x-lucide-check-circle class="w-5 h-5 shrink-0" />
        </template>
        <template x-if="bannerType === 'error'">
            <x-lucide-alert-circle class="w-5 h-5 shrink-0" />
        </template>
        <div class="flex-1 text-sm font-medium" x-text="bannerMessage"></div>
        <button type="button" @click="bannerMessage = ''" class="opacity-70 hover:opacity-100">
            <x-lucide-x class="w-4 h-4" />
        </button>
    </div>

    <section class="panel overflow-hidden">
        <div class="panel-header flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-ink-900">Edit Employee Details</h2>
                <p class="text-xs text-ink-500 mt-0.5">Update employee personal, role, and customer assignments in real-time.</p>
            </div>
            <div class="flex items-center gap-2">
                <template x-if="form.is_active">
                    <span class="badge badge-success"><x-lucide-check-circle class="w-3 h-3" /> Active</span>
                </template>
                <template x-if="!form.is_active">
                    <span class="badge badge-danger"><x-lucide-x-circle class="w-3 h-3" /> Inactive</span>
                </template>
            </div>
        </div>

        <form @submit.prevent="submitUpdate()" class="p-6 space-y-8">
            {{-- Personal Information --}}
            <div>
                <h3 class="text-sm font-bold text-ink-900 border-b border-surface-border pb-2 mb-4 flex items-center gap-2">
                    <x-lucide-user class="w-4 h-4 text-primary-600" /> Personal Information
                </h3>

                {{-- Profile Photo Upload & Preview --}}
                <div class="mb-6 flex items-center gap-5 p-4 rounded-xl bg-surface-ground/50 border border-surface-border">
                    <div class="relative group shrink-0">
                        <template x-if="photoPreview">
                            <img :src="photoPreview" alt="Profile Preview"
                                 class="w-20 h-20 rounded-full object-cover shadow-sm border-2 border-primary-500">
                        </template>
                        <template x-if="!photoPreview">
                            <div class="w-20 h-20 rounded-full bg-surface-card border-2 border-dashed border-ink-300 flex flex-col items-center justify-center text-ink-400 group-hover:border-primary-500 group-hover:text-primary-600 transition-colors">
                                <x-lucide-camera class="w-6 h-6" />
                                <span class="text-[10px] mt-1 font-medium">Photo</span>
                            </div>
                        </template>
                    </div>
                    <div class="flex-1">
                        <label class="form-label text-xs block mb-1">Profile Photo</label>
                        <div class="flex items-center gap-2">
                            <label class="btn btn-secondary text-xs cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-3">
                                <x-lucide-upload class="w-3.5 h-3.5" />
                                <span x-text="photoPreview ? 'Change Photo' : 'Upload Photo'"></span>
                                <input type="file" accept="image/*" class="hidden" @change="handlePhotoChange($event)">
                            </label>
                            <button type="button" x-show="photoPreview" @click="removePhotoSelection()"
                                    class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-2 py-1">
                                Remove Photo
                            </button>
                        </div>
                        <p class="text-[11px] text-ink-400 mt-1">Recommended: PNG, JPG, or WEBP up to 5MB.</p>
                        <template x-if="errors.profile_image">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.profile_image[0]"></p>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {{-- Full Name --}}
                    <div>
                        <label class="form-label text-xs">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="form.name"
                               required
                               placeholder="e.g. John Doe"
                               class="form-input text-xs w-full"
                               :class="errors.name ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.name">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.name[0]"></p>
                        </template>
                    </div>

                    {{-- Employee ID --}}
                    <div>
                        <label class="form-label text-xs">Employee ID</label>
                        <input type="text"
                               x-model="form.employee_id"
                               placeholder="e.g. EMP001"
                               class="form-input text-xs w-full"
                               :class="errors.employee_id ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.employee_id">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.employee_id[0]"></p>
                        </template>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="form-label text-xs">Email Address <span class="text-rose-500">*</span></label>
                        <input type="email"
                               x-model="form.email"
                               required
                               class="form-input text-xs w-full"
                               :class="errors.email ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.email">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.email[0]"></p>
                        </template>
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="form-label text-xs">Phone Number</label>
                        <input type="text"
                               x-model="form.phone"
                               placeholder="+91 9876543210"
                               class="form-input text-xs w-full"
                               :class="errors.phone ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.phone">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.phone[0]"></p>
                        </template>
                    </div>

                    {{-- Date of Birth --}}
                    <div>
                        <label class="form-label text-xs">Date of Birth</label>
                        <input type="date"
                               x-model="form.date_of_birth"
                               max="{{ date('Y-m-d') }}"
                               class="form-input text-xs w-full"
                               :class="errors.date_of_birth ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.date_of_birth">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.date_of_birth[0]"></p>
                        </template>
                    </div>

                    {{-- Change Password --}}
                    <div>
                        <label class="form-label text-xs">Change Password (optional)</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   x-model="form.password"
                                   minlength="8"
                                   placeholder="Leave blank to keep unchanged"
                                   class="form-input text-xs w-full pr-8"
                                   :class="errors.password ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-400 hover:text-ink-700">
                                <template x-if="!showPassword">
                                    <x-lucide-eye class="w-3.5 h-3.5" />
                                </template>
                                <template x-if="showPassword">
                                    <x-lucide-eye-off class="w-3.5 h-3.5" />
                                </template>
                            </button>
                        </div>
                        <template x-if="errors.password">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.password[0]"></p>
                        </template>
                        <p x-show="!errors.password" class="mt-1 text-[11px] text-ink-400">Only fill this if you want to set a new password</p>
                    </div>

                    {{-- Address --}}
                    <div class="md:col-span-2">
                        <label class="form-label text-xs">Address</label>
                        <textarea x-model="form.address"
                                  rows="2"
                                  class="form-input text-xs w-full"
                                  :class="errors.address ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''"></textarea>
                        <template x-if="errors.address">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.address[0]"></p>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Employment Information --}}
            <div>
                <h3 class="text-sm font-bold text-ink-900 border-b border-surface-border pb-2 mb-4 flex items-center gap-2">
                    <x-lucide-briefcase class="w-4 h-4 text-primary-600" /> Employment Information
                </h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {{-- Department --}}
                    <div>
                        <label class="form-label text-xs">Department</label>
                        <input type="text"
                               x-model="form.department"
                               class="form-input text-xs w-full"
                               :class="errors.department ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.department">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.department[0]"></p>
                        </template>
                    </div>

                    {{-- Position --}}
                    <div>
                        <label class="form-label text-xs">Position / Designation</label>
                        <input type="text"
                               x-model="form.position"
                               class="form-input text-xs w-full"
                               :class="errors.position ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.position">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.position[0]"></p>
                        </template>
                    </div>

                    {{-- Hire Date --}}
                    <div>
                        <label class="form-label text-xs">Hire Date</label>
                        <input type="date"
                               x-model="form.hire_date"
                               class="form-input text-xs w-full"
                               :class="errors.hire_date ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.hire_date">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.hire_date[0]"></p>
                        </template>
                    </div>

                    {{-- Salary --}}
                    <div>
                        <label class="form-label text-xs">Salary (₹)</label>
                        <input type="number"
                               x-model="form.salary"
                               step="0.01"
                               min="0"
                               class="form-input text-xs w-full"
                               :class="errors.salary ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                        <template x-if="errors.salary">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.salary[0]"></p>
                        </template>
                    </div>

                    {{-- Reporting To --}}
                    <div class="md:col-span-2">
                        <label class="form-label text-xs font-bold text-ink-800">Reporting To (Manager / VP)</label>
                        <select x-model="form.reporting_to" class="form-input text-xs w-full"
                                :class="errors.reporting_to ? 'border-rose-500 ring-1 ring-rose-200 bg-rose-50/20' : ''">
                            <option value="">-- No Manager (Top Level / Direct Admin Report) --</option>
                            @foreach ($managers as $mgr)
                                <option value="{{ $mgr->id }}">
                                    {{ $mgr->name }} ({{ $mgr->role_name }})
                                </option>
                            @endforeach
                        </select>
                        <template x-if="errors.reporting_to">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="errors.reporting_to[0]"></p>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Roles & Status --}}
            <div>
                <h3 class="text-sm font-bold text-ink-900 border-b border-surface-border pb-2 mb-4 flex items-center gap-2">
                    <x-lucide-shield class="w-4 h-4 text-primary-600" /> Roles & Account Status
                </h3>
                
                <div class="mb-4">
                    <label class="form-label text-xs">Assign Role <span class="text-rose-500">*</span></label>
                    @if(auth()->user()->can('employees.assign-roles') || auth()->user()->hasRole('Super Admin'))
                    <select x-model="form.role" class="form-input text-xs w-full max-w-md">
                        <option value="">Select Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                    @else
                    <div class="px-3 py-2 bg-slate-100 rounded-lg text-xs font-semibold text-slate-700 max-w-md border border-slate-200 flex items-center justify-between">
                        <span>{{ $employee->roles->pluck('name')->first() ?? 'No Role' }}</span>
                        <span class="text-[10px] text-slate-400 font-normal">Role locked (requires permission)</span>
                    </div>
                    @endif
                    <template x-if="errors.role || errors.roles">
                        <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="(errors.role || errors.roles)[0]"></p>
                    </template>
                </div>

                <div class="p-3 bg-surface-subtle rounded-xl border border-surface-border flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-ink-900 block">Account Status</span>
                        <span class="text-[11px] text-ink-500" x-text="form.is_active ? 'Active (Employee can login and perform field operations)' : 'Inactive (Account is disabled)'"></span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-9 h-5 bg-surface-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            {{-- Assigned Territories (Districts & Areas) --}}
            <div>
                <h3 class="text-sm font-bold text-ink-900 border-b border-surface-border pb-2 mb-2 flex items-center gap-2">
                    <x-lucide-map-pin class="w-4 h-4 text-primary-600" /> Assigned Territories (Districts & Areas)
                </h3>
                <p class="mb-3 text-xs text-ink-500">
                    Assign whole districts or specific beats/areas to this employee. The mobile app user will automatically see and have access to all parties located in these territories.
                </p>

                <div class="mb-3 relative max-w-sm">
                    <input type="text"
                           x-model="territorySearch"
                           class="form-input text-xs pl-8 w-full"
                           placeholder="Filter districts or areas...">
                    <x-lucide-search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400" />
                </div>

                <div class="max-h-80 overflow-y-auto rounded-xl border border-surface-border p-3 bg-surface-subtle/40 space-y-3">
                    @forelse ($districts as $district)
                        <div x-show="!territorySearch || '{{ strtolower(addslashes($district->name)) }}'.includes(territorySearch.toLowerCase()) || {{ Js::from($district->areas->pluck('name')->map(fn($n) => strtolower($n))) }}.some(a => a.includes(territorySearch.toLowerCase()))"
                             class="p-3 bg-white border border-surface-border rounded-lg shadow-2xs">
                            <div class="flex items-center justify-between pb-2 border-b border-surface-border/60">
                                <label class="flex items-center gap-2 text-xs font-bold text-ink-900 cursor-pointer">
                                    <input type="checkbox"
                                           :checked="isDistrictSelected({{ $district->id }})"
                                           @change="toggleDistrict({{ $district->id }})"
                                           class="rounded border-surface-border text-primary-600 focus:ring-primary-500">
                                    <span>{{ $district->name }} <span class="text-[10px] font-normal text-ink-500">(Entire District)</span></span>
                                </label>
                                <span class="text-[10px] text-ink-400 font-medium">{{ $district->areas->count() }} Areas</span>
                            </div>

                            @if($district->areas->isNotEmpty())
                                <div class="mt-2.5 pt-1 pl-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    @foreach($district->areas as $area)
                                        <label x-show="!territorySearch || '{{ strtolower(addslashes($area->name)) }}'.includes(territorySearch.toLowerCase()) || '{{ strtolower(addslashes($district->name)) }}'.includes(territorySearch.toLowerCase())"
                                               class="flex items-center gap-1.5 p-1.5 rounded border border-surface-border/50 text-[11px] font-medium text-ink-700 hover:bg-surface-subtle cursor-pointer"
                                               :class="isAreaSelected({{ $area->id }}) ? 'border-primary-500 bg-primary-50/20 text-primary-700' : ''">
                                            <input type="checkbox"
                                                   :checked="isAreaSelected({{ $area->id }})"
                                                   @change="toggleArea({{ $area->id }})"
                                                   class="rounded border-surface-border text-primary-600 focus:ring-primary-500">
                                            <span class="truncate">{{ $area->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-ink-400 py-2">No districts found in system.</p>
                    @endforelse
                </div>
            </div>

            {{-- Assigned Parties --}}
            <div>
                <h3 class="text-sm font-bold text-ink-900 border-b border-surface-border pb-2 mb-2 flex items-center gap-2">
                    <x-lucide-building class="w-4 h-4 text-primary-600" /> Assigned Parties (Direct Mapping)
                </h3>
                <p class="mb-3 text-xs text-ink-500">
                    Directly assign specific individual parties to this employee regardless of territory.
                </p>

                <div class="mb-3 relative max-w-sm">
                    <input type="text"
                           x-model="partySearch"
                           class="form-input text-xs pl-8 w-full"
                           placeholder="Filter parties list...">
                    <x-lucide-search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-ink-400" />
                </div>

                <div class="max-h-56 overflow-y-auto rounded-xl border border-surface-border p-3 bg-surface-subtle/40">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @forelse ($customers as $customer)
                            <label x-show="!partySearch || '{{ strtolower(addslashes($customer->name)) }}'.includes(partySearch.toLowerCase())"
                                   class="flex items-center gap-2 rounded-lg border border-surface-border bg-white px-3 py-2 text-xs font-medium text-ink-800 transition hover:bg-surface-subtle cursor-pointer"
                                   :class="form.parties.includes('{{ $customer->id }}') ? 'border-primary-500 ring-1 ring-primary-300' : ''">
                                <input type="checkbox"
                                       value="{{ $customer->id }}"
                                       x-model="form.parties"
                                       class="rounded border-surface-border text-primary-600 focus:ring-primary-500">
                                <span class="truncate">{{ $customer->name }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-ink-400 py-2">No parties found in system.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-between border-t border-surface-border pt-5">
                <a href="{{ route('admin.employees.index') }}" class="btn-secondary text-xs px-4 py-2">
                    Cancel
                </a>
                <button type="submit"
                        :disabled="submitting"
                        class="btn-primary text-xs px-5 py-2 inline-flex items-center gap-2 disabled:opacity-50"
                        style="background-color: #4f46e5; color: #ffffff;">
                    <template x-if="submitting">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="submitting ? 'Saving Changes...' : 'Update Employee'"></span>
                </button>
            </div>
        </form>

        @if(auth()->id() !== $employee->id)
            <div class="p-6 pt-0 flex justify-end border-t border-surface-border mt-2">
                <button type="button"
                        @click="deleteEmployee()"
                        :disabled="deleting"
                        class="btn-danger text-xs px-4 py-2 inline-flex items-center gap-1.5"
                        style="background-color: #e11d48; color: #ffffff;">
                    <x-lucide-trash-2 class="w-3.5 h-3.5" />
                    <span x-text="deleting ? 'Deleting...' : 'Delete Employee'"></span>
                </button>
            </div>
        @endif
    </section>
</div>

@push('scripts')
<script>
function editEmployeeForm(config) {
    return {
        form: {
            name: @js($employee->name),
            employee_id: @js($employee->employee_id ?? ''),
            email: @js($employee->email),
            phone: @js($employee->phone ?? ''),
            date_of_birth: @js($employee->date_of_birth?->format('Y-m-d') ?? ''),
            password: '',
            address: @js($employee->address ?? ''),
            department: @js($employee->department ?? ''),
            position: @js($employee->position ?? ''),
            hire_date: @js($employee->hire_date?->format('Y-m-d') ?? ''),
            salary: @js($employee->salary ?? ''),
            reporting_to: @js($employee->reporting_to ? (string)$employee->reporting_to : ''),
            is_active: @js((bool)$employee->is_active),
            role: @js($employee->roles->pluck('name')->first() ?? ''),
            roles: @js($employee->roles->pluck('name')->toArray()),
            parties: @js(array_map('strval', $assignedPartyIds)),
            territory_districts: @js(array_map('intval', $assignedDistrictIds ?? [])),
            territory_areas: @js(array_map('intval', $assignedAreaIds ?? []))
        },
        photoFile: null,
        photoPreview: @js($employee->profile_image_url ?? null),
        removePhoto: false,
        partySearch: '',
        territorySearch: '',
        showPassword: false,
        submitting: false,
        deleting: false,
        errors: {},
        bannerMessage: '',
        bannerType: 'success',

        toggleDistrict(districtId) {
            const id = parseInt(districtId);
            const idx = this.form.territory_districts.indexOf(id);
            if (idx > -1) {
                this.form.territory_districts.splice(idx, 1);
            } else {
                this.form.territory_districts.push(id);
            }
        },
        isDistrictSelected(districtId) {
            return this.form.territory_districts.includes(parseInt(districtId));
        },
        toggleArea(areaId) {
            const id = parseInt(areaId);
            const idx = this.form.territory_areas.indexOf(id);
            if (idx > -1) {
                this.form.territory_areas.splice(idx, 1);
            } else {
                this.form.territory_areas.push(id);
            }
        },
        isAreaSelected(areaId) {
            return this.form.territory_areas.includes(parseInt(areaId));
        },

        handlePhotoChange(e) {
            const file = e.target.files[0];
            if (file) {
                this.photoFile = file;
                this.photoPreview = URL.createObjectURL(file);
                this.removePhoto = false;
            }
        },

        removePhotoSelection() {
            this.photoFile = null;
            this.photoPreview = null;
            this.removePhoto = true;
        },

        showBanner(msg, type = 'success') {
            this.bannerMessage = msg;
            this.bannerType = type;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (type === 'success') {
                setTimeout(() => {
                    window.location.href = config.indexUrl;
                }, 1200);
            }
        },

        async submitUpdate() {
            this.submitting = true;
            this.errors = {};
            this.bannerMessage = '';

            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');

                formData.append('has_territory_sync', '1');

                for (const [key, val] of Object.entries(this.form)) {
                    if (key === 'roles' && Array.isArray(val)) {
                        val.forEach(r => formData.append('roles[]', r));
                    } else if (key === 'parties' && Array.isArray(val)) {
                        val.forEach(p => formData.append('parties[]', p));
                    } else if (key === 'territory_districts' && Array.isArray(val)) {
                        val.forEach(d => formData.append('territory_districts[]', d));
                    } else if (key === 'territory_areas' && Array.isArray(val)) {
                        val.forEach(a => formData.append('territory_areas[]', a));
                    } else if (key === 'password') {
                        if (val && String(val).trim().length > 0) {
                            formData.append('password', val);
                        }
                    } else if (key === 'is_active') {
                        formData.append('is_active', val ? '1' : '0');
                    } else if (val !== null && val !== undefined && val !== '') {
                        formData.append(key, val);
                    }
                }

                if (this.form.role && (!Array.isArray(this.form.roles) || !this.form.roles.length)) {
                    formData.append('roles[]', this.form.role);
                }

                if (this.photoFile) {
                    formData.append('profile_image', this.photoFile);
                }

                if (this.removePhoto) {
                    formData.append('remove_profile_image', '1');
                }

                const response = await fetch(config.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showBanner(data.message || 'Employee updated successfully! Redirecting...', 'success');
                } else if (response.status === 422) {
                    this.errors = data.errors || {};
                    const firstMsg = Object.values(this.errors).flat()[0];
                    this.showBanner(firstMsg || data.message || 'Please fix the validation errors below.', 'error');
                } else {
                    this.showBanner(data.message || 'An error occurred while updating the employee.', 'error');
                }
            } catch (err) {
                console.error('AJAX Error:', err);
                this.showBanner('Failed to submit form. Please check your network connection.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async deleteEmployee() {
            if (!confirm('Are you sure you want to delete this employee? This action cannot be undone.')) return;

            this.deleting = true;
            try {
                const response = await fetch(config.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showBanner(data.message || 'Employee deleted successfully. Redirecting...', 'success');
                } else {
                    this.showBanner(data.message || 'Failed to delete employee.', 'error');
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showBanner('An unexpected error occurred while deleting.', 'error');
            } finally {
                this.deleting = false;
            }
        }
    };
}
</script>
@endpush
@endsection

