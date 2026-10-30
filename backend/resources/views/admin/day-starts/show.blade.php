@extends('admin.layouts.app')

@section('title', 'Day Start Details')
@section('breadcrumb', 'Admin / Day Starts / Details')
@section('page_title', 'Day Start Details')

@section('content')
    <div x-data="{
        lightboxOpen: false,
        lightboxImage: '',
        historyModalOpen: false,
        historyRecord: null,
        editModalOpen: false,
        editSaving: false,
        editDayEndOpen: false,
        editDayEndSaving: false,
        editDayStartOpen: false,
        editDayStartSaving: false,
        dayStartData: {
            date: '{{ $dayStart->date ? $dayStart->date->format('Y-m-d') : '' }}',
            dateFormatted: '{{ $dayStart->date ? $dayStart->date->format('d M Y') : '—' }}',
            startTime: '{{ $dayStart->start_time ? \Carbon\Carbon::parse($dayStart->start_time)->format('H:i') : '' }}',
            startTimeFormatted: '{{ $dayStart->start_time ? \Carbon\Carbon::parse($dayStart->start_time)->format('h:i A') : '—' }}',
            tourPurposeId: '{{ $dayStart->tour_purpose_id }}',
            tourPurposeName: {{ json_encode($dayStart->tourPurpose?->name ?? '—') }},
            tourTypeId: '{{ $dayStart->tour_type_id }}',
            tourTypeName: {{ json_encode($dayStart->tourType?->name ?? '—') }},
            vehicleTypeId: '{{ $dayStart->vehicle_type_id }}',
            vehicleTypeName: {{ json_encode($dayStart->vehicleType?->name ?? '—') }},
            vehicleNo: {{ json_encode($dayStart->vehicle_no ?? '') }},
            openingKm: '{{ $dayStart->opening_km !== null ? $dayStart->opening_km : '' }}',
            closingKm: '{{ $dayStart->closing_km !== null ? $dayStart->closing_km : '' }}',
            partyId: '{{ $dayStart->party_id ?? '' }}',
            partyName: {{ json_encode($dayStart->party?->name ?? '—') }},
            visitPlace: {{ json_encode($dayStart->visit_place ?? '') }},
            openingPhotoUrl: '{{ $dayStart->opening_km_photo ? asset('storage/' . $dayStart->opening_km_photo) : '' }}',
            newPhotoPreview: null,
            reason: '',
            errorMsg: '',
            updateUrl: '{{ route('admin.day-starts.updateStart', $dayStart->id) }}',
        },
        openEditDayStart() {
            this.dayStartData.reason = '';
            this.dayStartData.errorMsg = '';
            this.dayStartData.newPhotoPreview = null;
            if (this.$refs.showEditOpeningPhotoInput) {
                this.$refs.showEditOpeningPhotoInput.value = '';
            }
            this.editDayStartOpen = true;
        },
        closeEditDayStart() {
            this.editDayStartOpen = false;
            this.editDayStartSaving = false;
            this.dayStartData.newPhotoPreview = null;
            this.dayStartData.errorMsg = '';
        },
        handleDayStartPhotoChange(event) {
            const file = event.target.files && event.target.files[0];
            if (file) {
                this.dayStartData.newPhotoPreview = URL.createObjectURL(file);
            } else {
                this.dayStartData.newPhotoPreview = null;
            }
        },
        async submitDayStartEdit() {
            if (!this.dayStartData.openingKm || isNaN(this.dayStartData.openingKm) || Number(this.dayStartData.openingKm) < 0) {
                this.dayStartData.errorMsg = 'Please enter a valid positive number for Opening KM.';
                return;
            }
            if (this.dayStartData.closingKm && Number(this.dayStartData.closingKm) < Number(this.dayStartData.openingKm)) {
                this.dayStartData.errorMsg = 'Closing KM cannot be less than Opening KM (' + this.dayStartData.openingKm + ').';
                return;
            }
            if (!this.dayStartData.reason.trim()) {
                this.dayStartData.errorMsg = 'Please provide a reason for the adjustment (Audit requirement).';
                return;
            }
            this.dayStartData.errorMsg = '';
            this.editDayStartSaving = true;
            try {
                const formData = new FormData();
                formData.append('date', this.dayStartData.date);
                if (this.dayStartData.startTime) formData.append('start_time', this.dayStartData.startTime);
                formData.append('tour_purpose_id', this.dayStartData.tourPurposeId);
                formData.append('tour_type_id', this.dayStartData.tourTypeId);
                formData.append('vehicle_type_id', this.dayStartData.vehicleTypeId);
                if (this.dayStartData.vehicleNo) formData.append('vehicle_no', this.dayStartData.vehicleNo);
                formData.append('opening_km', Number(this.dayStartData.openingKm));
                if (this.dayStartData.closingKm) formData.append('closing_km', Number(this.dayStartData.closingKm));
                if (this.dayStartData.partyId) formData.append('party_id', this.dayStartData.partyId);
                if (this.dayStartData.visitPlace) formData.append('visit_place', this.dayStartData.visitPlace);
                formData.append('reason', this.dayStartData.reason);
                if (this.$refs.showEditOpeningPhotoInput && this.$refs.showEditOpeningPhotoInput.files && this.$refs.showEditOpeningPhotoInput.files[0]) {
                    formData.append('opening_km_photo', this.$refs.showEditOpeningPhotoInput.files[0]);
                }

                const res = await fetch(this.dayStartData.updateUrl, {
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
                    window.toast ? window.toast(data.message || 'Day Start details updated successfully.', 'success') : null;
                    this.closeEditDayStart();
                    window.location.reload();
                } else {
                    this.dayStartData.errorMsg = data.message || 'Failed to update Day Start details.';
                }
            } catch (e) {
                this.dayStartData.errorMsg = 'Network error while updating Day Start details.';
            } finally {
                this.editDayStartSaving = false;
            }
        },
        dayEndData: {
            closingKm: '{{ $dayStart->closing_km !== null ? $dayStart->closing_km : '' }}',
            openingKm: {{ (float) ($dayStart->opening_km ?? 0) }},
            endTime: '{{ $dayStart->end_time ? \Carbon\Carbon::parse($dayStart->end_time)->format('H:i') : '' }}',
            endTimeFormatted: '{{ $dayStart->end_time ? \Carbon\Carbon::parse($dayStart->end_time)->format('h:i A') : '—' }}',
            remarks: {{ json_encode($dayStart->end_remarks ?? '') }},
            reason: '',
            closingPhotoUrl: '{{ $dayStart->closing_km_photo ? asset('storage/' . $dayStart->closing_km_photo) : '' }}',
            newPhotoPreview: null,
            isAutoClosed: {{ $dayStart->is_auto_closed ? 'true' : 'false' }},
            flaggedReason: {{ json_encode($dayStart->flagged_reason ?? '') }},
            distanceKm: '{{ ($dayStart->closing_km && $dayStart->opening_km) ? max(0, $dayStart->closing_km - $dayStart->opening_km) : '—' }}',
            updateUrl: '{{ route('admin.day-starts.updateClosing', $dayStart->id) }}',
            errorMsg: ''
        },
        openEditDayEnd() {
            this.dayEndData.reason = '';
            this.dayEndData.errorMsg = '';
            this.dayEndData.newPhotoPreview = null;
            if (this.$refs.showEditClosingPhotoInput) {
                this.$refs.showEditClosingPhotoInput.value = '';
            }
            this.editDayEndOpen = true;
        },
        closeEditDayEnd() {
            this.editDayEndOpen = false;
            this.editDayEndSaving = false;
            this.dayEndData.newPhotoPreview = null;
            this.dayEndData.errorMsg = '';
        },
        handleDayEndPhotoChange(event) {
            const file = event.target.files && event.target.files[0];
            if (file) {
                this.dayEndData.newPhotoPreview = URL.createObjectURL(file);
            } else {
                this.dayEndData.newPhotoPreview = null;
            }
        },
        async submitDayEndEdit() {
            if (!this.dayEndData.closingKm || isNaN(this.dayEndData.closingKm) || Number(this.dayEndData.closingKm) < 0) {
                this.dayEndData.errorMsg = 'Please enter a valid positive number for Closing KM.';
                return;
            }
            if (this.dayEndData.openingKm && Number(this.dayEndData.closingKm) < this.dayEndData.openingKm) {
                this.dayEndData.errorMsg = 'Closing KM cannot be less than Opening KM (' + this.dayEndData.openingKm + ').';
                return;
            }
            if (!this.dayEndData.reason.trim()) {
                this.dayEndData.errorMsg = 'Please provide a reason for the adjustment (Audit requirement).';
                return;
            }
            this.dayEndData.errorMsg = '';
            this.editDayEndSaving = true;
            try {
                const formData = new FormData();
                formData.append('closing_km', Number(this.dayEndData.closingKm));
                if (this.dayEndData.endTime) {
                    formData.append('end_time', this.dayEndData.endTime);
                }
                if (this.dayEndData.remarks) {
                    formData.append('remarks', this.dayEndData.remarks);
                }
                formData.append('reason', this.dayEndData.reason);
                if (this.$refs.showEditClosingPhotoInput && this.$refs.showEditClosingPhotoInput.files && this.$refs.showEditClosingPhotoInput.files[0]) {
                    formData.append('closing_km_photo', this.$refs.showEditClosingPhotoInput.files[0]);
                }

                const res = await fetch(this.dayEndData.updateUrl, {
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
                    this.dayEndData.closingKm = data.record.closing_km;
                    this.dayEndData.endTimeFormatted = data.record.end_time_formatted;
                    this.dayEndData.distanceKm = data.record.distance_km;
                    this.dayEndData.isAutoClosed = false;
                    this.dayEndData.flaggedReason = '';
                    if (data.record.closing_km_photo_url) {
                        this.dayEndData.closingPhotoUrl = data.record.closing_km_photo_url;
                    }
                    if (data.record.end_remarks !== undefined) {
                        this.dayEndData.remarks = data.record.end_remarks;
                    }
                    window.toast ? window.toast(data.message || 'Closing details updated successfully.', 'success') : null;
                    this.closeEditDayEnd();
                    window.location.reload();
                } else {
                    this.dayEndData.errorMsg = data.message || 'Failed to update closing details.';
                }
            } catch (e) {
                this.dayEndData.errorMsg = 'Network error while updating closing details.';
            } finally {
                this.editDayEndSaving = false;
            }
        },
        actionModal: {
            isOpen: false,
            submitting: false,
            expenseId: null,
            action: '',
            title: '',
            promptLabel: '',
            confirmButtonText: '',
            confirmButtonStyle: '',
            note: '',
            isRejection: false,
            rowRef: null,
            summary: ''
        },
        openExpenseAction(rowRef, expenseId, action, summary) {
            let title = 'Confirm Action';
            let promptLabel = 'Notes / Remarks (Optional)';
            let confirmButtonText = 'Confirm';
            let confirmButtonStyle = 'background-color: #16a34a !important; color: #ffffff !important;';
            let isRejection = false;

            if (action === 'temp_approve') {
                title = 'Level 1 Verification (Manager Review)';
                promptLabel = 'Verification Remark (Optional)';
                confirmButtonText = '✓ Verify Claim (L1)';
                confirmButtonStyle = 'background-color: #2563eb !important; color: #ffffff !important;';
            } else if (action === 'final_approve') {
                title = 'Final Approval (HR / Finance)';
                promptLabel = 'Approval Note (Optional)';
                confirmButtonText = '★ Confirm Final Approval';
                confirmButtonStyle = 'background-color: #16a34a !important; color: #ffffff !important;';
            } else if (action === 'manager_reject') {
                title = 'Reject Expense Claim (Level 1)';
                promptLabel = 'Reason for Rejection *';
                confirmButtonText = '✕ Reject Claim';
                confirmButtonStyle = 'background-color: #dc2626 !important; color: #ffffff !important;';
                isRejection = true;
            } else if (action === 'final_reject') {
                title = 'Permanent Rejection (Final Decision)';
                promptLabel = 'Reason for Rejection *';
                confirmButtonText = '✕ Permanently Reject';
                confirmButtonStyle = 'background-color: #b91c1c !important; color: #ffffff !important;';
                isRejection = true;
            }

            this.actionModal = {
                isOpen: true,
                submitting: false,
                expenseId: expenseId,
                action: action,
                title: title,
                promptLabel: promptLabel,
                confirmButtonText: confirmButtonText,
                confirmButtonStyle: confirmButtonStyle,
                note: '',
                isRejection: isRejection,
                rowRef: rowRef,
                summary: summary || ''
            };
        },
        closeExpenseAction() {
            this.actionModal.isOpen = false;
            this.actionModal.submitting = false;
            this.actionModal.note = '';
            this.actionModal.rowRef = null;
        },
        async submitExpenseAction() {
            if (this.actionModal.isRejection && !this.actionModal.note.trim()) {
                window.toast ? window.toast('Please provide a reason for the rejection.', 'warning') : null;
                return;
            }

            this.actionModal.submitting = true;
            const url = '/admin/expenses/' + this.actionModal.expenseId + '/status';

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
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
                    if (this.actionModal.rowRef) {
                        if (data.record) {
                            this.actionModal.rowRef.status = data.record.status;
                            this.actionModal.rowRef.tempApprovedBy = data.record.temp_approved_by || this.actionModal.rowRef.tempApprovedBy;
                            this.actionModal.rowRef.tempApprovedAt = data.record.temp_approved_at || this.actionModal.rowRef.tempApprovedAt;
                            this.actionModal.rowRef.tempApprovedNote = data.record.temp_approved_note || this.actionModal.rowRef.tempApprovedNote;
                            this.actionModal.rowRef.approvedBy = data.record.approved_by || this.actionModal.rowRef.approvedBy;
                            this.actionModal.rowRef.approvedAt = data.record.approved_at || this.actionModal.rowRef.approvedAt;
                            this.actionModal.rowRef.finalNote = data.record.final_note || this.actionModal.rowRef.finalNote;
                            this.actionModal.rowRef.rejectedBy = data.record.rejected_by || this.actionModal.rowRef.rejectedBy;
                            this.actionModal.rowRef.rejectedAt = data.record.rejected_at || this.actionModal.rowRef.rejectedAt;
                            this.actionModal.rowRef.rejectionReason = data.record.rejection_reason || this.actionModal.rowRef.rejectionReason;
                        } else {
                            this.actionModal.rowRef.status = data.status || (this.actionModal.action === 'final_approve' ? 'approved' : (this.actionModal.action === 'temp_approve' ? 'temp_approved' : this.actionModal.action));
                        }
                    }
                    window.toast ? window.toast(data.message || 'Status updated successfully.', 'success') : null;
                    this.closeExpenseAction();
                } else {
                    window.toast ? window.toast(data.message || 'Failed to update status.', 'warning') : null;
                }
            } catch (e) {
                window.toast ? window.toast('Error submitting approval action.', 'error') : null;
            } finally {
                this.actionModal.submitting = false;
            }
        },
        editData: {
            id: null,
            amount: '',
            categoryId: '',
            date: '',
            status: '',
            rejectionReason: '',
            note: '',
            billPhotoUrl: '',
            newPhotoPreview: null,
            updateUrl: '',
            rowRef: null,
        },
        openExpenseHistory(record) {
            this.historyRecord = record;
            this.historyModalOpen = true;
        },
        closeExpenseHistory() {
            this.historyModalOpen = false;
            this.historyRecord = null;
        },
        openEditExpense(data, rowRef) {
            this.editData = {
                id: data.id,
                amount: data.amount,
                categoryId: data.categoryId,
                isSystemTravel: data.isSystemTravel ?? false,
                date: data.date,
                status: data.status,
                rejectionReason: data.rejectionReason,
                note: data.finalNote || data.tempApprovedNote || '',
                billPhotoUrl: data.billPhotoUrl,
                newPhotoPreview: null,
                updateUrl: data.updateUrl,
                rowRef: rowRef,
            };
            if (this.$refs.showEditBillPhotoInput) {
                this.$refs.showEditBillPhotoInput.value = '';
            }
            this.editModalOpen = true;
        },
        closeEditExpense() {
            this.editModalOpen = false;
            this.editSaving = false;
            this.editData.newPhotoPreview = null;
            if (this.$refs.showEditBillPhotoInput) {
                this.$refs.showEditBillPhotoInput.value = '';
            }
        },
        handleShowEditPhotoChange(event) {
            const file = event.target.files && event.target.files[0];
            if (file) {
                this.editData.newPhotoPreview = URL.createObjectURL(file);
            } else {
                this.editData.newPhotoPreview = null;
            }
        },
        removeShowNewPhoto() {
            this.editData.newPhotoPreview = null;
            if (this.$refs.showEditBillPhotoInput) {
                this.$refs.showEditBillPhotoInput.value = '';
            }
        },
        async submitExpenseEdit() {
            if (!this.editData.amount || isNaN(this.editData.amount) || Number(this.editData.amount) < 0) {
                window.toast ? window.toast('Please enter a valid amount.', 'warning') : null;
                return;
            }
            if (['manager_rejected', 'rejected'].includes(this.editData.status) && !this.editData.rejectionReason.trim()) {
                window.toast ? window.toast('Please provide a reason for the rejection.', 'warning') : null;
                return;
            }
            this.editSaving = true;
            try {
                const formData = new FormData();
                formData.append('_method', 'PATCH');
                formData.append('amount', Number(this.editData.amount));
                if (this.editData.categoryId) {
                    formData.append('category_id', this.editData.categoryId);
                }
                if (this.editData.date) {
                    formData.append('date', this.editData.date);
                }
                if (this.editData.status) {
                    formData.append('status', this.editData.status);
                }
                if (this.editData.rejectionReason) {
                    formData.append('rejection_reason', this.editData.rejectionReason);
                }
                if (this.editData.note) {
                    formData.append('final_note', this.editData.note);
                }
                if (this.$refs.showEditBillPhotoInput && this.$refs.showEditBillPhotoInput.files && this.$refs.showEditBillPhotoInput.files[0]) {
                    formData.append('bill_photo', this.$refs.showEditBillPhotoInput.files[0]);
                }

                const res = await fetch(this.editData.updateUrl, {
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
                    if (this.editData.rowRef && data.record) {
                        this.editData.rowRef.amount = data.record.raw_amount;
                        this.editData.rowRef.status = data.record.status;
                        this.editData.rowRef.categoryName = data.record.category_name;
                        this.editData.rowRef.categoryId = data.record.category_id;
                        this.editData.rowRef.date = data.record.raw_date;
                        this.editData.rowRef.billPhotoUrl = data.record.bill_photo_url || '';
                        this.editData.rowRef.tempApprovedBy = data.record.temp_approved_by || '';
                        this.editData.rowRef.tempApprovedAt = data.record.temp_approved_at || '';
                        this.editData.rowRef.tempApprovedNote = data.record.temp_approved_note || '';
                        this.editData.rowRef.approvedBy = data.record.approved_by || '';
                        this.editData.rowRef.approvedAt = data.record.approved_at || '';
                        this.editData.rowRef.finalNote = data.record.final_note || '';
                        this.editData.rowRef.rejectedBy = data.record.rejected_by || '';
                        this.editData.rowRef.rejectedAt = data.record.rejected_at || '';
                        this.editData.rowRef.rejectionReason = data.record.rejection_reason || '';
                    }
                    window.toast ? window.toast(data.message || 'Expense updated successfully.', 'success') : null;
                    this.closeEditExpense();
                } else {
                    window.toast ? window.toast(data.message || 'Failed to update expense.', 'warning') : null;
                }
            } catch (e) {
                window.toast ? window.toast('Error updating expense.', 'error') : null;
            } finally {
                this.editSaving = false;
            }
        }
    }">
        <!-- Lightbox Modal -->
        <div x-show="lightboxOpen"
            style="display: none; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.9); backdrop-filter: blur(4px);"
            x-transition>
            <button type="button" @click="lightboxOpen = false"
                style="position: absolute; top: 2rem; right: 2rem; color: white; background: none; border: none; cursor: pointer; z-index: 10000;">
                <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <img :src="lightboxImage"
                style="max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); margin: auto; display: block;"
                @click.away="lightboxOpen = false">
        </div>

        <section class="panel">
            <div class="panel-header">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-950">
                            {{ $dayStart->user ? $dayStart->user->name . ' (' . $dayStart->user->role_name . ')' : '—' }} —
                            <span x-text="dayStartData.dateFormatted">{{ $dayStart->date->format('d M Y') }}</span></h2>
                        <p class="mt-1 text-sm text-slate-500">Tour plan & Day Start details</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($canEditDay)
                            <button type="button" @click="openEditDayStart"
                                class="btn-secondary text-xs px-2.5 py-1.5 inline-flex items-center gap-1.5 text-slate-700 hover:text-slate-950 font-semibold border border-slate-300 shadow-2xs hover:bg-slate-100 transition-colors">
                                <x-lucide-pencil class="w-3.5 h-3.5 text-indigo-600" />
                                <span>Edit Day Start</span>
                            </button>
                        @endif
                        <a href="{{ route('admin.day-starts.index') }}" class="btn-secondary">Back to List</a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Employee</div>
                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $dayStart->user ? $dayStart->user->name . ' (' . $dayStart->user->role_name . ')' : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Date</div>
                        <div class="mt-1 text-slate-900" x-text="dayStartData.dateFormatted">{{ $dayStart->date->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Start Time</div>
                        <div class="mt-1 font-semibold text-slate-900" x-text="dayStartData.startTimeFormatted || '—'">
                            {{ $dayStart->start_time ? \Carbon\Carbon::parse($dayStart->start_time)->format('h:i A') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Tour Purpose</div>
                        <div class="mt-1 text-slate-900 font-medium" x-text="dayStartData.tourPurposeName">{{ $dayStart->tourPurpose?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Tour Plan / Plan Notes</div>
                        <div class="mt-1 text-slate-900 bg-amber-50/80 border border-amber-200/80 rounded-lg p-2.5 text-sm">
                            @if (!empty($dayStart->remarks))
                                <span class="text-amber-950 font-medium">📋 {{ $dayStart->remarks }}</span>
                            @else
                                <span class="text-slate-400 italic">No tour plan notes provided</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Tour Type</div>
                        <div class="mt-1 text-slate-900" x-text="dayStartData.tourTypeName">{{ $dayStart->tourType?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Vehicle Type</div>
                        <div class="mt-1 text-slate-900" x-text="dayStartData.vehicleTypeName">{{ $dayStart->vehicleType->name }}</div>
                    </div>
                    <div x-data="{
                        status: '{{ $dayStart->status }}',
                        approverName: {{ json_encode($dayStart->approvedBy?->name ?? '') }},
                        approvedAt: '{{ $dayStart->updated_at?->format('d M Y, h:i A') ?? '' }}',
                        loading: false,
                        async updateDayStatus(newStatus) {
                            this.loading = true;
                            try {
                                const res = await fetch('{{ route('admin.day-starts.updateStatus', $dayStart->id) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ status: newStatus })
                                });
                                const data = await res.json();
                                if (res.ok && data.success) {
                                    this.status = newStatus;
                                    if (data.approved_by_name) {
                                        this.approverName = data.approved_by_name;
                                    }
                                    window.toast ? window.toast(data.message || `Day status updated to ${newStatus}.`, 'success') : null;
                                } else {
                                    window.toast ? window.toast(data.message || 'Failed to update status.', 'warning') : null;
                                }
                            } catch (e) {
                                window.toast ? window.toast('Error updating status.', 'error') : null;
                            } finally {
                                this.loading = false;
                            }
                        }
                    }">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Status & Tour Verification
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span class="badge" :class="{
                                'badge-success': status === 'approved',
                                'badge-danger': status === 'rejected' || status === 'flagged',
                                'badge-muted': status !== 'approved' && status !== 'rejected' && status !== 'flagged'
                            }" x-text="status.charAt(0).toUpperCase() + status.slice(1)">
                                {{ ucfirst($dayStart->status) }}
                            </span>

                            <template x-if="status === 'approved' && approverName">
                                <span class="text-xs text-slate-600">
                                    &bull; Approved by <strong class="text-slate-900" x-text="approverName"></strong>
                                    <span class="text-slate-400 text-[11px]" x-show="approvedAt"
                                        x-text="'(' + approvedAt + ')'"></span>
                                </span>
                            </template>

                            <template x-if="status === 'rejected' && approverName">
                                <span class="text-xs text-rose-600">
                                    &bull; Rejected by <strong x-text="approverName"></strong>
                                    <span class="text-slate-400 text-[11px]" x-show="approvedAt"
                                        x-text="'(' + approvedAt + ')'"></span>
                                </span>
                            </template>

                            @can('day-start.approve')
                                <div x-show="status === 'pending'" class="flex gap-1.5 ml-2">
                                    <button type="button" @click="updateDayStatus('approved')" :disabled="loading"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded transition shadow-sm hover:opacity-90 inline-flex items-center gap-1"
                                        style="background-color: #16a34a !important; color: #ffffff !important;">
                                        ✓ Approve
                                    </button>
                                    <button type="button" @click="updateDayStatus('rejected')" :disabled="loading"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded transition shadow-sm hover:opacity-90 inline-flex items-center gap-1"
                                        style="background-color: #dc2626 !important; color: #ffffff !important;">
                                        ✕ Reject
                                    </button>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Visit Place</div>
                        <div class="mt-1 text-slate-900" x-text="dayStartData.visitPlace || '—'">{{ $dayStart->visit_place ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Party</div>
                        <div class="mt-1 text-slate-900" x-text="dayStartData.partyName || '—'">{{ $dayStart->party?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Vehicle No.</div>
                        <div class="mt-1 text-slate-900" x-text="dayStartData.vehicleNo || '—'">{{ $dayStart->vehicle_no ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening KM</div>
                        <div class="mt-1 font-bold text-slate-900" x-text="dayStartData.openingKm ? dayStartData.openingKm + ' KM' : '—'">{{ $dayStart->opening_km ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Location</div>
                        <div class="mt-1 text-slate-900">
                            @if ($dayStart->start_lat && $dayStart->start_lng)
                                {{ $dayStart->start_lat }}, {{ $dayStart->start_lng }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening KM Photo</div>
                        <template x-if="dayStartData.openingPhotoUrl">
                            <button type="button"
                                @click="lightboxImage = dayStartData.openingPhotoUrl; lightboxOpen = true;"
                                class="mt-2 block">
                                <img :src="dayStartData.openingPhotoUrl" alt="Opening KM"
                                    style="width: 192px; height: 128px; object-fit: cover;"
                                    class="rounded-lg border border-slate-200 shadow-sm hover:opacity-90 transition">
                            </button>
                        </template>
                        <template x-if="!dayStartData.openingPhotoUrl">
                            <div class="mt-2 h-32 w-48 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200 border-dashed">
                                <span class="text-xs text-slate-400">No Photo</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            @if ($dayStart->end_time)
                <div class="border-t border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-slate-950">Day End Details</h3>
                            <template x-if="dayEndData.isAutoClosed">
                                <span class="badge badge-warning inline-flex items-center gap-1 text-[11px]" title="Field worker forgot to click Day End">
                                    ⚠️ System Auto-Closed (Missing Day End)
                                </span>
                            </template>
                        </div>
                        @if ($canEditClosing)
                            <button type="button" @click="openEditDayEnd"
                                class="btn-secondary text-xs px-2.5 py-1 inline-flex items-center gap-1.5 text-slate-700 hover:text-slate-950 font-semibold border border-slate-300 shadow-2xs hover:bg-slate-100 transition-colors">
                                <x-lucide-pencil class="w-3.5 h-3.5 text-indigo-600" />
                                <span>Edit Day End / Closing KM</span>
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Ended At</div>
                                <div class="mt-1 text-slate-900" x-text="dayEndData.endTimeFormatted">
                                    {{ \Carbon\Carbon::parse($dayStart->end_time)->format('h:i A') }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing KM</div>
                                <div class="mt-1 text-slate-900" x-text="dayEndData.closingKm || '—'">{{ $dayStart->closing_km ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Distance Travelled</div>
                                <div class="mt-1 font-semibold text-brand-600" x-text="dayEndData.distanceKm !== '—' ? dayEndData.distanceKm + ' KM' : '—'">
                                    @if($dayStart->closing_km && $dayStart->opening_km)
                                        {{ max(0, $dayStart->closing_km - $dayStart->opening_km) }} KM
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">End Location</div>
                                <div class="mt-1 text-slate-900">
                                    @if ($dayStart->end_lat && $dayStart->end_lng)
                                        {{ $dayStart->end_lat }}, {{ $dayStart->end_lng }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing Remarks / Day Summary</div>
                                <div class="mt-1 text-slate-900" x-text="dayEndData.remarks || '—'">{{ $dayStart->end_remarks ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing KM Photo</div>
                                <template x-if="dayEndData.closingPhotoUrl">
                                    <button type="button"
                                        @click="lightboxImage = dayEndData.closingPhotoUrl; lightboxOpen = true;"
                                        class="mt-2 block">
                                        <img :src="dayEndData.closingPhotoUrl" alt="Closing KM"
                                            style="width: 192px; height: 128px; object-fit: cover;"
                                            class="rounded-lg border border-slate-200 shadow-sm hover:opacity-90 transition">
                                    </button>
                                </template>
                                <template x-if="!dayEndData.closingPhotoUrl">
                                    <div class="mt-2 h-32 w-48 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200 border-dashed">
                                        <span class="text-xs text-slate-400">No Photo</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="border-t border-slate-200 p-5 bg-slate-50 rounded-b-lg flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">This day has not been ended yet by the field worker.</p>
                        <p class="text-xs text-slate-500 mt-0.5">As an authorized admin or manager, you can manually enter closing KM and record day end.</p>
                    </div>
                    @if ($canEditClosing)
                        <button type="button" @click="openEditDayEnd"
                            class="btn-primary text-xs px-3 py-1.5 inline-flex items-center gap-1.5 shadow-sm"
                            style="background-color: #4f46e5; color: #ffffff;">
                            <x-lucide-pencil class="w-3.5 h-3.5" />
                            <span>Enter Closing KM & End Day</span>
                        </button>
                    @endif
                </div>
            @endif
        </section>

        {{-- Visits Timeline --}}
        <section class="panel mt-6" id="visits">
            <div class="panel-header border-b border-slate-200 p-5">
                <h3 class="font-semibold text-slate-950">Visits Timeline</h3>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Party Visited</th>
                            <th>Status</th>
                            <th>Consultation Notes / Purpose</th>
                            <th class="text-right">Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visits as $visit)
                            <tr>
                                <td>
                                    <div class="font-medium text-slate-900">
                                        {{ $visit->check_in_time ? \Carbon\Carbon::parse($visit->check_in_time)->format('h:i A') : '—' }}
                                    </div>
                                    @if($visit->check_out_time)
                                        <div class="text-xs text-slate-500">
                                            to {{ \Carbon\Carbon::parse($visit->check_out_time)->format('h:i A') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-900">{{ $visit->party->name ?? 'Unknown' }}</div>
                                    @if($visit->party?->city || $visit->party?->mobile)
                                        <div class="text-xs text-slate-500">
                                            {{ $visit->party->city ?? '' }}
                                            {{ $visit->party->mobile ? '• ' . $visit->party->mobile : '' }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($visit->status === 'completed')
                                        <span class="badge badge-success">Completed</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($visit->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-sm max-w-md">
                                    @if($visit->purpose && $visit->notes)
                                        <div class="font-semibold text-xs text-slate-500 uppercase tracking-wide mb-1">
                                            {{ $visit->purpose }}</div>
                                        <div class="text-sm text-slate-900 bg-slate-50 p-2 rounded border border-slate-200">
                                            {{ $visit->notes }}</div>
                                    @elseif($visit->notes)
                                        <div class="text-sm text-slate-900 bg-slate-50 p-2 rounded border border-slate-200">
                                            {{ $visit->notes }}</div>
                                    @elseif($visit->purpose)
                                        <span class="text-slate-800 font-medium">{{ $visit->purpose }}</span>
                                    @else
                                        <span class="text-slate-400 italic">No notes recorded</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($visit->check_in_photo_url)
                                        <button type="button"
                                            @click="lightboxImage = '{{ $visit->check_in_photo_url }}'; lightboxOpen = true;"
                                            class="inline-block relative group cursor-pointer focus:outline-none"
                                            title="View Check-In Photo">
                                            <img src="{{ $visit->check_in_photo_url }}" alt="Check In Photo"
                                                class="w-10 h-10 object-cover rounded-lg border border-slate-200 hover:scale-105 transition shadow-sm inline-block">
                                        </button>
                                    @else
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-slate-500">No visits recorded today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Orders Placed --}}
        <section class="panel mt-6" id="orders">
            <div class="panel-header border-b border-slate-200 p-5">
                <h3 class="font-semibold text-slate-950">Orders Placed</h3>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Party</th>
                            <th>Source</th>
                            <th>Notes / Remarks</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-right">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>{{ $order->created_at->format('h:i A') }}</td>
                                <td class="font-semibold">{{ $order->party->name ?? 'Unknown' }}</td>
                                <td>
                                    @if(($order->order_source ?? '') === 'phone')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            📞 Phone
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            📍 Visit
                                        </span>
                                    @endif
                                </td>
                                <td class="max-w-[260px] text-xs text-slate-600 truncate" title="{{ $order->remarks }}">
                                    {{ $order->remarks ?? '—' }}
                                </td>
                                <td class="font-bold text-brand-600">₹{{ number_format($order->total_amount, 2) }}</td>
                                <td>
                                    <x-status-badge :status="$order->status" :map="['approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger']" />
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                        class="btn-secondary px-2 py-1 text-xs" target="_blank">Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-slate-500">No orders placed today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Expenses Claimed --}}
        <section class="panel mt-6" id="expenses">
            <div class="panel-header border-b border-slate-200 p-5">
                <h3 class="font-semibold text-slate-950">Expenses Claimed</h3>
            </div>

            <div class="table-wrap" x-data="{ expandedExpense: null }">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Timing</th>
                            <th>Photo</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                            <tr x-data="{
                                    status: '{{ $expense->status }}',
                                    amount: '{{ (float) $expense->amount }}',
                                    categoryId: '{{ $expense->category_id }}',
                                    categoryName: {{ json_encode($expense->is_system_generated ? 'System Travel' : ($expense->category?->name ?? 'N/A')) }},
                                    date: '{{ $expense->date?->format('Y-m-d') ?? '' }}',
                                    billPhotoUrl: '{{ $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : '' }}',
                                    loading: false,
                                    tempApprovedBy: {{ json_encode($expense->tempApprovedBy?->name ?? '') }},
                                    tempApprovedAt: '{{ $expense->temp_approved_at ? $expense->temp_approved_at->format('d M Y, h:i A') : '' }}',
                                    tempApprovedNote: {{ json_encode($expense->temp_approved_note ?? '') }},
                                    approvedBy: {{ json_encode($expense->approvedBy?->name ?? '') }},
                                    approvedAt: '{{ $expense->approved_at ? $expense->approved_at->format('d M Y, h:i A') : '' }}',
                                    finalNote: {{ json_encode($expense->final_note ?? '') }},
                                    rejectedBy: {{ json_encode($expense->rejectedBy?->name ?? '') }},
                                    rejectedAt: '{{ $expense->rejected_at ? $expense->rejected_at->format('d M Y, h:i A') : '' }}',
                                    rejectionReason: {{ json_encode($expense->rejection_reason ?? '') }},
                                    updateExpenseStatus(actionType, confirmText) {
                                        openExpenseAction(this, {{ $expense->id }}, actionType, {{ json_encode('Claim #' . $expense->id . ' • ₹' . number_format((float)$expense->amount, 2) . ' • ' . ($expense->is_system_generated ? 'System Travel' : ($expense->category?->name ?? 'Expense'))) }});
                                    }
                                }">
                                <td>
                                    @if($expense->is_system_generated)
                                        <span class="badge badge-primary">System Travel</span>
                                    @else
                                        <div class="flex items-center gap-1.5">
                                            <span x-text="categoryName">{{ $expense->category->name ?? 'N/A' }}</span>
                                            @if($expense->items->count() > 0)
                                                <button
                                                    @click="expandedExpense = (expandedExpense === {{ $expense->id }} ? null : {{ $expense->id }})"
                                                    type="button"
                                                    class="text-brand-600 hover:text-brand-700 flex items-center gap-0.5 text-xs font-semibold bg-brand-50 px-1.5 py-0.5 rounded border border-brand-100 transition-colors">
                                                    <span>{{ $expense->items->count() }}
                                                        {{ $expense->items->count() === 1 ? 'item' : 'items' }}</span>
                                                    <x-lucide-chevron-down class="w-3.5 h-3.5 transition-transform duration-200"
                                                        ::class="expandedExpense === {{ $expense->id }} ? 'rotate-180' : ''" />
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="font-bold text-slate-900" x-text="'₹' + Number(amount).toFixed(2)">
                                    ₹{{ number_format($expense->amount, 2) }}</td>
                                <td>
                                    @if($expense->is_after_day_end)
                                        <span class="badge badge-warning inline-flex items-center gap-1"
                                            title="Submitted after day end or backdated">
                                            ⚠️ After Day End
                                        </span>
                                    @else
                                        <span class="badge badge-success inline-flex items-center gap-1"
                                            title="Submitted during active shift">
                                            ✓ During Shift
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($expense->is_system_generated)
                                        <button
                                            @click="expandedExpense = (expandedExpense === {{ $expense->id }} ? null : {{ $expense->id }})"
                                            type="button" class="btn-secondary px-2 py-1 text-xs">
                                            View Meter
                                        </button>
                                    @else
                                        <template x-if="billPhotoUrl">
                                            <button type="button" @click="lightboxImage = billPhotoUrl; lightboxOpen = true;"
                                                class="text-brand-600 underline text-sm font-semibold hover:text-brand-700">View
                                                Proof</button>
                                        </template>
                                        <template x-if="!billPhotoUrl">
                                            <span class="text-slate-400 text-sm">No Photo</span>
                                        </template>
                                    @endif
                                </td>
                                <td>
                                    {{-- Pending L1 --}}
                                    <template x-if="status === 'pending'">
                                        <div>
                                            <span class="badge badge-warning">
                                                ⏳ Pending L1
                                            </span>
                                            <div class="text-[10px] text-slate-400 mt-0.5">Awaiting Manager</div>
                                        </div>
                                    </template>

                                    {{-- Verified (L1) --}}
                                    <template x-if="status === 'temp_approved'">
                                        <div>
                                            <span class="badge badge-primary">
                                                ✓ Verified (L1)
                                            </span>
                                            <div class="text-[11px] text-slate-600 mt-1 leading-tight">
                                                <div>By: <strong class="text-slate-800"
                                                        x-text="tempApprovedBy || 'Manager'"></strong></div>
                                                <div class="text-[10px] text-slate-400" x-show="tempApprovedAt"
                                                    x-text="tempApprovedAt"></div>
                                                <div class="text-[10px] text-blue-600 italic mt-0.5" x-show="tempApprovedNote"
                                                    x-text="'“' + tempApprovedNote + '”'"></div>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Manager Rejected --}}
                                    <template x-if="status === 'manager_rejected'">
                                        <div>
                                            <span class="badge badge-danger">
                                                ⚠️ Manager Rejected
                                            </span>
                                            <div class="text-[11px] text-rose-600 mt-1 leading-tight">
                                                <div>By: <strong x-text="rejectedBy || 'Manager'"></strong></div>
                                                <div class="text-[10px] text-slate-400" x-show="rejectedAt" x-text="rejectedAt">
                                                </div>
                                                <div class="text-[10px] text-rose-700 font-medium mt-0.5"
                                                    x-show="rejectionReason" x-text="'Reason: ' + rejectionReason"></div>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Final Approved --}}
                                    <template x-if="status === 'approved'">
                                        <div>
                                            <span class="badge badge-success">
                                                ★ Final Approved
                                            </span>
                                            <div class="text-[11px] text-slate-600 mt-1 leading-tight">
                                                <div x-show="approvedBy">Final: <strong class="text-slate-800"
                                                        x-text="approvedBy"></strong></div>
                                                <div class="text-[10px] text-slate-500" x-show="tempApprovedBy"
                                                    x-text="'L1 Verified: ' + tempApprovedBy"></div>
                                                <div class="text-[10px] text-slate-400" x-show="approvedAt" x-text="approvedAt">
                                                </div>
                                                <div class="text-[10px] text-emerald-700 italic mt-0.5" x-show="finalNote"
                                                    x-text="'“' + finalNote + '”'"></div>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Final Rejected --}}
                                    <template x-if="status === 'rejected'">
                                        <div>
                                            <span class="badge badge-danger">
                                                ✕ Final Rejected
                                            </span>
                                            <div class="text-[11px] text-rose-600 mt-1 leading-tight">
                                                <div x-show="rejectedBy">By: <strong class="text-rose-800"
                                                        x-text="rejectedBy"></strong></div>
                                                <div class="text-[10px] text-slate-400" x-show="rejectedAt" x-text="rejectedAt">
                                                </div>
                                                <div class="text-[10px] text-rose-700 font-medium mt-0.5"
                                                    x-show="rejectionReason" x-text="'Reason: ' + rejectionReason"></div>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                        {{-- History / Audit Log Button --}}
                                        <button type="button" @click="openExpenseHistory({
                                                id: {{ $expense->id }},
                                                userName: {{ json_encode($expense->user?->name ?? 'Worker') }},
                                                amount: '{{ number_format((float) $expense->amount, 2) }}',
                                                date: '{{ $expense->date?->format('d M Y') ?? '—' }}',
                                                categoryName: {{ json_encode($expense->is_system_generated ? 'System Travel' : ($expense->category?->name ?? 'N/A')) }},
                                                isAfterDayEnd: {{ $expense->is_after_day_end ? 'true' : 'false' }},
                                                billPhotoUrl: '{{ $expense->bill_photo ? asset('storage/' . $expense->bill_photo) : '' }}',
                                                status: status,
                                                tempApprovedBy: tempApprovedBy,
                                                tempApprovedAt: tempApprovedAt,
                                                tempApprovedNote: tempApprovedNote,
                                                approvedBy: approvedBy,
                                                approvedAt: approvedAt,
                                                finalNote: finalNote,
                                                rejectedBy: rejectedBy,
                                                rejectedAt: rejectedAt,
                                                rejectionReason: rejectionReason
                                            })"
                                            class="btn-secondary text-xs px-2 py-1 inline-flex items-center gap-1 text-slate-600 hover:text-slate-900"
                                            title="View Full Verification & Approval Log">
                                            <x-lucide-history class="w-3.5 h-3.5 text-slate-500" />
                                            Log
                                        </button>

                                        {{-- Edit Claim Button --}}
                                        @if($canEditExpense)
                                            <button type="button" @click="openEditExpense({
                                                        id: {{ $expense->id }},
                                                        amount: amount,
                                                        categoryId: categoryId,
                                                        date: date,
                                                        status: status,
                                                        isSystemTravel: {{ $expense->is_system_generated ? 'true' : 'false' }},
                                                        rejectionReason: rejectionReason,
                                                        finalNote: finalNote,
                                                        tempApprovedNote: tempApprovedNote,
                                                        billPhotoUrl: billPhotoUrl,
                                                        updateUrl: '{{ route('admin.expenses.update', $expense->id) }}'
                                                    }, $data)"
                                                class="btn-secondary text-xs px-2 py-1 inline-flex items-center gap-1 text-slate-700 hover:text-slate-900 border border-slate-200"
                                                title="Edit Claim">
                                                <x-lucide-pencil class="w-3.5 h-3.5 text-slate-600" />
                                                Edit
                                            </button>
                                        @endif

                                        {{-- Manager Level 1 (Verify & Reject on pending) --}}
                                        @if($canTempApprove && !$canFinalApprove)
                                            <div x-show="status === 'pending'" class="flex gap-1.5">
                                                <button type="button"
                                                    @click="updateExpenseStatus('temp_approve', 'Verify this expense (Level 1)?')"
                                                    :disabled="loading"
                                                    class="px-2.5 py-1 text-xs font-bold rounded shadow-sm transition hover:opacity-90 inline-flex items-center gap-1"
                                                    style="background-color: #2563eb !important; color: #ffffff !important;">
                                                    ✓ Verify
                                                </button>
                                                <button type="button"
                                                    @click="updateExpenseStatus('manager_reject', 'Reject this expense?')"
                                                    :disabled="loading"
                                                    class="px-2.5 py-1 text-xs font-bold rounded shadow-sm transition hover:opacity-90 inline-flex items-center gap-1"
                                                    style="background-color: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #fca5a5 !important;">
                                                    ✕ Reject
                                                </button>
                                            </div>
                                            <div x-show="status !== 'pending'">
                                                <span class="text-xs text-slate-500 font-medium">Processed</span>
                                            </div>
                                        @endif

                                        @php
                                            $hasReportingManager = !empty($dayStart->user?->reporting_to);
                                            $isSuperAdminUser = auth()->user()?->hasRole(['Super Admin', 'super_admin']);
                                        @endphp

                                        {{-- Admin / HR Level 2 (Final Approve) --}}
                                        @if($canFinalApprove)
                                            {{-- When pending: requires Level 1 verification first unless Super Admin or unassigned --}}
                                            <div x-show="status === 'pending'" class="flex gap-1.5 items-center">
                                                @if($canTempApprove)
                                                    <button type="button"
                                                        @click="updateExpenseStatus('temp_approve', 'Verify this expense (Level 1)?')"
                                                        :disabled="loading"
                                                        class="px-2.5 py-1 text-xs font-bold rounded shadow-sm transition hover:opacity-90 inline-flex items-center gap-1"
                                                        style="background-color: #2563eb !important; color: #ffffff !important;">
                                                        ✓ Verify (L1)
                                                    </button>
                                                @endif
                                                @if($isSuperAdminUser || !$hasReportingManager)
                                                    <button type="button"
                                                        @click="updateExpenseStatus('final_approve', 'Final approve this expense?')"
                                                        :disabled="loading"
                                                        class="px-2.5 py-1 text-xs font-bold rounded shadow-sm transition hover:opacity-90 inline-flex items-center gap-1"
                                                        style="background-color: #16a34a !important; color: #ffffff !important;">
                                                        ★ Final Approve
                                                    </button>
                                                @else
                                                    @if(!$canTempApprove)
                                                        <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-medium">
                                                            ⏳ Awaiting Manager L1
                                                        </span>
                                                    @endif
                                                @endif
                                                <button type="button"
                                                    @click="updateExpenseStatus('final_reject', 'Permanently reject this expense?')"
                                                    :disabled="loading"
                                                    class="px-2.5 py-1 text-xs font-bold rounded shadow-sm transition hover:opacity-90 inline-flex items-center gap-1"
                                                    style="background-color: #dc2626 !important; color: #ffffff !important;">
                                                    ✕ Reject
                                                </button>
                                            </div>

                                            {{-- When temp_approved or manager_rejected: Ready for Final Decision --}}
                                            <div x-show="['temp_approved', 'manager_rejected'].includes(status)" class="flex gap-1.5 items-center">
                                                <button type="button"
                                                    @click="updateExpenseStatus('final_approve', 'Final approve this expense?')"
                                                    :disabled="loading"
                                                    class="px-2.5 py-1 text-xs font-bold rounded shadow-sm transition hover:opacity-90 inline-flex items-center gap-1"
                                                    style="background-color: #16a34a !important; color: #ffffff !important;">
                                                    ★ Final Approve
                                                </button>
                                                <button type="button"
                                                    @click="updateExpenseStatus('final_reject', 'Permanently reject this expense?')"
                                                    :disabled="loading"
                                                    class="px-2.5 py-1 text-xs font-bold rounded shadow-sm transition hover:opacity-90 inline-flex items-center gap-1"
                                                    style="background-color: #dc2626 !important; color: #ffffff !important;">
                                                    ✕ Reject
                                                </button>
                                            </div>

                                            <div x-show="['approved', 'rejected'].includes(status)">
                                                <span class="text-xs text-slate-500 font-medium">Processed</span>
                                            </div>
                                        @endif

                                        {{-- Fallback if neither --}}
                                        @if(!$canTempApprove && !$canFinalApprove)
                                            <span class="text-xs text-slate-500 font-medium">View Only</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if($expense->is_system_generated)
                                {{-- Expandable Meter Summary Row --}}
                                <tr x-show="expandedExpense === {{ $expense->id }}" style="display: none;">
                                    <td colspan="6" class="bg-slate-50 p-6 border-b border-slate-200 shadow-inner">
                                        <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-4">Meter &
                                            Travel Summary</h4>
                                        <div class="flex flex-wrap gap-8 items-start">

                                            {{-- Opening --}}
                                            <div class="flex items-center gap-4">
                                                @if ($dayStart->opening_km_photo)
                                                    <button type="button"
                                                        @click="lightboxImage = '{{ asset('storage/' . $dayStart->opening_km_photo) }}'; lightboxOpen = true;"
                                                        class="shrink-0 block">
                                                        <img src="{{ asset('storage/' . $dayStart->opening_km_photo) }}"
                                                            alt="Opening KM" style="width: 128px; height: 80px; object-fit: cover;"
                                                            class="rounded border border-slate-300 shadow-sm hover:opacity-90 transition">
                                                    </button>
                                                @else
                                                    <div
                                                        class="h-20 w-32 rounded bg-slate-200 flex items-center justify-center border border-slate-300 border-dashed shrink-0">
                                                        <span class="text-xs font-semibold text-slate-400">No Photo</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <p class="text-[10px] uppercase font-bold text-slate-500">Start KM</p>
                                                    <p class="font-black text-slate-900 text-xl mt-0.5">
                                                        {{ $dayStart->opening_km ?? '—' }}</p>
                                                </div>
                                            </div>

                                            {{-- Closing --}}
                                            <div class="flex items-center gap-4">
                                                @if ($dayStart->closing_km_photo)
                                                    <button type="button"
                                                        @click="lightboxImage = '{{ asset('storage/' . $dayStart->closing_km_photo) }}'; lightboxOpen = true;"
                                                        class="shrink-0 block">
                                                        <img src="{{ asset('storage/' . $dayStart->closing_km_photo) }}"
                                                            alt="Closing KM" style="width: 128px; height: 80px; object-fit: cover;"
                                                            class="rounded border border-slate-300 shadow-sm hover:opacity-90 transition">
                                                    </button>
                                                @else
                                                    <div
                                                        class="h-20 w-32 rounded bg-slate-200 flex items-center justify-center border border-slate-300 border-dashed shrink-0">
                                                        <span class="text-xs font-semibold text-slate-400">No Photo</span>
                                                    </div>
                                                @endif
                                                <div>
                                                    <p class="text-[10px] uppercase font-bold text-slate-500">End KM</p>
                                                    <p class="font-black text-slate-900 text-xl mt-0.5">
                                                        {{ $dayStart->closing_km ?? '—' }}</p>
                                                </div>
                                            </div>

                                            {{-- Distance --}}
                                            <div
                                                class="flex items-center gap-3 ml-auto bg-brand-50 border border-brand-200 shadow-sm rounded-xl px-5 py-3">
                                                <div>
                                                    <p class="text-[10px] uppercase font-bold text-brand-600">Total Distance</p>
                                                    <p class="font-black text-brand-600 text-2xl mt-0.5">
                                                        @if($dayStart->closing_km && $dayStart->opening_km)
                                                            {{ max(0, $dayStart->closing_km - $dayStart->opening_km) }} KM
                                                        @else
                                                            —
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @if(!$expense->is_system_generated && $expense->items->count() > 0)
                                {{-- Expandable Itemized Row --}}
                                <tr x-show="expandedExpense === {{ $expense->id }}" style="display: none;">
                                    <td colspan="6" class="bg-slate-50 p-6 border-b border-slate-200 shadow-inner">
                                        <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-4">Itemized
                                            Breakdown</h4>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left text-slate-600">
                                                <thead>
                                                    <tr
                                                        class="border-b border-slate-200 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                                                        <th class="pb-2">Item Name</th>
                                                        <th class="pb-2 text-center">Qty</th>
                                                        <th class="pb-2 text-right">Unit Price</th>
                                                        <th class="pb-2 text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($expense->items as $item)
                                                        <tr class="border-b border-slate-100 last:border-0">
                                                            <td class="py-2.5 font-medium text-slate-900">{{ $item->item_name }}</td>
                                                            <td class="py-2.5 text-center">{{ $item->quantity }}</td>
                                                            <td class="py-2.5 text-right">₹{{ number_format($item->unit_price, 2) }}
                                                            </td>
                                                            <td class="py-2.5 text-right font-semibold text-slate-900">
                                                                ₹{{ number_format($item->total_price, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-slate-500">No expenses claimed today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($expenses->count() > 0)
                        @php
                            $systemTravelTotal = (float) $expenses->where('is_system_generated', true)->sum('amount');
                            $otherExpensesTotal = (float) $expenses->where('is_system_generated', false)->sum('amount');
                            $grandTotalExpenses = (float) $expenses->sum('amount');
                        @endphp
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200">
                                <td class="font-semibold text-slate-700 text-right">System Travel Expense:</td>
                                <td class="font-bold text-slate-900 text-base">₹{{ number_format($systemTravelTotal, 2) }}</td>
                                <td colspan="4" class="text-xs text-slate-500">Auto-calculated distance travel</td>
                            </tr>
                            <tr class="bg-slate-50 border-t border-slate-100">
                                <td class="font-semibold text-slate-700 text-right">Other Expenses:</td>
                                <td class="font-bold text-slate-900 text-base">₹{{ number_format($otherExpensesTotal, 2) }}</td>
                                <td colspan="4" class="text-xs text-slate-500">Manual expense claims (stationery, food, hotel, etc.)</td>
                            </tr>
                            <tr class="bg-slate-100 border-t-2 border-slate-300">
                                <td class="font-bold text-slate-900 text-right text-base">Total Bill Amount:</td>
                                <td class="font-bold text-brand-600 text-lg">₹{{ number_format($grandTotalExpenses, 2) }}</td>
                                <td colspan="4" class="text-xs font-semibold text-slate-700">Combined total expense</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>

        {{-- Expense Approval History & Audit Trail Modal --}}
        <div x-show="historyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-transition.opacity style="display: none;">
            <div @click.away="closeExpenseHistory"
                class="bg-white rounded-xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-200"
                x-transition.scale>
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                            <x-lucide-history class="w-4 h-4 text-brand-600" />
                            Verification & Approval Log
                        </h3>
                        <p class="text-xs text-slate-500"
                            x-text="historyRecord ? ('Claim #' + historyRecord.id + ' • ' + historyRecord.userName + ' • ₹' + historyRecord.amount) : ''">
                        </p>
                    </div>
                    <button type="button" @click="closeExpenseHistory"
                        class="text-slate-400 hover:text-slate-700 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <template x-if="historyRecord">
                        <div
                            class="relative pl-6 space-y-8 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">

                            {{-- Step 1: Submission --}}
                            <div class="relative group">
                                <div
                                    class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-emerald-500 ring-4 ring-white flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </div>
                                <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-900">Step 1: Submitted by Worker</span>
                                        <span class="text-[11px] text-slate-500 font-medium"
                                            x-text="historyRecord.date"></span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-700">
                                        <span class="font-medium" x-text="historyRecord.userName"></span> submitted claim
                                        for <strong class="text-slate-900" x-text="'₹' + historyRecord.amount"></strong>
                                        (<span x-text="historyRecord.categoryName"></span>).
                                    </div>
                                    <div class="mt-2 flex items-center gap-2 text-[11px]">
                                        <template x-if="historyRecord.isAfterDayEnd">
                                            <span
                                                class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">⚠️
                                                Submitted after day end</span>
                                        </template>
                                        <template x-if="!historyRecord.isAfterDayEnd">
                                            <span
                                                class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">✓
                                                Submitted during shift</span>
                                        </template>
                                        <template x-if="historyRecord.billPhotoUrl">
                                            <a :href="historyRecord.billPhotoUrl" target="_blank"
                                                class="text-brand-600 hover:underline font-medium inline-flex items-center gap-1">
                                                <span>📷 View Attached Bill Proof</span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Step 2: Level 1 Review (Manager / Lead) --}}
                            <div class="relative group">
                                <template x-if="historyRecord.tempApprovedBy">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-blue-600 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template x-if="historyRecord.status === 'manager_rejected'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-rose-600 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template
                                    x-if="!historyRecord.tempApprovedBy && historyRecord.status !== 'manager_rejected'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-slate-300 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>

                                <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-900">Step 2: Level 1 Review (Manager /
                                            Lead)</span>
                                        <span class="text-[11px] text-slate-500"
                                            x-text="historyRecord.tempApprovedAt || (historyRecord.status === 'manager_rejected' ? historyRecord.rejectedAt : 'Pending')"></span>
                                    </div>

                                    {{-- Temp Approved --}}
                                    <template x-if="historyRecord.tempApprovedBy">
                                        <div class="mt-1 text-xs">
                                            <div class="text-blue-700 font-semibold flex items-center gap-1">
                                                <span>✓ Verified & Forwarded for Final Approval</span>
                                            </div>
                                            <div class="text-slate-600 mt-0.5">
                                                Verified by: <strong class="text-slate-900"
                                                    x-text="historyRecord.tempApprovedBy"></strong>
                                            </div>
                                            <template x-if="historyRecord.tempApprovedNote">
                                                <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-blue-900 text-[11px] italic"
                                                    x-text="'“' + historyRecord.tempApprovedNote + '”'"></div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Manager Rejected --}}
                                    <template x-if="historyRecord.status === 'manager_rejected'">
                                        <div class="mt-1 text-xs">
                                            <div class="text-rose-700 font-semibold flex items-center gap-1">
                                                <span>✕ Rejected at Manager Review</span>
                                            </div>
                                            <div class="text-slate-600 mt-0.5">
                                                Rejected by: <strong class="text-slate-900"
                                                    x-text="historyRecord.rejectedBy || 'Manager'"></strong>
                                            </div>
                                            <template x-if="historyRecord.rejectionReason">
                                                <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded text-rose-900 text-[11px]"
                                                    x-text="'Reason: ' + historyRecord.rejectionReason"></div>
                                            </template>
                                            <div class="mt-1.5 text-[11px] text-slate-500 italic">
                                                Note: HR/Admin can still override with final approval or uphold this
                                                rejection.
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Pending L1 --}}
                                    <template x-if="!historyRecord.tempApprovedBy && historyRecord.status === 'pending'">
                                        <div class="mt-1 text-xs text-amber-700 font-medium">
                                            ⏳ Waiting for Level 1 Manager Verification
                                        </div>
                                    </template>

                                    {{-- Direct Final Approval without L1 --}}
                                    <template
                                        x-if="!historyRecord.tempApprovedBy && ['approved', 'rejected'].includes(historyRecord.status)">
                                        <div class="mt-1 text-xs text-slate-500 italic">
                                            — Direct review processed by Admin/Finance without Level 1 hold.
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Step 3: Level 2 Final Decision (HR / Finance / Admin) --}}
                            <div class="relative group">
                                <template x-if="historyRecord.status === 'approved'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-emerald-600 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template x-if="historyRecord.status === 'rejected'">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-rose-600 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>
                                <template x-if="!['approved', 'rejected'].includes(historyRecord.status)">
                                    <div
                                        class="absolute -left-6 top-0 w-4 h-4 rounded-full bg-slate-300 ring-4 ring-white flex items-center justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </div>
                                </template>

                                <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-900">Step 3: Level 2 Final Decision (HR /
                                            Admin)</span>
                                        <span class="text-[11px] text-slate-500"
                                            x-text="historyRecord.approvedAt || (historyRecord.status === 'rejected' ? historyRecord.rejectedAt : 'Awaiting')"></span>
                                    </div>

                                    {{-- Final Approved --}}
                                    <template x-if="historyRecord.status === 'approved'">
                                        <div class="mt-1 text-xs">
                                            <div class="text-emerald-700 font-bold flex items-center gap-1">
                                                <span>★ Final Approved & Cleared for Settlement</span>
                                            </div>
                                            <div class="text-slate-600 mt-0.5">
                                                Signed off by: <strong class="text-slate-900"
                                                    x-text="historyRecord.approvedBy || 'HR/Admin'"></strong>
                                            </div>
                                            <template x-if="historyRecord.finalNote">
                                                <div class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded text-emerald-900 text-[11px] italic"
                                                    x-text="'“' + historyRecord.finalNote + '”'"></div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Final Rejected --}}
                                    <template x-if="historyRecord.status === 'rejected'">
                                        <div class="mt-1 text-xs">
                                            <div class="text-rose-700 font-bold flex items-center gap-1">
                                                <span>✕ Final Rejected</span>
                                            </div>
                                            <div class="text-slate-600 mt-0.5">
                                                Rejected by: <strong class="text-slate-900"
                                                    x-text="historyRecord.rejectedBy || 'HR/Admin'"></strong>
                                            </div>
                                            <template x-if="historyRecord.rejectionReason">
                                                <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded text-rose-900 text-[11px]"
                                                    x-text="'Reason: ' + historyRecord.rejectionReason"></div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Ready for Final --}}
                                    <template x-if="historyRecord.status === 'temp_approved'">
                                        <div class="mt-1 text-xs text-blue-700 font-medium">
                                            ⏳ Ready for Final Review: Awaiting HR/Finance approval and payout clearance.
                                        </div>
                                    </template>

                                    {{-- Pending HR Decision on Manager Rejection --}}
                                    <template x-if="historyRecord.status === 'manager_rejected'">
                                        <div class="mt-1 text-xs text-rose-700 font-medium">
                                            ⚠️ Manager Rejection Pending Review: HR may uphold the rejection or override
                                            with Final Approval.
                                        </div>
                                    </template>

                                    {{-- Pending Level 1 first --}}
                                    <template x-if="historyRecord.status === 'pending'">
                                        <div class="mt-1 text-xs text-slate-400 italic">
                                            Awaiting Level 1 verification first.
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>

                    <div class="pt-4 border-t border-slate-200 flex justify-end">
                        <button type="button" @click="closeExpenseHistory" class="btn-secondary px-4 py-1.5 text-xs">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expense Action (Verify / Final Approve / Reject) Modal --}}
        <div x-show="actionModal.isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
            x-transition.opacity
            style="display: none;">
            <div @click.away="closeExpenseAction"
                class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 my-8"
                x-transition.scale>
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base" x-text="actionModal.title"></h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="actionModal.summary"></p>
                    </div>
                    <button type="button" @click="closeExpenseAction"
                        class="text-slate-400 hover:text-slate-700 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitExpenseAction" class="p-6 space-y-4">
                    <div>
                        <label class="form-label font-semibold text-xs text-slate-700" x-text="actionModal.promptLabel"></label>
                        <textarea x-model="actionModal.note" rows="3"
                            :required="actionModal.isRejection"
                            :placeholder="actionModal.isRejection ? 'Explain why this claim is being rejected...' : 'Optional approval note or remark...'"
                            class="form-input mt-1 w-full text-xs"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="closeExpenseAction" :disabled="actionModal.submitting"
                            class="btn-secondary text-xs px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" :disabled="actionModal.submitting"
                            class="text-xs px-4 py-2 font-bold rounded shadow-sm inline-flex items-center gap-1.5 transition hover:opacity-90"
                            :style="actionModal.confirmButtonStyle">
                            <span x-show="actionModal.submitting" class="animate-spin text-xs">⏳</span>
                            <span x-text="actionModal.confirmButtonText"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Expense Modal --}}
        <div x-show="editModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto" x-transition.opacity
            style="display: none;">
            <div @click.away="closeEditExpense"
                class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 my-8"
                x-transition.scale>
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Edit Expense Claim</h3>
                        <p class="text-xs text-slate-500">Update amount, category, date, rejection reason, or bill proof</p>
                    </div>
                    <button type="button" @click="closeEditExpense"
                        class="text-slate-400 hover:text-slate-700 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitExpenseEdit" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    {{-- Amount & Date Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Amount --}}
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Amount (₹) *</label>
                            <div
                                class="flex items-stretch rounded-lg shadow-sm mt-1 overflow-hidden border border-slate-300 focus-within:ring-2 focus-within:ring-brand-500 focus-within:border-brand-500 bg-white">
                                <span
                                    class="inline-flex items-center px-3 bg-slate-100 border-r border-slate-300 text-slate-600 font-bold text-sm select-none">
                                    ₹
                                </span>
                                <input type="number" step="0.01" min="0" x-model="editData.amount" required
                                    class="flex-1 px-3 py-2 border-0 font-bold text-slate-900 focus:outline-none focus:ring-0 text-sm bg-transparent"
                                    placeholder="0.00">
                            </div>
                        </div>

                        {{-- Date --}}
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Expense Date *</label>
                            <input type="date" x-model="editData.date" required class="form-input mt-1 w-full text-xs">
                        </div>
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-slate-700">Expense Category</label>
                        <template x-if="editData.isSystemTravel">
                            <div class="mt-1 px-3 py-2 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-lg text-xs font-semibold">
                                🚗 System Travel (Automated Distance Claim)
                            </div>
                        </template>
                        <template x-if="!editData.isSystemTravel">
                            <select x-model="editData.categoryId" class="form-select mt-1 w-full text-xs">
                                <option value="">-- Select Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </template>
                    </div>

                    {{-- Bill Photo Attachment Section --}}
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2.5">
                        <label class="form-label font-semibold text-xs text-slate-700 block">Bill Photo / Proof of
                            Expense</label>

                        {{-- Current Photo Preview --}}
                        <div class="flex items-center gap-3">
                            <template x-if="editData.billPhotoUrl && !editData.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <a :href="editData.billPhotoUrl" target="_blank" class="block group relative">
                                        <img :src="editData.billPhotoUrl" alt="Bill Photo"
                                            class="w-16 h-16 object-cover rounded border border-slate-300 group-hover:opacity-90 shadow-sm">
                                    </a>
                                    <div class="text-xs">
                                        <span class="text-slate-600 block font-medium">Current bill proof</span>
                                        <a :href="editData.billPhotoUrl" target="_blank"
                                            class="text-brand-600 hover:underline text-[11px] font-semibold">View Full Image
                                            ↗</a>
                                    </div>
                                </div>
                            </template>

                            {{-- New Photo Preview (if chosen) --}}
                            <template x-if="editData.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <img :src="editData.newPhotoPreview" alt="Replacement Bill Preview"
                                        class="w-16 h-16 object-cover rounded border-2 border-brand-500 shadow-sm">
                                    <div class="text-xs">
                                        <span class="text-brand-700 font-semibold block">New photo selected</span>
                                        <button type="button" @click="removeShowNewPhoto"
                                            class="text-rose-600 hover:underline text-[11px] font-semibold mt-0.5">Remove &
                                            Keep Original</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!editData.billPhotoUrl && !editData.newPhotoPreview">
                                <span class="text-xs text-slate-400 italic">No photo attached yet.</span>
                            </template>
                        </div>

                        {{-- Upload / Replace File Input --}}
                        <div>
                            <input type="file" x-ref="showEditBillPhotoInput" @change="handleShowEditPhotoChange($event)"
                                accept="image/*"
                                class="text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">Select a new image file to replace the bill receipt
                                (PNG, JPG, max 20MB).</p>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-slate-700">Approval Status</label>
                        <select x-model="editData.status" class="form-select mt-1 w-full text-xs font-semibold">
                            <option value="pending">⏳ Pending Review (Level 1)</option>
                            <option value="temp_approved">✓ Verified (Level 1)</option>
                            <option value="manager_rejected">⚠️ Manager Rejected</option>
                            <option value="approved">★ Final Approved</option>
                            <option value="rejected">✕ Final Rejected</option>
                        </select>
                    </div>

                    {{-- Rejection Reason Field (visible when status is rejected) --}}
                    <div x-show="['manager_rejected', 'rejected'].includes(editData.status)" x-transition>
                        <label class="form-label font-semibold text-xs text-rose-700 flex items-center gap-1">
                            <span>Why was this rejected? (Reason visible to employee) *</span>
                        </label>
                        <textarea x-model="editData.rejectionReason" rows="2"
                            class="form-input mt-1 w-full text-xs border-rose-300 focus:ring-rose-400 focus:border-rose-400"
                            placeholder="e.g. Incomplete bill proof, amount mismatch, or duplicate claim..."></textarea>
                    </div>

                    {{-- Settlement / Approval Note --}}
                    <div x-show="['approved', 'temp_approved'].includes(editData.status)" x-transition>
                        <label class="form-label font-semibold text-xs text-slate-700">Remarks / Approval Note
                            (Optional)</label>
                        <input type="text" x-model="editData.note" class="form-input mt-1 w-full text-xs"
                            placeholder="e.g. Approved per policy...">
                    </div>

                    {{-- Modal Actions --}}
                    <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button type="button" @click="closeEditExpense" class="btn-secondary px-4 py-2 text-sm"
                            :disabled="editSaving">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary px-5 py-2 text-sm font-semibold flex items-center gap-2"
                            :disabled="editSaving">
                            <span x-show="!editSaving">Save Changes</span>
                            <span x-show="editSaving" class="flex items-center gap-1.5">
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

        {{-- Edit Day End / Closing KM Modal --}}
        <div x-show="editDayEndOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div @click.away="closeEditDayEnd"
                class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 transition-all transform"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95">

                {{-- Header --}}
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                            <x-lucide-pencil class="w-4 h-4 text-indigo-600" />
                            <span>Adjust Day End & Closing KM</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Employee: <strong class="text-slate-800">{{ $dayStart->user?->name_with_role ?? 'Employee' }}</strong>
                            &bull; Date: <span class="font-semibold text-slate-800">{{ $dayStart->date?->format('d M Y') ?? '—' }}</span>
                        </p>
                    </div>
                    <button type="button" @click="closeEditDayEnd" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Error Alert in Modal --}}
                <template x-if="dayEndData.errorMsg">
                    <div class="mx-6 mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium flex items-center gap-2">
                        <x-lucide-alert-circle class="w-4 h-4 text-rose-500 shrink-0" />
                        <span x-text="dayEndData.errorMsg"></span>
                    </div>
                </template>

                <form @submit.prevent="submitDayEndEdit" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    {{-- Opening KM Reference Banner --}}
                    <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 flex items-center justify-between text-xs">
                        <div>
                            <span class="text-indigo-700 font-bold uppercase tracking-wider block text-[10px]">Opening Reading</span>
                            <span class="text-indigo-950 font-extrabold text-sm">{{ $dayStart->opening_km ?? 0 }} KM</span>
                        </div>
                        <div class="text-right">
                            <span class="text-indigo-700 font-bold uppercase tracking-wider block text-[10px]">Calculated Traveled</span>
                            <span class="text-indigo-600 font-extrabold text-sm"
                                  x-text="dayEndData.closingKm && dayEndData.closingKm >= dayEndData.openingKm ? (dayEndData.closingKm - dayEndData.openingKm).toFixed(1) + ' KM' : '—'">
                            </span>
                        </div>
                    </div>

                    {{-- Closing KM & Ended At Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Closing KM --}}
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Closing KM Reading <span class="text-rose-500">*</span></label>
                            <div class="flex items-stretch rounded-lg shadow-sm mt-1 overflow-hidden border border-slate-300 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 bg-white">
                                <span class="inline-flex items-center px-3 bg-slate-100 border-r border-slate-300 text-slate-600 font-bold text-xs select-none">
                                    KM
                                </span>
                                <input type="number" step="0.1" min="0" x-model="dayEndData.closingKm" required
                                    class="flex-1 px-3 py-2 border-0 font-bold text-slate-900 focus:outline-none focus:ring-0 text-sm bg-transparent"
                                    placeholder="e.g. 15420">
                            </div>
                        </div>

                        {{-- Ended At Time --}}
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Day End Time</label>
                            <input type="time" x-model="dayEndData.endTime" class="form-input mt-1 w-full text-xs font-semibold">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Leave as is or set actual departure time</span>
                        </div>
                    </div>

                    {{-- Closing KM Photo Attachment --}}
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                        <label class="form-label font-semibold text-xs text-slate-700 block">Closing Meter Photo (Optional)</label>

                        {{-- Current / New Photo Preview --}}
                        <div class="flex items-center gap-3">
                            <template x-if="dayEndData.closingPhotoUrl && !dayEndData.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <a :href="dayEndData.closingPhotoUrl" target="_blank" class="block group relative">
                                        <img :src="dayEndData.closingPhotoUrl" alt="Meter Photo"
                                            class="w-16 h-16 object-cover rounded-lg border border-slate-300 group-hover:opacity-90 shadow-sm">
                                    </a>
                                    <div class="text-xs">
                                        <span class="text-slate-600 block font-medium">Existing meter photo</span>
                                        <a :href="dayEndData.closingPhotoUrl" target="_blank"
                                            class="text-indigo-600 hover:underline text-[11px] font-semibold">View Full Image ↗</a>
                                    </div>
                                </div>
                            </template>

                            <template x-if="dayEndData.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <img :src="dayEndData.newPhotoPreview" alt="Replacement Meter Preview"
                                        class="w-16 h-16 object-cover rounded-lg border-2 border-indigo-500 shadow-sm">
                                    <div class="text-xs">
                                        <span class="text-indigo-700 font-semibold block">New meter photo selected</span>
                                        <button type="button" @click="dayEndData.newPhotoPreview = null; if($refs.showEditClosingPhotoInput) $refs.showEditClosingPhotoInput.value = '';"
                                            class="text-rose-600 hover:underline text-[11px] font-semibold mt-0.5">Remove</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!dayEndData.closingPhotoUrl && !dayEndData.newPhotoPreview">
                                <span class="text-xs text-slate-400 italic">No meter photo uploaded yet.</span>
                            </template>
                        </div>

                        <div>
                            <input type="file" x-ref="showEditClosingPhotoInput" @change="handleDayEndPhotoChange($event)"
                                accept="image/*"
                                class="text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, or JPEG up to 20MB.</p>
                        </div>
                    </div>

                    {{-- Remarks --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-slate-700">Remarks (Optional)</label>
                        <input type="text" x-model="dayEndData.remarks" class="form-input mt-1 w-full text-xs"
                            placeholder="e.g. End of day visits completed">
                    </div>

                    {{-- Rationale / Reason (Required for Audit Log) --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-slate-700 flex items-center justify-between">
                            <span>Reason for Admin Adjustment <span class="text-rose-500">*</span></span>
                            <span class="text-[10px] font-normal text-slate-400">Required for Audit Log</span>
                        </label>
                        <textarea x-model="dayEndData.reason" rows="2" required
                            class="form-input mt-1 w-full text-xs"
                            placeholder="e.g. Field rep forgot to press Day End; closing KM verified via odometer reading photo received on WhatsApp."></textarea>
                    </div>

                    {{-- Modal Actions --}}
                    <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button type="button" @click="closeEditDayEnd" class="btn-secondary px-4 py-2 text-xs"
                            :disabled="editDayEndSaving">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary px-5 py-2 text-xs font-semibold flex items-center gap-2"
                            style="background-color: #4f46e5; color: #ffffff;"
                            :disabled="editDayEndSaving">
                            <span x-show="!editDayEndSaving">Save & Apply Adjustment</span>
                            <span x-show="editDayEndSaving" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
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

        {{-- Edit Day Start Modal --}}
        <div x-show="editDayStartOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div @click.away="closeEditDayStart"
                class="bg-white rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-200 transition-all transform"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95">

                {{-- Header --}}
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                            <x-lucide-pencil class="w-4 h-4 text-indigo-600" />
                            <span>Adjust Day Start Details</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Employee: <strong class="text-slate-800">{{ $dayStart->user?->name_with_role ?? 'Employee' }}</strong>
                        </p>
                    </div>
                    <button type="button" @click="closeEditDayStart" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Error Alert in Modal --}}
                <template x-if="dayStartData.errorMsg">
                    <div class="mx-6 mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium flex items-center gap-2">
                        <x-lucide-alert-circle class="w-4 h-4 text-rose-500 shrink-0" />
                        <span x-text="dayStartData.errorMsg"></span>
                    </div>
                </template>

                <form @submit.prevent="submitDayStartEdit" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    {{-- Date & Start Time Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Date <span class="text-rose-500">*</span></label>
                            <input type="date" x-model="dayStartData.date" required class="form-input mt-1 w-full text-xs font-semibold">
                        </div>
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Start Time</label>
                            <input type="time" x-model="dayStartData.startTime" class="form-input mt-1 w-full text-xs font-semibold">
                        </div>
                    </div>

                    {{-- Tour Purpose & Tour Type Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Tour Purpose <span class="text-rose-500">*</span></label>
                            <select x-model="dayStartData.tourPurposeId" required class="form-select mt-1 w-full text-xs">
                                @foreach($tourPurposes as $tp)
                                    <option value="{{ $tp->id }}">{{ $tp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Tour Type <span class="text-rose-500">*</span></label>
                            <select x-model="dayStartData.tourTypeId" required class="form-select mt-1 w-full text-xs">
                                @foreach($tourTypes as $tt)
                                    <option value="{{ $tt->id }}">{{ $tt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Vehicle Type & Vehicle No Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Vehicle Type <span class="text-rose-500">*</span></label>
                            <select x-model="dayStartData.vehicleTypeId" required class="form-select mt-1 w-full text-xs">
                                @foreach($vehicleTypes as $vt)
                                    <option value="{{ $vt->id }}">{{ $vt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Vehicle Number</label>
                            <input type="text" x-model="dayStartData.vehicleNo" class="form-input mt-1 w-full text-xs font-semibold" placeholder="e.g. MP09-AB-1234">
                        </div>
                    </div>

                    {{-- Opening KM & Closing KM Grid --}}
                    <div class="p-3 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label font-bold text-xs text-indigo-950">Opening KM Reading <span class="text-rose-500">*</span></label>
                                <div class="flex items-stretch rounded-lg shadow-sm mt-1 overflow-hidden border border-slate-300 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 bg-white">
                                    <span class="inline-flex items-center px-3 bg-slate-100 border-r border-slate-300 text-slate-600 font-bold text-xs select-none">
                                        KM
                                    </span>
                                    <input type="number" step="0.1" min="0" x-model="dayStartData.openingKm" required
                                        class="flex-1 px-3 py-2 border-0 font-bold text-slate-900 focus:outline-none focus:ring-0 text-sm bg-transparent"
                                        placeholder="e.g. 1000">
                                </div>
                            </div>
                            <div>
                                <label class="form-label font-semibold text-xs text-slate-700">Closing KM Reading (Optional)</label>
                                <div class="flex items-stretch rounded-lg shadow-sm mt-1 overflow-hidden border border-slate-300 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 bg-white">
                                    <span class="inline-flex items-center px-3 bg-slate-100 border-r border-slate-300 text-slate-600 font-bold text-xs select-none">
                                        KM
                                    </span>
                                    <input type="number" step="0.1" min="0" x-model="dayStartData.closingKm"
                                        class="flex-1 px-3 py-2 border-0 font-bold text-slate-900 focus:outline-none focus:ring-0 text-sm bg-transparent"
                                        placeholder="Leave empty or set">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-indigo-100/80">
                            <span class="text-indigo-700 font-medium text-[11px]">Calculated Distance Traveled:</span>
                            <span class="text-indigo-950 font-extrabold"
                                x-text="dayStartData.closingKm && dayStartData.openingKm && Number(dayStartData.closingKm) >= Number(dayStartData.openingKm) ? (Number(dayStartData.closingKm) - Number(dayStartData.openingKm)).toFixed(1) + ' KM' : '—'">
                            </span>
                        </div>
                    </div>

                    {{-- Opening KM Photo Attachment --}}
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                        <label class="form-label font-semibold text-xs text-slate-700 block">Opening Meter Photo (Optional)</label>

                        <div class="flex items-center gap-3">
                            <template x-if="dayStartData.openingPhotoUrl && !dayStartData.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <a :href="dayStartData.openingPhotoUrl" target="_blank" class="block group relative">
                                        <img :src="dayStartData.openingPhotoUrl" alt="Meter Photo"
                                            class="w-16 h-16 object-cover rounded-lg border border-slate-300 group-hover:opacity-90 shadow-sm">
                                    </a>
                                    <div class="text-xs">
                                        <span class="text-slate-600 block font-medium">Existing opening photo</span>
                                        <a :href="dayStartData.openingPhotoUrl" target="_blank"
                                            class="text-indigo-600 hover:underline text-[11px] font-semibold">View Full Image ↗</a>
                                    </div>
                                </div>
                            </template>

                            <template x-if="dayStartData.newPhotoPreview">
                                <div class="flex items-center gap-2">
                                    <img :src="dayStartData.newPhotoPreview" alt="Replacement Opening Preview"
                                        class="w-16 h-16 object-cover rounded-lg border-2 border-indigo-500 shadow-sm">
                                    <div class="text-xs">
                                        <span class="text-indigo-700 font-semibold block">New meter photo selected</span>
                                        <button type="button" @click="dayStartData.newPhotoPreview = null; if($refs.showEditOpeningPhotoInput) $refs.showEditOpeningPhotoInput.value = '';"
                                            class="text-rose-600 hover:underline text-[11px] font-semibold mt-0.5">Remove</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!dayStartData.openingPhotoUrl && !dayStartData.newPhotoPreview">
                                <span class="text-xs text-slate-400 italic">No meter photo uploaded yet.</span>
                            </template>
                        </div>

                        <div>
                            <input type="file" x-ref="showEditOpeningPhotoInput" @change="handleDayStartPhotoChange($event)"
                                accept="image/*"
                                class="text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, or JPEG up to 20MB.</p>
                        </div>
                    </div>

                    {{-- Visit Place & Customer/Party Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Visit Place</label>
                            <input type="text" x-model="dayStartData.visitPlace" class="form-input mt-1 w-full text-xs font-semibold" placeholder="e.g. Indore Sector 3">
                        </div>
                        <div>
                            <label class="form-label font-semibold text-xs text-slate-700">Party / Customer</label>
                            <select x-model="dayStartData.partyId" class="form-select mt-1 w-full text-xs">
                                <option value="">-- None / Select Party --</option>
                                @foreach($customers as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Rationale / Reason (Required for Audit Log) --}}
                    <div>
                        <label class="form-label font-semibold text-xs text-slate-700 flex items-center justify-between">
                            <span>Reason for Admin Adjustment <span class="text-rose-500">*</span></span>
                            <span class="text-[10px] font-normal text-slate-400">Required for Audit Log</span>
                        </label>
                        <textarea x-model="dayStartData.reason" rows="2" required
                            class="form-input mt-1 w-full text-xs"
                            placeholder="e.g. Field rep entered incorrect Opening KM; corrected from 15000 to 1500 per actual odometer reading."></textarea>
                    </div>

                    {{-- Modal Actions --}}
                    <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button type="button" @click="closeEditDayStart" class="btn-secondary px-4 py-2 text-xs"
                            :disabled="editDayStartSaving">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary px-5 py-2 text-xs font-semibold flex items-center gap-2"
                            style="background-color: #4f46e5; color: #ffffff;"
                            :disabled="editDayStartSaving">
                            <span x-show="!editDayStartSaving">Save & Apply Adjustment</span>
                            <span x-show="editDayStartSaving" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
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