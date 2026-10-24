@extends('admin.layouts.app')

@section('title', 'Expenses')
@section('breadcrumb', 'ADMIN / EXPENSES')
@section('page_title', 'Expense Tracking & Claims')

@section('content')
    <div id="expenses-container" class="panel" x-data="{
        userId: '{{ request('user_id') }}',
        status: '{{ request('status') }}',
        timing: '{{ request('timing') }}',
        month: '{{ request('month') }}',
        search: '{{ request('search') }}',
        canTempApprove: {{ $canTempApprove ? 'true' : 'false' }},
        canFinalApprove: {{ $canFinalApprove ? 'true' : 'false' }},
        canEdit: {{ $canEdit ? 'true' : 'false' }},

        applyFilters() {
            if (window.expenseTable) {
                window.expenseTable.draw();
            }
        },

        setStatus(newStatus) {
            this.status = newStatus;
            if (window.expenseTable) {
                window.expenseTable.draw();
            }
        },

        resetFilters() {
            this.userId = '';
            this.status = '';
            this.timing = '';
            this.month = '';
            this.search = '';
            if (window.expenseTable) {
                window.expenseTable.draw();
            }
        },

        // Edit Modal State
        editModal: {
            isOpen: false,
            saving: false,
            id: null,
            userName: '',
            isSystemTravel: false,
            amount: '',
            categoryId: '',
            date: '',
            status: 'pending',
            rejectionReason: '',
            note: '',
            billPhotoUrl: '',
            newPhotoPreview: null,
            updateUrl: '',
            recordRef: null
        },

        openEditModal(record) {
            this.editModal = {
                isOpen: true,
                saving: false,
                id: record.id,
                userName: record.user_name,
                isSystemTravel: record.is_system_generated,
                amount: record.raw_amount ?? record.amount_formatted.replace(/,/g, ''),
                categoryId: record.category_id || '',
                date: record.raw_date || '',
                status: record.status,
                rejectionReason: record.rejection_reason || '',
                note: record.final_note || record.temp_approved_note || '',
                billPhotoUrl: record.bill_photo_url || '',
                newPhotoPreview: null,
                updateUrl: record.update_url,
                recordRef: record
            };
            if (this.$refs.editBillPhotoInput) {
                this.$refs.editBillPhotoInput.value = '';
            }
        },

        handleEditPhotoChange(event) {
            const file = event.target.files && event.target.files[0];
            if (file) {
                this.editModal.newPhotoPreview = URL.createObjectURL(file);
            } else {
                this.editModal.newPhotoPreview = null;
            }
        },

        removeNewPhoto() {
            this.editModal.newPhotoPreview = null;
            if (this.$refs.editBillPhotoInput) {
                this.$refs.editBillPhotoInput.value = '';
            }
        },

        closeEditModal() {
            this.editModal.isOpen = false;
            this.editModal.saving = false;
            this.editModal.newPhotoPreview = null;
            if (this.$refs.editBillPhotoInput) {
                this.$refs.editBillPhotoInput.value = '';
            }
        },

        async submitEdit() {
            if (!this.editModal.amount || isNaN(this.editModal.amount) || Number(this.editModal.amount) < 0) {
                window.toast('Please enter a valid amount.', 'warning');
                return;
            }
            if (['manager_rejected', 'rejected'].includes(this.editModal.status) && !this.editModal.rejectionReason.trim()) {
                window.toast('Please provide a rejection reason when setting status to rejected.', 'warning');
                return;
            }

            this.editModal.saving = true;
            try {
                const formData = new FormData();
                formData.append('_method', 'PATCH');
                formData.append('amount', Number(this.editModal.amount));
                if (this.editModal.categoryId) {
                    formData.append('category_id', this.editModal.categoryId);
                }
                if (this.editModal.date) {
                    formData.append('date', this.editModal.date);
                }
                if (this.editModal.status) {
                    formData.append('status', this.editModal.status);
                }
                if (this.editModal.rejectionReason) {
                    formData.append('rejection_reason', this.editModal.rejectionReason);
                }
                if (this.editModal.note) {
                    formData.append('final_note', this.editModal.note);
                }
                if (this.$refs.editBillPhotoInput && this.$refs.editBillPhotoInput.files && this.$refs.editBillPhotoInput.files[0]) {
                    formData.append('bill_photo', this.$refs.editBillPhotoInput.files[0]);
                }

                const res = await fetch(this.editModal.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (this.editModal.recordRef && data.record) {
                        Object.assign(this.editModal.recordRef, data.record);
                    }
                    window.toast(data.message || 'Expense updated successfully.', 'success');
                    this.closeEditModal();
                    if (window.expenseTable) {
                        window.expenseTable.ajax.reload(null, false);
                    }
                } else {
                    window.toast(data.message || 'Failed to update expense.', 'error');
                }
            } catch (e) {
                window.toast('Error updating expense.', 'error');
            } finally {
                this.editModal.saving = false;
            }
        },

        // Approval / Rejection Action Modal State
        actionModal: {
            isOpen: false,
            submitting: false,
            record: null,
            action: '',
            title: '',
            promptLabel: '',
            confirmButtonClass: '',
            confirmButtonText: '',
            note: '',
            isRejection: false
        },

        openActionModal(record, action) {
            let title = '';
            let promptLabel = 'Notes / Remarks (Optional)';
            let confirmButtonClass = 'text-white';
            let confirmButtonStyle = 'background-color: #16a34a !important; color: #ffffff !important;';
            let confirmButtonText = 'Confirm Approval';
            let isRejection = false;

            if (action === 'temp_approve') {
                title = 'Level 1 Verification (Temp Approve)';
                promptLabel = 'Verification Remark (Optional)';
                confirmButtonClass = 'text-white';
                confirmButtonStyle = 'background-color: #2563eb !important; color: #ffffff !important;';
                confirmButtonText = '✓ Verify (Level 1)';
            } else if (action === 'final_approve') {
                title = 'Final Approval (HR / Admin / Finance)';
                promptLabel = 'Approval Note (Optional)';
                confirmButtonClass = 'text-white';
                confirmButtonStyle = 'background-color: #16a34a !important; color: #ffffff !important;';
                confirmButtonText = '★ Confirm Final Approval';
            } else if (action === 'manager_reject') {
                title = 'Reject Claim (Manager Level)';
                promptLabel = 'Reason for Rejection *';
                confirmButtonClass = 'text-white';
                confirmButtonStyle = 'background-color: #dc2626 !important; color: #ffffff !important;';
                confirmButtonText = '✕ Reject Claim';
                isRejection = true;
            } else if (action === 'final_reject') {
                title = 'Permanent Rejection (Final Decision)';
                promptLabel = 'Reason for Rejection *';
                confirmButtonClass = 'text-white';
                confirmButtonStyle = 'background-color: #b91c1c !important; color: #ffffff !important;';
                confirmButtonText = '✕ Permanently Reject';
                isRejection = true;
            }

            this.actionModal = {
                isOpen: true,
                submitting: false,
                record: record,
                action: action,
                title: title,
                promptLabel: promptLabel,
                confirmButtonClass: confirmButtonClass,
                confirmButtonStyle: confirmButtonStyle,
                confirmButtonText: confirmButtonText,
                note: '',
                isRejection: isRejection
            };
        },

        closeActionModal() {
            this.actionModal.isOpen = false;
            this.actionModal.submitting = false;
            this.actionModal.note = '';
            this.actionModal.record = null;
        },

        async submitAction() {
            if (this.actionModal.isRejection && !this.actionModal.note.trim()) {
                window.toast('Please provide a reason for the rejection.', 'warning');
                return;
            }

            this.actionModal.submitting = true;
            const record = this.actionModal.record;

            try {
                const res = await fetch(record.update_status_url, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: this.actionModal.action,
                        note: this.actionModal.note
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    if (data.record && record) {
                        Object.assign(record, data.record);
                    } else if (data.status && record) {
                        record.status = data.status;
                    }
                    window.toast(data.message || 'Status updated successfully.', 'success');
                    this.closeActionModal();
                    if (window.expenseTable) {
                        window.expenseTable.ajax.reload(null, false);
                    }
                } else {
                    window.toast(data.message || 'Failed to update status.', 'error');
                }
            } catch (e) {
                window.toast('Error submitting approval action.', 'error');
            } finally {
                this.actionModal.submitting = false;
            }
        },

        // History / Audit Modal State
        historyModal: {
            isOpen: false,
            record: null
        },

        openHistoryModal(record) {
            this.historyModal = {
                isOpen: true,
                record: record
            };
        },

        closeHistoryModal() {
            this.historyModal.isOpen = false;
            this.historyModal.record = null;
        }
    }">
        {{-- Header --}}
        <div class="panel-header space-y-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-section-title">Expense Claims & Approvals</h2>
                    <p class="text-caption mt-1">Multi-level verification system: Level 1 (Manager/Lead) &bull; Level 2
                        (Final HR/Admin payout sign-off).</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.expenses.reports.monthly') }}"
                        class="btn-secondary flex items-center gap-2 text-xs">
                        <x-lucide-calendar class="w-4 h-4" />
                        Monthly Report
                    </a>
                </div>
            </div>

            {{-- Quick Status Filter Tabs --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs border-b border-surface-border">
                <button type="button" @click="setStatus('')"
                    :class="status === '' ? 'bg-ink-900 text-white font-bold shadow-sm' : 'bg-surface-muted text-ink-600 hover:bg-surface-border'"
                    class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                    All Claims
                </button>
                <button type="button" @click="setStatus('pending')"
                    :class="status === 'pending' ? 'bg-amber-600 text-white font-bold shadow-sm' : 'bg-surface-muted text-ink-600 hover:bg-amber-50 hover:text-amber-700'"
                    class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
                    Pending Level 1
                </button>
                <button type="button" @click="setStatus('temp_approved')"
                    :class="status === 'temp_approved' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'bg-surface-muted text-ink-600 hover:bg-blue-50 hover:text-blue-700'"
                    class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
                    Verified / Ready for Final (HR)
                </button>
                <button type="button" @click="setStatus('manager_rejected')"
                    :class="status === 'manager_rejected' ? 'bg-rose-600 text-white font-bold shadow-sm' : 'bg-surface-muted text-ink-600 hover:bg-rose-50 hover:text-rose-700'"
                    class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-400 inline-block"></span>
                    Manager Rejected (Review)
                </button>
                <button type="button" @click="setStatus('approved')"
                    :class="status === 'approved' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-surface-muted text-ink-600 hover:bg-emerald-50 hover:text-emerald-700'"
                    class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                    Final Approved
                </button>
                <button type="button" @click="setStatus('rejected')"
                    :class="status === 'rejected' ? 'bg-rose-800 text-white font-bold shadow-sm' : 'bg-surface-muted text-ink-600 hover:bg-rose-50 hover:text-rose-800'"
                    class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-700 inline-block"></span>
                    Final Rejected
                </button>
            </div>

            {{-- Filter Form --}}
            <form @submit.prevent="applyFilters()" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
                <div>
                    <label class="form-label">Field Worker</label>
                    <select x-model="userId" @change="applyFilters()" class="form-input">
                        <option value="">All Workers</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }} ({{ $user->role_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select x-model="status" @change="applyFilters()" class="form-input">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending Level 1</option>
                        <option value="temp_approved">Verified / Ready for Final</option>
                        <option value="manager_rejected">Manager Rejected</option>
                        <option value="approved">Final Approved</option>
                        <option value="rejected">Final Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Timing</label>
                    <select x-model="timing" @change="applyFilters()" class="form-input">
                        <option value="">All Timings</option>
                        <option value="during_shift">During Shift</option>
                        <option value="after_day_end">After Day End</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Month</label>
                    <input type="month" x-model="month" @change="applyFilters()" class="form-input">
                </div>
                <div>
                    <label class="form-label">Search</label>
                    <input type="text" x-model="search" @keydown.enter.prevent="applyFilters()"
                        placeholder="Category, worker, amount..." class="form-input">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary flex-1 py-2 text-sm font-semibold">
                        <span class="flex items-center justify-center gap-1.5">
                            <x-lucide-filter class="w-4 h-4" />
                            Filter
                        </span>
                    </button>
                    <button type="button" @click="resetFilters()" class="btn-secondary px-3 py-2 text-sm">
                        Reset
                    </button>
                </div>
            </form>
        </div>

        {{-- DataTable Wrap --}}
        <div class="table-wrap min-h-[350px]">
            <table id="expenses-table" class="data-table">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">Date</th>
                        <th>Worker</th>
                        <th>Category</th>
                        <th class="text-right whitespace-nowrap">Amount</th>
                        <th>Timing</th>
                        <th class="text-center whitespace-nowrap">Photo Proof</th>
                        <th>Approval Status</th>
                        <th class="text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- DataTables serverSide will populate this --}}
                </tbody>
            </table>
        </div>

        {{-- Approval / Rejection Action Modal --}}
        <div x-show="actionModal.isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-transition.opacity style="display: none;">
            <div @click.away="closeActionModal"
                class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-surface-border"
                x-transition.scale>
                <div class="px-6 py-4 border-b border-surface-border bg-surface-muted/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-ink-900 text-base" x-text="actionModal.title"></h3>
                        <p class="text-xs text-ink-500"
                            x-text="actionModal.record ? ('Claim #' + actionModal.record.id + ' • ' + actionModal.record.user_name + ' • ₹' + actionModal.record.amount_formatted) : ''">
                        </p>
                    </div>
                    <button type="button" @click="closeActionModal" class="text-ink-400 hover:text-ink-700 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAction" class="p-6 space-y-4">
                    {{-- Claim Quick Summary Box --}}
                    <div class="p-3 bg-surface-muted rounded-lg border border-surface-border text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-ink-500">Employee:</span>
                            <span class="font-semibold text-ink-900" x-text="actionModal.record?.user_name"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-500">Category:</span>
                            <span class="font-medium text-ink-800" x-text="actionModal.record?.category_name"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-500">Claim Date:</span>
                            <span class="font-medium text-ink-800" x-text="actionModal.record?.date_formatted"></span>
                        </div>
                        <div class="flex justify-between border-t border-surface-border pt-1 font-bold text-sm">
                            <span class="text-ink-700">Amount:</span>
                            <span class="text-emerald-700"
                                x-text="'₹' + (actionModal.record?.amount_formatted || '0.00')"></span>
                        </div>
                    </div>

                    {{-- Note / Reason Textarea --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-ink-700"
                            x-text="actionModal.promptLabel"></label>
                        <textarea x-model="actionModal.note" rows="3" class="form-input mt-1 w-full text-xs"
                            :placeholder="actionModal.isRejection ? 'Please write the reason for rejection here (mandatory)...' : 'Add an optional remark or settlement note...'"></textarea>
                    </div>

                    {{-- Modal Actions --}}
                    <div class="pt-3 border-t border-surface-border flex items-center justify-end gap-2">
                        <button type="button" @click="closeActionModal" class="btn-secondary px-4 py-2 text-sm"
                            :disabled="actionModal.submitting">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold rounded-lg shadow-sm flex items-center gap-2 transition hover:opacity-90"
                            :class="actionModal.confirmButtonClass" :style="actionModal.confirmButtonStyle"
                            :disabled="actionModal.submitting">
                            <span x-show="!actionModal.submitting" x-text="actionModal.confirmButtonText"></span>
                            <span x-show="actionModal.submitting" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- History / Audit Timeline Modal --}}
        <div x-show="historyModal.isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-transition.opacity style="display: none;">
            <div @click.away="closeHistoryModal"
                class="bg-white rounded-xl shadow-2xl max-w-xl w-full overflow-hidden border border-surface-border"
                x-transition.scale>
                <div class="px-6 py-4 border-b border-surface-border bg-surface-muted/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-ink-900 text-base flex items-center gap-2">
                            <x-lucide-history class="w-4 h-4 text-brand-600" />
                            Approval History & Audit Trail
                        </h3>
                        <p class="text-xs text-ink-500"
                            x-text="historyModal.record ? ('Claim #' + historyModal.record.id + ' • ' + historyModal.record.user_name + ' • ₹' + historyModal.record.amount_formatted) : ''">
                        </p>
                    </div>
                    <button type="button" @click="closeHistoryModal" class="text-ink-400 hover:text-ink-700 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <template x-if="historyModal.record">
                        <div
                            class="relative pl-6 space-y-8 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-border">

                            {{-- Step 1: Submission --}}
                            <div class="relative group">
                                <div
                                    class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-emerald-500 ring-4 ring-white flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                                <div class="bg-surface-muted/60 p-3.5 rounded-lg border border-surface-border">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-ink-900">Step 1: Submitted by Worker</span>
                                        <span class="text-[11px] text-ink-500 font-medium"
                                            x-text="historyModal.record.date_formatted"></span>
                                    </div>
                                    <div class="mt-1 text-xs text-ink-700">
                                        <span class="font-medium" x-text="historyModal.record.user_name"></span> submitted
                                        claim for <strong class="text-ink-900"
                                            x-text="'₹' + historyModal.record.amount_formatted"></strong>
                                        (<span x-text="historyModal.record.category_name"></span>).
                                    </div>
                                    <div class="mt-2 flex items-center gap-2 text-[11px]">
                                        <template x-if="historyModal.record.is_after_day_end">
                                            <span
                                                class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">⚠️
                                                Submitted after day end</span>
                                        </template>
                                        <template x-if="!historyModal.record.is_after_day_end">
                                            <span
                                                class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">✓
                                                Submitted during shift</span>
                                        </template>
                                        <template x-if="historyModal.record.bill_photo_url">
                                            <a :href="historyModal.record.bill_photo_url" target="_blank"
                                                class="text-brand-600 hover:underline font-medium inline-flex items-center gap-1">
                                                <span>📷 View Attached Bill Photo</span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Step 2: Level 1 Verification (Manager / Team Lead) --}}
                            <div class="relative group">
                                <template x-if="historyModal.record.temp_approved_by">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-blue-500 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template x-if="historyModal.record.status === 'manager_rejected'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-rose-500 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template
                                    x-if="!historyModal.record.temp_approved_by && historyModal.record.status !== 'manager_rejected'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-amber-400 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>

                                <div class="bg-surface-muted/60 p-3.5 rounded-lg border border-surface-border">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-ink-900">Step 2: Level 1 Review (Manager /
                                            Lead)</span>
                                        <span class="text-[11px] text-ink-500"
                                            x-text="historyModal.record.temp_approved_at || (historyModal.record.status === 'manager_rejected' ? historyModal.record.rejected_at : 'Pending')"></span>
                                    </div>

                                    {{-- Temp Approved --}}
                                    <template x-if="historyModal.record.temp_approved_by">
                                        <div class="mt-1 text-xs">
                                            <div class="text-blue-700 font-semibold flex items-center gap-1">
                                                <span>✓ Verified & Forwarded for Final Approval</span>
                                            </div>
                                            <div class="text-ink-600 mt-0.5">
                                                Verified by: <strong class="text-ink-900"
                                                    x-text="historyModal.record.temp_approved_by"></strong>
                                            </div>
                                            <template x-if="historyModal.record.temp_approved_note">
                                                <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-blue-900 text-[11px] italic"
                                                    x-text="'“' + historyModal.record.temp_approved_note + '”'"></div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Manager Rejected --}}
                                    <template x-if="historyModal.record.status === 'manager_rejected'">
                                        <div class="mt-1 text-xs">
                                            <div class="text-rose-700 font-semibold flex items-center gap-1">
                                                <span>✕ Rejected at Manager Review</span>
                                            </div>
                                            <div class="text-ink-600 mt-0.5">
                                                Rejected by: <strong class="text-ink-900"
                                                    x-text="historyModal.record.rejected_by || 'Manager'"></strong>
                                            </div>
                                            <template x-if="historyModal.record.rejection_reason">
                                                <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded text-rose-900 text-[11px]"
                                                    x-text="'Reason: ' + historyModal.record.rejection_reason"></div>
                                            </template>
                                            <div class="mt-1.5 text-[11px] text-ink-500 italic">
                                                Note: This claim is visible to HR/Admin who can either confirm rejection or
                                                override with final approval.
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Pending L1 --}}
                                    <template
                                        x-if="!historyModal.record.temp_approved_by && historyModal.record.status === 'pending'">
                                        <div class="mt-1 text-xs text-amber-700 font-medium">
                                            ⏳ Waiting for Level 1 Manager Verification
                                        </div>
                                    </template>

                                    {{-- Direct Final Approval without L1 --}}
                                    <template
                                        x-if="!historyModal.record.temp_approved_by && ['approved', 'rejected'].includes(historyModal.record.status)">
                                        <div class="mt-1 text-xs text-ink-500 italic">
                                            — Direct review processed by Admin/Finance without Level 1 hold.
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Step 3: Level 2 Final Decision (HR / Finance / Admin) --}}
                            <div class="relative group">
                                <template x-if="historyModal.record.status === 'approved'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-emerald-600 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template x-if="historyModal.record.status === 'rejected'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-rose-600 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template x-if="!['approved', 'rejected'].includes(historyModal.record.status)">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-slate-300 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>

                                <div class="bg-surface-muted/60 p-3.5 rounded-lg border border-surface-border">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-ink-900">Step 3: Final Approval (HR / Finance /
                                            Admin)</span>
                                        <span class="text-[11px] text-ink-500"
                                            x-text="historyModal.record.approved_at || (historyModal.record.status === 'rejected' ? historyModal.record.rejected_at : 'Awaiting')"></span>
                                    </div>

                                    {{-- Final Approved --}}
                                    <template x-if="historyModal.record.status === 'approved'">
                                        <div class="mt-1 text-xs">
                                            <div class="text-emerald-700 font-bold flex items-center gap-1">
                                                <span>★ Final Approved & Cleared for Settlement</span>
                                            </div>
                                            <div class="text-ink-600 mt-0.5">
                                                Signed off by: <strong class="text-ink-900"
                                                    x-text="historyModal.record.approved_by || 'HR/Admin'"></strong>
                                            </div>
                                            <template x-if="historyModal.record.final_note">
                                                <div class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded text-emerald-900 text-[11px] italic"
                                                    x-text="'“' + historyModal.record.final_note + '”'"></div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Final Rejected --}}
                                    <template x-if="historyModal.record.status === 'rejected'">
                                        <div class="mt-1 text-xs">
                                            <div class="text-rose-700 font-bold flex items-center gap-1">
                                                <span>✕ Final Rejected</span>
                                            </div>
                                            <div class="text-ink-600 mt-0.5">
                                                Rejected by: <strong class="text-ink-900"
                                                    x-text="historyModal.record.rejected_by || 'HR/Admin'"></strong>
                                            </div>
                                            <template x-if="historyModal.record.rejection_reason">
                                                <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded text-rose-900 text-[11px]"
                                                    x-text="'Reason: ' + historyModal.record.rejection_reason"></div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Ready for Final --}}
                                    <template x-if="historyModal.record.status === 'temp_approved'">
                                        <div class="mt-1 text-xs text-blue-700 font-medium">
                                            ⏳ Ready for Final Review: Awaiting HR/Finance approval and payout clearance.
                                        </div>
                                    </template>

                                    {{-- Pending HR Decision on Manager Rejection --}}
                                    <template x-if="historyModal.record.status === 'manager_rejected'">
                                        <div class="mt-1 text-xs text-rose-700 font-medium">
                                            ⚠️ Manager Rejection Pending Review: HR may uphold the rejection or override
                                            with Final Approval.
                                        </div>
                                    </template>

                                    {{-- Pending Level 1 first --}}
                                    <template x-if="historyModal.record.status === 'pending'">
                                        <div class="mt-1 text-xs text-ink-400 italic">
                                            Awaiting Level 1 verification first.
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>

                    <div class="pt-4 border-t border-surface-border flex justify-end">
                        <button type="button" @click="closeHistoryModal" class="btn-secondary px-4 py-1.5 text-xs">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Expense Modal --}}
        <div x-show="editModal.isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto" x-transition.opacity
            style="display: none;">
            <div @click.away="closeEditModal"
                class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border border-surface-border my-8"
                x-transition.scale>
                <div class="px-6 py-4 border-b border-surface-border bg-surface-muted/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-ink-900 text-base">Edit Expense Claim</h3>
                        <p class="text-xs text-ink-500" x-text="'Worker: ' + editModal.userName"></p>
                    </div>
                    <button type="button" @click="closeEditModal" class="text-ink-400 hover:text-ink-700 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    {{-- Amount & Date Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Amount --}}
                        <div>
                            <label class="form-label font-semibold text-xs text-ink-700">Amount (₹) *</label>
                            <div
                                class="flex items-stretch rounded-lg shadow-sm mt-1 overflow-hidden border border-surface-border focus-within:ring-2 focus-within:ring-brand-500 focus-within:border-brand-500 bg-white">
                                <span
                                    class="inline-flex items-center px-3 bg-surface-muted border-r border-surface-border text-ink-600 font-bold text-sm select-none">
                                    ₹
                                </span>
                                <input type="number" step="0.01" min="0" x-model="editModal.amount" required
                                    class="flex-1 px-3 py-2 border-0 font-bold text-ink-900 focus:outline-none focus:ring-0 text-sm bg-transparent"
                                    placeholder="0.00">
                            </div>
                        </div>

                        {{-- Date --}}
                        <div>
                            <label class="form-label font-semibold text-xs text-ink-700">Expense Date *</label>
                            <input type="date" x-model="editModal.date" required class="form-input mt-1 w-full text-xs">
                        </div>
                    </div>

                    {{-- Category (if not system travel) --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-ink-700">Expense Category</label>
                        <template x-if="editModal.isSystemTravel">
                            <div
                                class="mt-1 px-3 py-2 bg-brand-50 border border-brand-200 text-brand-800 rounded-lg text-xs font-semibold">
                                🚗 System Travel (Automated Distance Claim)
                            </div>
                        </template>
                        <template x-if="!editModal.isSystemTravel">
                            <select x-model="editModal.categoryId" class="form-select mt-1 w-full text-xs">
                                <option value="">-- Select Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </template>
                    </div>

                    {{-- Bill Photo / Image Attachment Section --}}
                    <div class="p-3 bg-surface-muted/60 rounded-lg border border-surface-border space-y-2.5">
                        <label class="form-label font-semibold text-xs text-ink-700 block">Bill Photo / Proof of
                            Expense</label>

                        {{-- Current Photo Preview --}}
                        <div class="flex items-center gap-3">
                            <template x-if="editModal.billPhotoUrl && !editModal.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <a :href="editModal.billPhotoUrl" target="_blank" class="block group relative">
                                        <img :src="editModal.billPhotoUrl" alt="Bill Photo"
                                            class="w-16 h-16 object-cover rounded border border-surface-border group-hover:opacity-90 shadow-sm">
                                    </a>
                                    <div class="text-xs">
                                        <span class="text-ink-600 block font-medium">Current bill proof</span>
                                        <a :href="editModal.billPhotoUrl" target="_blank"
                                            class="text-brand-600 hover:underline text-[11px] font-semibold">View Full Image
                                            ↗</a>
                                    </div>
                                </div>
                            </template>

                            {{-- New Photo Preview (if chosen) --}}
                            <template x-if="editModal.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <img :src="editModal.newPhotoPreview" alt="Replacement Bill Preview"
                                        class="w-16 h-16 object-cover rounded border-2 border-brand-500 shadow-sm">
                                    <div class="text-xs">
                                        <span class="text-brand-700 font-semibold block">New photo selected</span>
                                        <button type="button" @click="removeNewPhoto"
                                            class="text-rose-600 hover:underline text-[11px] font-semibold mt-0.5">Remove &
                                            Keep Original</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!editModal.billPhotoUrl && !editModal.newPhotoPreview">
                                <span class="text-xs text-ink-400 italic">No photo attached yet.</span>
                            </template>
                        </div>

                        {{-- Upload / Replace File Input --}}
                        <div>
                            <input type="file" x-ref="editBillPhotoInput" @change="handleEditPhotoChange($event)"
                                accept="image/*"
                                class="text-xs text-ink-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                            <p class="text-[10px] text-ink-400 mt-1">Select a new image file to replace the bill receipt
                                (PNG, JPG, max 20MB).</p>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-ink-700">Approval Status</label>
                        <select x-model="editModal.status" class="form-select mt-1 w-full text-xs font-semibold">
                            <option value="pending">⏳ Pending Review (Level 1)</option>
                            <option value="temp_approved">✓ Verified (Level 1)</option>
                            <option value="manager_rejected">⚠️ Manager Rejected</option>
                            <option value="approved">★ Final Approved</option>
                            <option value="rejected">✕ Final Rejected</option>
                        </select>
                    </div>

                    {{-- Rejection Reason Field (visible when status is rejected) --}}
                    <div x-show="['manager_rejected', 'rejected'].includes(editModal.status)" x-transition>
                        <label class="form-label font-semibold text-xs text-rose-700 flex items-center gap-1">
                            <span>Why was this rejected? (Reason visible to employee) *</span>
                        </label>
                        <textarea x-model="editModal.rejectionReason" rows="2"
                            class="form-input mt-1 w-full text-xs border-rose-300 focus:ring-rose-400 focus:border-rose-400"
                            placeholder="e.g. Incomplete bill proof, amount mismatch, or duplicate claim..."></textarea>
                    </div>

                    {{-- Settlement / Approval Note --}}
                    <div x-show="['approved', 'temp_approved'].includes(editModal.status)" x-transition>
                        <label class="form-label font-semibold text-xs text-ink-700">Remarks / Approval Note
                            (Optional)</label>
                        <input type="text" x-model="editModal.note" class="form-input mt-1 w-full text-xs"
                            placeholder="e.g. Approved per policy...">
                    </div>

                    {{-- Modal Actions --}}
                    <div class="pt-4 border-t border-surface-border flex items-center justify-end gap-2">
                        <button type="button" @click="closeEditModal" class="btn-secondary px-4 py-2 text-sm"
                            :disabled="editModal.saving">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary px-5 py-2 text-sm font-semibold flex items-center gap-2"
                            :disabled="editModal.saving">
                            <span x-show="!editModal.saving">Save Changes</span>
                            <span x-show="editModal.saving" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.expenseRecordsMap = {};

            // Global bridge functions to trigger Alpine modals from DataTable actions
            window.openExpenseActionModal = function (id, action) {
                const record = window.expenseRecordsMap ? window.expenseRecordsMap[id] : null;
                if (!record) return;
                const container = document.getElementById('expenses-container');
                if (window.Alpine && container) {
                    Alpine.$data(container).openActionModal(record, action);
                }
            };

            window.openExpenseEditModal = function (id) {
                const record = window.expenseRecordsMap ? window.expenseRecordsMap[id] : null;
                if (!record) return;
                const container = document.getElementById('expenses-container');
                if (window.Alpine && container) {
                    Alpine.$data(container).openEditModal(record);
                }
            };

            window.openExpenseHistoryModal = function (id) {
                const record = window.expenseRecordsMap ? window.expenseRecordsMap[id] : null;
                if (!record) return;
                const container = document.getElementById('expenses-container');
                if (window.Alpine && container) {
                    Alpine.$data(container).openHistoryModal(record);
                }
            };

            // Initialize Expenses DataTable
            window.expenseTable = new DataTable('#expenses-table', {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.expenses.data') }}',
                    data: function (d) {
                        const container = document.getElementById('expenses-container');
                        const filters = window.Alpine ? Alpine.$data(container) : null;
                        if (filters) {
                            d.search = filters.search || '';
                            d.user_id = filters.userId || '';
                            d.status = filters.status || '';
                            d.timing = filters.timing || '';
                            d.month = filters.month || '';
                        }
                    }
                },
                columns: [
                    {
                        data: 'date',
                        className: 'whitespace-nowrap',
                        render: function (data) {
                            return `<span class="font-medium text-ink-700 text-xs">${data.formatted}</span>`;
                        }
                    },
                    {
                        data: 'worker',
                        render: function (data) {
                            return `
                                <div class="font-semibold text-ink-900 text-sm">${data.name}</div>
                                <div class="text-caption text-ink-400">${data.role}</div>
                            `;
                        }
                    },
                    {
                        data: 'category',
                        render: function (data) {
                            if (data.is_system_generated) {
                                return `<span class="badge badge-primary">System Travel</span>`;
                            }
                            return `<span class="text-body font-medium">${data.name}</span>`;
                        }
                    },
                    {
                        data: 'amount',
                        className: 'text-right whitespace-nowrap',
                        render: function (data) {
                            return `<span class="font-bold text-ink-900 text-sm">₹${data.formatted}</span>`;
                        }
                    },
                    {
                        data: 'timing',
                        render: function (data) {
                            if (data.is_after_day_end) {
                                return `
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200" title="Submitted after day end or backdated">
                                        ⚠️ After Day End
                                    </span>
                                `;
                            }
                            return `
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200" title="Submitted during active shift">
                                    ✓ During Shift
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'photo',
                        className: 'text-center whitespace-nowrap',
                        render: function (data) {
                            if (data.url) {
                                return `
                                    <a href="${data.url}" target="_blank" class="inline-block relative group" title="Click to view full receipt">
                                        <img src="${data.url}" alt="Bill Proof" class="w-10 h-10 object-cover rounded-lg border border-surface-border mx-auto hover:opacity-85 transition shadow-sm">
                                        <span class="absolute inset-0 bg-black/40 text-white text-[9px] font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 rounded-lg transition">View</span>
                                    </a>
                                `;
                            } else if (data.is_system_generated) {
                                return `<span class="text-xs text-ink-400 italic">Day Meter</span>`;
                            }
                            return `<span class="text-xs text-ink-400 italic">No photo</span>`;
                        }
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            if (data.code === 'pending') {
                                return `
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            ⏳ Pending L1
                                        </span>
                                        <div class="text-[10px] text-ink-400 mt-0.5">Awaiting Manager</div>
                                    </div>
                                `;
                            } else if (data.code === 'temp_approved') {
                                return `
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            ✓ Verified (L1)
                                        </span>
                                        <div class="text-[10px] text-ink-500 mt-0.5 leading-tight">
                                            <div>By: <strong class="text-ink-700">${data.temp_approved_by || 'Manager'}</strong></div>
                                            ${data.temp_approved_at ? `<div class="text-[9px] text-ink-400">${data.temp_approved_at}</div>` : ''}
                                            ${data.temp_approved_note ? `<div class="text-[9px] text-blue-600 italic mt-0.5">“${data.temp_approved_note}”</div>` : ''}
                                        </div>
                                    </div>
                                `;
                            } else if (data.code === 'manager_rejected') {
                                return `
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            ⚠️ Manager Rejected
                                        </span>
                                        <div class="text-[10px] text-rose-600 mt-0.5 leading-tight">
                                            <div>By: <strong>${data.rejected_by || 'Manager'}</strong></div>
                                            ${data.rejected_at ? `<div class="text-[9px] text-ink-400">${data.rejected_at}</div>` : ''}
                                            ${data.rejection_reason ? `<div class="text-[9px] text-rose-700 font-medium mt-0.5">Reason: ${data.rejection_reason}</div>` : ''}
                                        </div>
                                    </div>
                                `;
                            } else if (data.code === 'approved') {
                                return `
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            ★ Final Approved
                                        </span>
                                        <div class="text-[10px] text-ink-500 mt-0.5 leading-tight">
                                            ${data.approved_by ? `<div>Final: <strong class="text-ink-700">${data.approved_by}</strong></div>` : ''}
                                            ${data.temp_approved_by ? `<div class="text-[9px] text-ink-400">L1: ${data.temp_approved_by}</div>` : ''}
                                            ${data.approved_at ? `<div class="text-[9px] text-ink-400">${data.approved_at}</div>` : ''}
                                            ${data.final_note ? `<div class="text-[9px] text-emerald-700 italic mt-0.5">“${data.final_note}”</div>` : ''}
                                        </div>
                                    </div>
                                `;
                            } else if (data.code === 'rejected') {
                                return `
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                            ✕ Final Rejected
                                        </span>
                                        <div class="text-[10px] text-ink-500 mt-0.5 leading-tight">
                                            ${data.rejected_by ? `<div>By: <strong class="text-rose-700">${data.rejected_by}</strong></div>` : ''}
                                            ${data.rejected_at ? `<div class="text-[9px] text-ink-400">${data.rejected_at}</div>` : ''}
                                            ${data.rejection_reason ? `<div class="text-[9px] text-rose-700 font-medium mt-0.5">Reason: ${data.rejection_reason}</div>` : ''}
                                        </div>
                                    </div>
                                `;
                            }
                            return `<span class="badge badge-secondary">${data.code || 'Pending'}</span>`;
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-right whitespace-nowrap w-20',
                        render: function (data) {
                            window.expenseRecordsMap = window.expenseRecordsMap || {};
                            window.expenseRecordsMap[data.id] = data;

                            const hasApprovalActions = data.can_temp_approve || data.can_manager_reject || data.can_final_approve || data.can_final_reject;

                            return `
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" @click.stop class="p-1.5 rounded-lg hover:bg-surface-muted transition-colors text-ink-600 focus:outline-none" title="Actions">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-transition 
                                         class="absolute right-0 mt-1 min-w-[220px] bg-white border border-surface-border rounded-xl shadow-xl z-50 py-1.5 top-full text-left" 
                                         style="min-width: 220px; width: max-content; display: none;">

                                        {{-- Approval History --}}
                                        <button type="button" onclick="window.openExpenseHistoryModal(${data.id})" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-ink-800 hover:bg-surface-muted text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-ink-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Approval History</span>
                                        </button>

                                        {{-- View Day Receipt --}}
                                        ${data.day_report_url ? `
                                        <a href="${data.day_report_url}" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-ink-800 hover:bg-surface-muted text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-ink-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>View Day Receipt</span>
                                        </a>
                                        ` : ''}

                                        {{-- Edit Claim --}}
                                        ${data.can_edit ? `
                                        <button type="button" onclick="window.openExpenseEditModal(${data.id})" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-800 hover:bg-surface-muted text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>Edit Claim</span>
                                        </button>
                                        ` : ''}

                                        ${hasApprovalActions ? `
                                        <div class="my-1.5 border-t border-surface-border"></div>
                                        <div class="px-3.5 py-1 text-[10px] font-bold text-ink-400 uppercase tracking-wider">Approval Actions</div>
                                        ` : ''}

                                        {{-- Level 1 Verify --}}
                                        ${data.can_temp_approve ? `
                                        <button type="button" onclick="window.openExpenseActionModal(${data.id}, 'temp_approve')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-blue-700 hover:bg-blue-50 text-left whitespace-nowrap transition-colors font-medium">
                                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Verify Claim (L1)</span>
                                        </button>
                                        ` : ''}

                                        {{-- Manager Reject --}}
                                        ${data.can_manager_reject ? `
                                        <button type="button" onclick="window.openExpenseActionModal(${data.id}, 'manager_reject')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-700 hover:bg-rose-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Manager Reject</span>
                                        </button>
                                        ` : ''}

                                        {{-- Final Approve --}}
                                        ${data.can_final_approve ? `
                                        <button type="button" onclick="window.openExpenseActionModal(${data.id}, 'final_approve')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-700 hover:bg-emerald-50 text-left whitespace-nowrap transition-colors font-semibold">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>★ Final Approve</span>
                                        </button>
                                        ` : ''}

                                        {{-- Final Reject --}}
                                        ${data.can_final_reject ? `
                                        <button type="button" onclick="window.openExpenseActionModal(${data.id}, 'final_reject')" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-700 hover:bg-rose-50 text-left whitespace-nowrap transition-colors">
                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>✕ Final Reject</span>
                                        </button>
                                        ` : ''}

                                        ${!hasApprovalActions && ['approved', 'rejected'].includes(data.status) ? `
                                        <div class="my-1 border-t border-surface-border"></div>
                                        <div class="px-3.5 py-1 text-xs text-ink-400 italic">Claim is closed</div>
                                        ` : ''}

                                    </div>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 20,
                lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
                dom: 'rt<"flex items-center justify-between px-6 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>',
                drawCallback: function () {
                    if (window.Alpine) {
                        window.Alpine.initTree(document.getElementById('expenses-table'));
                    }
                }
            });
        });
    </script>
@endpush