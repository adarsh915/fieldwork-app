@extends('admin.layouts.app')

@section('title', 'Leave Rules & Policy')
@section('breadcrumb', 'ADMIN / LEAVE RULES')
@section('page_title', 'Leave Policy & Entitlement Rules')

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('success') }}</p>
    </div>
@endif

<div x-data="{ createModalOpen: false, editModalOpen: false, editItem: {} }">

    {{-- Top Action Bar --}}
    <div class="panel p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900">Leave Entitlement Rules</h2>
                <p class="text-caption mt-0.5">Manage yearly quotas, monthly accrual rates, and carry forward policies.</p>
            </div>

            <x-button type="button" variant="primary" @click="createModalOpen = true" class="flex items-center gap-2">
                <x-lucide-plus class="w-4 h-4" /> Add Leave Type
            </x-button>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Leave Type Name</th>
                        <th>Accrual Frequency</th>
                        <th>Quota Rate</th>
                        <th>Carry Forward</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveTypes as $type)
                    <tr>
                        <td>
                            <span class="badge badge-primary font-mono font-bold text-xs">{{ $type->code }}</span>
                        </td>
                        <td class="font-bold text-ink-900">
                            {{ $type->name }}
                        </td>
                        <td>
                            @if($type->accrual_frequency === 'monthly')
                                <span class="badge badge-warning">📅 Monthly Accrual</span>
                            @else
                                <span class="badge badge-primary">🗓️ Annual Upfront</span>
                            @endif
                        </td>
                        <td class="font-extrabold text-ink-900">
                            @if($type->accrual_frequency === 'monthly')
                                {{ $type->days_per_month }} days / month
                            @else
                                {{ $type->days_per_year == 0 ? 'Unlimited' : $type->days_per_year . ' days / year' }}
                            @endif
                        </td>
                        <td>
                            @if($type->carry_forward)
                                <span class="badge badge-success">Allowed</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            @if($type->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <x-button type="button" variant="secondary" class="px-3 py-1 text-xs"
                                      @click="editModalOpen = true; editItem = { id: {{ $type->id }}, name: '{{ addslashes($type->name) }}', code: '{{ addslashes($type->code) }}', days_per_year: {{ $type->days_per_year }}, accrual_frequency: '{{ $type->accrual_frequency ?? 'annual' }}', days_per_month: {{ $type->days_per_month ?? 0 }}, carry_forward: {{ $type->carry_forward ? 'true' : 'false' }}, is_active: {{ $type->is_active ? 'true' : 'false' }} }">
                                <x-lucide-pencil class="w-3.5 h-3.5 inline mr-1" /> Edit Rules
                            </x-button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-6 text-ink-500">No leave types defined.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create Leave Type Modal --}}
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="createModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border" x-data="{ freq: 'annual' }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-ink-900">Add Leave Type</h3>
                <button type="button" @click="createModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>
            <form action="{{ route('admin.leave-types.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Leave Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Casual Leave" class="form-input" />
                </div>

                <div class="mb-4">
                    <label class="form-label">Short Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" required placeholder="e.g. CL" class="form-input uppercase" maxLength="10" />
                </div>

                <div class="mb-4">
                    <label class="form-label">Accrual Policy Frequency <span class="text-rose-500">*</span></label>
                    <select name="accrual_frequency" x-model="freq" class="form-input" required>
                        <option value="annual">Annual Upfront (Granted Jan 1st)</option>
                        <option value="monthly">Monthly Accrual (Granted 1st of every month)</option>
                    </select>
                </div>

                <div class="mb-4" x-show="freq === 'annual'">
                    <label class="form-label">Allowed Days Per Year <span class="text-rose-500">*</span></label>
                    <input type="number" name="days_per_year" value="12" required min="0" placeholder="e.g. 12 (0 for unlimited)" class="form-input" />
                </div>

                <div class="mb-4" x-show="freq === 'monthly'">
                    <label class="form-label">Allowed Days Granted Per Month <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" name="days_per_month" value="1.5" min="0" placeholder="e.g. 1.5 days/month" class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">System automatically adds this amount to each fieldworker's balance on the 1st of every month.</p>
                </div>

                <div class="mb-6 flex items-center gap-2">
                    <input type="checkbox" name="carry_forward" value="1" id="carry_forward_create" class="rounded border-surface-border text-brand-600 focus:ring-brand-500" />
                    <label for="carry_forward_create" class="text-sm font-medium text-ink-700">Allow Carry Forward to Next Year</label>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="createModalOpen = false">Cancel</x-button>
                    <x-button type="submit" variant="primary">Save Leave Type</x-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Leave Type Modal --}}
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="editModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-ink-900">Edit Leave Rule</h3>
                <button type="button" @click="editModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>
            <form :action="'{{ url('admin/leave-types') }}/' + editItem.id" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="form-label">Leave Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editItem.name" required class="form-input" />
                </div>

                <div class="mb-4">
                    <label class="form-label">Short Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" x-model="editItem.code" required class="form-input uppercase" maxLength="10" />
                </div>

                <div class="mb-4">
                    <label class="form-label">Accrual Policy Frequency <span class="text-rose-500">*</span></label>
                    <select name="accrual_frequency" x-model="editItem.accrual_frequency" class="form-input" required>
                        <option value="annual">Annual Upfront (Granted Jan 1st)</option>
                        <option value="monthly">Monthly Accrual (Granted 1st of every month)</option>
                    </select>
                </div>

                <div class="mb-4" x-show="editItem.accrual_frequency === 'annual'">
                    <label class="form-label">Allowed Days Per Year <span class="text-rose-500">*</span></label>
                    <input type="number" name="days_per_year" x-model="editItem.days_per_year" required min="0" class="form-input" />
                </div>

                <div class="mb-4" x-show="editItem.accrual_frequency === 'monthly'">
                    <label class="form-label">Allowed Days Granted Per Month <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" name="days_per_month" x-model="editItem.days_per_month" min="0" class="form-input" />
                </div>

                <div class="mb-4 flex items-center gap-2">
                    <input type="checkbox" name="carry_forward" value="1" :checked="editItem.carry_forward" id="carry_forward_edit" class="rounded border-surface-border text-brand-600 focus:ring-brand-500" />
                    <label for="carry_forward_edit" class="text-sm font-medium text-ink-700">Allow Carry Forward</label>
                </div>

                <div class="mb-6 flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" id="is_active_edit" class="rounded border-surface-border text-brand-600 focus:ring-brand-500" />
                    <label for="is_active_edit" class="text-sm font-medium text-ink-700">Active Status</label>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="editModalOpen = false">Cancel</x-button>
                    <x-button type="submit" variant="primary">Update Rules</x-button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
