@extends('admin.layouts.app')

@section('title', 'Payments')
@section('breadcrumb', 'ADMIN / PAYMENTS')
@section('page_title', 'Payment Collection & Confirmation')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ $activeTab }}',
    partyId: '{{ $partyId }}',
    collectedBy: '{{ $collectedBy }}',
    status: '{{ $status }}',
    search: '{{ $search }}',
    loading: false,
    pendingPayments: {{ Js::from($pendingPayments->map(fn($p) => [
        'id'                  => $p->id,
        'receipt_no'          => $p->receipt_no,
        'party_name'          => $p->party?->name ?? '—',
        'party_mobile'        => $p->party?->mobile ?? '',
        'collected_by_name'   => $p->collectedBy?->name_with_role ?? '—',
        'amount'              => (float) $p->amount,
        'amount_formatted'    => number_format((float) $p->amount, 2),
        'payment_mode'        => $p->payment_mode,
        'reference_number'    => $p->reference_number,
        'proof_photo_url'     => $p->proof_photo_url ? asset($p->proof_photo_url) : null,
        'collection_date'     => $p->collection_datetime ? $p->collection_datetime->format('d M Y') : $p->created_at->format('d M Y'),
        'collection_time'     => $p->collection_datetime ? $p->collection_datetime->format('h:i A') : $p->created_at->format('h:i A'),
        'confirm_url'         => route('admin.payments.confirm', $p->id),
        'reject_url'          => route('admin.payments.reject', $p->id),
    ])) }},
    allPayments: {{ Js::from($allPayments->map(fn($p) => [
        'id'                  => $p->id,
        'receipt_no'          => $p->receipt_no,
        'party_name'          => $p->party?->name ?? '—',
        'collected_by_name'   => $p->collectedBy?->name_with_role ?? '—',
        'amount'              => (float) $p->amount,
        'amount_formatted'    => number_format((float) $p->amount, 2),
        'payment_mode'        => $p->payment_mode,
        'status'              => $p->status,
        'collection_datetime' => $p->collection_datetime ? $p->collection_datetime->format('d M Y, h:i A') : $p->created_at->format('d M Y, h:i A'),
        'confirmed_by_name'   => $p->confirmedBy?->name_with_role,
        'confirmed_at'        => $p->confirmed_at?->format('d M Y, h:i A'),
        'rejection_reason'    => $p->rejection_reason,
    ])) }},
    repSummaries: {{ Js::from($repSummaries->map(fn($rs) => [
        'rep_name'      => $rs->collectedBy?->name_with_role ?? 'Unknown Rep',
        'pending_count' => (int) $rs->pending_count,
        'pending_total' => number_format((float) $rs->pending_total, 2),
    ])) }},
    pagination: {
        current_page: {{ $allPayments->currentPage() }},
        last_page: {{ $allPayments->lastPage() }},
        per_page: {{ $allPayments->perPage() }},
        total: {{ $allPayments->total() }},
        from: {{ $allPayments->firstItem() ?? 0 }},
        to: {{ $allPayments->lastItem() ?? 0 }}
    },
    rejectModal: {
        open: false,
        id: null,
        receiptNo: '',
        partyName: '',
        amount: '',
        reason: '',
        submitting: false
    },

    async fetchRecords(page = 1) {
        this.loading = true;
        try {
            const params = new URLSearchParams();
            params.append('tab', this.activeTab);
            if (this.partyId) params.append('party_id', this.partyId);
            if (this.collectedBy) params.append('collected_by', this.collectedBy);
            if (this.status) params.append('status', this.status);
            if (this.search) params.append('search', this.search);
            params.append('page', page);

            const res = await fetch(`{{ route('admin.payments.index') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (data.success) {
                this.pendingPayments = data.pendingPayments;
                this.allPayments = data.allPayments;
                this.repSummaries = data.repSummaries;
                this.pagination = data.pagination;
            }
        } catch (e) {
            console.error('Failed to load payments', e);
        } finally {
            this.loading = false;
        }
    },

    setTab(tab) {
        this.activeTab = tab;
        this.fetchRecords(1);
    },

    resetFilters() {
        this.partyId = '';
        this.collectedBy = '';
        this.status = 'all';
        this.search = '';
        this.fetchRecords(1);
    },

    async confirmPayment(payment) {
        if (!confirm(`Confirm payment receipt of ₹${payment.amount_formatted} from ${payment.party_name}? This will credit their ledger balance.`)) {
            return;
        }

        try {
            const res = await fetch(payment.confirm_url, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                window.toast(data.message || 'Payment confirmed successfully!', 'success');
                this.fetchRecords(this.pagination.current_page);
            } else {
                window.toast(data.message || 'Failed to confirm payment.', 'error');
            }
        } catch (e) {
            window.toast('Error confirming payment.', 'error');
        }
    },

    openReject(payment) {
        this.rejectModal.id = payment.id;
        this.rejectModal.receiptNo = payment.receipt_no;
        this.rejectModal.partyName = payment.party_name;
        this.rejectModal.amount = payment.amount_formatted;
        this.rejectModal.reason = '';
        this.rejectModal.open = true;
    },

    closeReject() {
        this.rejectModal.open = false;
    },

    async submitReject() {
        if (!this.rejectModal.reason || this.rejectModal.reason.length < 3) {
            window.toast('Please enter a rejection reason (minimum 3 characters).', 'warning');
            return;
        }

        this.rejectModal.submitting = true;
        try {
            const res = await fetch(`/admin/payments/${this.rejectModal.id}/reject`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ rejection_reason: this.rejectModal.reason })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.closeReject();
                window.toast(data.message || 'Payment rejected successfully.', 'warning');
                this.fetchRecords(this.pagination.current_page);
            } else {
                window.toast(data.message || 'Failed to reject payment.', 'error');
            }
        } catch (e) {
            window.toast('Error rejecting payment.', 'error');
        } finally {
            this.rejectModal.submitting = false;
        }
    }
}">

    {{-- ── 1. Per-Rep Pending Summary Widget ── --}}
    <template x-if="repSummaries && repSummaries.length > 0">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <template x-for="(repSummary, index) in repSummaries" :key="index">
                <div class="panel p-4 border-l-4 border-amber-500 bg-amber-50/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Unconfirmed Rep Collection</span>
                            <h4 class="text-sm font-extrabold text-ink-900 mt-1" x-text="repSummary.rep_name"></h4>
                            <p class="text-xs text-ink-500" x-text="repSummary.pending_count + ' payment(s) pending'"></p>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-black text-amber-600" x-text="'₹' + repSummary.pending_total"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- ── 2. Navigation Tabs (Zero-Reload AJAX) ── --}}
    <div class="flex border-b border-surface-border">
        <button type="button" @click="setTab('pending')" 
           class="px-6 py-3 font-bold text-sm border-b-2 transition-colors cursor-pointer"
           :class="activeTab === 'pending' ? 'border-brand-600 text-brand-600' : 'border-transparent text-ink-500 hover:text-ink-700'">
            Pending Confirmation Queue 
            <span x-show="pendingPayments && pendingPayments.length > 0" 
                  class="ml-2 px-2 py-0.5 text-xs font-bold bg-amber-500 text-white rounded-full" 
                  x-text="pendingPayments.length"></span>
        </button>
        <button type="button" @click="setTab('all')" 
           class="px-6 py-3 font-bold text-sm border-b-2 transition-colors cursor-pointer"
           :class="activeTab === 'all' ? 'border-brand-600 text-brand-600' : 'border-transparent text-ink-500 hover:text-ink-700'">
            All Payment Records (History)
        </button>
    </div>

    {{-- ── TAB 1: PENDING CONFIRMATION QUEUE ── --}}
    <div x-show="activeTab === 'pending'" class="panel overflow-hidden relative min-h-[250px]" x-transition>
        {{-- Loading Overlay --}}
        <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center z-10" x-transition>
            <div class="flex items-center gap-2 text-brand-600 font-semibold text-sm">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating payments...
            </div>
        </div>

        <div class="panel-header flex items-center justify-between">
            <div>
                <h3 class="text-section-title">Awaiting Admin Confirmation</h3>
                <p class="text-caption mt-1">Payments here have NOT yet credited the dealer's ledger. Confirming will apply the credit.</p>
            </div>
            <span class="text-xs font-bold text-ink-600 px-2.5 py-1 bg-surface-muted rounded-full" x-text="(pendingPayments ? pendingPayments.length : 0) + ' pending'"></span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Dealer (Party)</th>
                        <th>Collected By</th>
                        <th>Amount</th>
                        <th>Mode & Ref</th>
                        <th>Proof Photo</th>
                        <th>Date & Time</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="payment in pendingPayments" :key="payment.id">
                        <tr class="hover:bg-surface-muted/60 transition-colors">
                            <td class="font-mono text-xs font-bold text-brand-700" x-text="payment.receipt_no"></td>
                            <td>
                                <div class="font-semibold text-ink-900" x-text="payment.party_name"></div>
                                <div class="text-xs text-ink-400" x-text="payment.party_mobile"></div>
                            </td>
                            <td>
                                <div class="font-medium text-ink-800" x-text="payment.collected_by_name"></div>
                            </td>
                            <td class="font-extrabold text-emerald-600 text-sm" x-text="'₹' + payment.amount_formatted"></td>
                            <td>
                                <span class="inline-block px-2 py-0.5 text-xs font-bold rounded uppercase bg-surface-muted text-ink-800" x-text="payment.payment_mode"></span>
                                <template x-if="payment.reference_number">
                                    <div class="text-xs font-mono text-ink-500 mt-1" x-text="'Ref: ' + payment.reference_number"></div>
                                </template>
                            </td>
                            <td>
                                <template x-if="payment.proof_photo_url">
                                    <a :href="payment.proof_photo_url" target="_blank" class="inline-block relative group">
                                        <img :src="payment.proof_photo_url" alt="Proof" class="w-10 h-10 object-cover rounded-lg border border-surface-border">
                                        <span class="absolute inset-0 bg-black/40 text-white text-[9px] font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 rounded-lg">View</span>
                                    </a>
                                </template>
                                <template x-if="!payment.proof_photo_url">
                                    <span class="text-xs text-ink-400 italic">No photo</span>
                                </template>
                            </td>
                            <td class="text-caption">
                                <div x-text="payment.collection_date"></div>
                                <div class="text-ink-400 text-[11px]" x-text="payment.collection_time"></div>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Confirm Button --}}
                                    <button type="button" @click="confirmPayment(payment)" class="btn-primary text-xs px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700">
                                        Confirm
                                    </button>

                                    {{-- Reject Trigger --}}
                                    <button type="button" @click="openReject(payment)" class="btn-danger text-xs px-2.5 py-1.5">
                                        Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!loading && (!pendingPayments || pendingPayments.length === 0)">
                        <td colspan="8" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-inbox class="w-10 h-10" />
                                <p class="text-sm font-medium">No pending payments in queue.</p>
                                <p class="text-xs text-ink-400">All field collections have been processed.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── TAB 2: ALL PAYMENT RECORDS (HISTORY & AJAX FILTERS) ── --}}
    <div x-show="activeTab === 'all'" class="panel overflow-hidden" x-transition>
        <div class="panel-header">
            <h3 class="text-section-title mb-3">Filter Payments</h3>
            <form @submit.prevent="fetchRecords(1)" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="form-label">Dealer</label>
                    <select x-model="partyId" @change="fetchRecords(1)" class="form-input">
                        <option value="">All Dealers</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Field Rep</label>
                    <select x-model="collectedBy" @change="fetchRecords(1)" class="form-input">
                        <option value="">All Field Reps</option>
                        @foreach($reps as $r)
                            <option value="{{ $r->id }}">{{ $r->name_with_role }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Status</label>
                    <select x-model="status" @change="fetchRecords(1)" class="form-input">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Search</label>
                    <input type="text" x-model="search" @keydown.enter.prevent="fetchRecords(1)" placeholder="Receipt or ref no..." class="form-input">
                </div>

                <div class="flex gap-2">
                    <button type="submit" :disabled="loading" class="btn-primary w-full py-2 text-sm font-semibold">
                        <span x-show="!loading" class="flex items-center gap-1.5">
                            <x-lucide-filter class="w-4 h-4" />
                            Filter
                        </span>
                        <span x-show="loading" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            ...
                        </span>
                    </button>
                    <button type="button" @click="resetFilters" class="btn-secondary px-3 py-2 text-sm">Reset</button>
                </div>
            </form>
        </div>

        <div class="table-wrap relative min-h-[300px]">
            {{-- Loading Overlay --}}
            <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center z-10" x-transition>
                <div class="flex items-center gap-2 text-brand-600 font-semibold text-sm">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Updating history...
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Dealer</th>
                        <th>Collected By</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Confirmed / Reviewed By</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="payment in allPayments" :key="payment.id">
                        <tr class="hover:bg-surface-muted/60 transition-colors">
                            <td class="font-mono text-xs font-bold text-brand-700" x-text="payment.receipt_no"></td>
                            <td>
                                <div class="font-semibold text-ink-900" x-text="payment.party_name"></div>
                            </td>
                            <td>
                                <div class="font-medium text-ink-800" x-text="payment.collected_by_name"></div>
                            </td>
                            <td class="font-extrabold text-ink-900 text-sm" x-text="'₹' + payment.amount_formatted"></td>
                            <td>
                                <span class="inline-block px-2 py-0.5 text-xs font-bold rounded uppercase bg-surface-muted text-ink-800" x-text="payment.payment_mode"></span>
                            </td>
                            <td>
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': payment.status === 'confirmed',
                                        'bg-amber-100 text-amber-800': payment.status === 'pending',
                                        'bg-rose-100 text-rose-800': payment.status === 'rejected',
                                        'bg-surface-muted text-ink-700': !['confirmed','pending','rejected'].includes(payment.status)
                                    }"
                                    :title="payment.rejection_reason || ''"
                                    x-text="payment.status.toUpperCase()">
                                </span>
                            </td>
                            <td class="text-caption" x-text="payment.collection_datetime"></td>
                            <td class="text-caption">
                                <template x-if="payment.confirmed_by_name">
                                    <div>
                                        <div class="font-semibold text-ink-800" x-text="payment.confirmed_by_name"></div>
                                        <div class="text-ink-400 text-[11px]" x-text="payment.confirmed_at"></div>
                                    </div>
                                </template>
                                <template x-if="!payment.confirmed_by_name && payment.status === 'rejected' && payment.rejection_reason">
                                    <div class="text-rose-700 font-semibold italic" x-text="'Reason: ' + payment.rejection_reason"></div>
                                </template>
                                <template x-if="!payment.confirmed_by_name && (!payment.rejection_reason || payment.status !== 'rejected')">
                                    <span class="text-ink-400 italic">—</span>
                                </template>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!loading && (!allPayments || allPayments.length === 0)">
                        <td colspan="8" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-inbox class="w-10 h-10" />
                                <p class="text-sm font-medium">No payment records found.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- AJAX Pagination --}}
        <div x-show="pagination.last_page > 1" class="border-t border-surface-border px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-ink-500">
                Showing <span class="font-semibold text-ink-800" x-text="pagination.from"></span> to <span class="font-semibold text-ink-800" x-text="pagination.to"></span> of <span class="font-semibold text-ink-800" x-text="pagination.total"></span> payments
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button"
                    @click="fetchRecords(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1 || loading"
                    class="btn-secondary text-xs px-2.5 py-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <span class="text-xs text-ink-600 px-2 font-medium">
                    Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                </span>
                <button type="button"
                    @click="fetchRecords(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page || loading"
                    class="btn-secondary text-xs px-2.5 py-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>
    </div>

    {{-- ── Reject Reason Modal (AJAX) ── --}}
    <div x-show="rejectModal.open" 
         x-cloak
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 backdrop-blur-[2px]"
         x-transition>
        <div @click.away="closeReject()" class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-base font-bold text-rose-700">Reject Payment</h3>
                <button type="button" @click="closeReject()" class="text-ink-400 hover:text-ink-700 font-bold">&times;</button>
            </div>

            <p class="text-xs text-ink-600">
                Rejecting payment <span class="font-mono font-bold text-ink-900" x-text="rejectModal.receiptNo"></span> of 
                <span class="font-bold text-ink-900" x-text="'₹' + rejectModal.amount"></span> from <span class="font-bold text-ink-900" x-text="rejectModal.partyName"></span>.
            </p>

            <form @submit.prevent="submitReject()" class="space-y-4">
                <div>
                    <label class="form-label text-xs font-bold text-ink-700">Reason for Rejection <span class="text-rose-500">*</span></label>
                    <textarea x-model="rejectModal.reason" rows="3" required placeholder="e.g. UTR number not matching bank statement, cheque signature mismatch..." class="form-input text-xs"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="closeReject()" class="btn-secondary px-4 py-2 text-xs">Cancel</button>
                    <button type="submit" :disabled="rejectModal.submitting" class="btn-danger px-4 py-2 text-xs flex items-center gap-1.5">
                        <span x-show="!rejectModal.submitting">Confirm Rejection</span>
                        <span x-show="rejectModal.submitting">Rejecting...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
