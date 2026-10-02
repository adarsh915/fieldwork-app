@extends('admin.layouts.app')

@section('title', 'Payments')
@section('breadcrumb', 'ADMIN / PAYMENTS')
@section('page_title', 'Payment Collection & Confirmation')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Per-Rep Pending Summary Widget ── --}}
    @if($repSummaries->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($repSummaries as $repSummary)
        <div class="panel p-4 border-l-4 border-amber-500 bg-amber-50/50">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">Unconfirmed Rep Collection</span>
                    <h4 class="text-sm font-extrabold text-ink-900 mt-1">{{ $repSummary->collectedBy?->name ?? 'Unknown Rep' }}</h4>
                    <p class="text-xs text-ink-500">{{ $repSummary->pending_count }} payment(s) pending</p>
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-amber-600">₹{{ number_format($repSummary->pending_total, 2) }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── 2. Navigation Tabs ── --}}
    <div class="flex border-b border-surface-200">
        <a href="{{ route('admin.payments.index', ['tab' => 'pending']) }}" 
           class="px-6 py-3 font-bold text-sm border-b-2 transition-colors {{ $activeTab === 'pending' ? 'border-primary-600 text-primary-600' : 'border-transparent text-ink-500 hover:text-ink-700' }}">
            Pending Confirmation Queue 
            @if($pendingPayments->count() > 0)
                <span class="ml-2 px-2 py-0.5 text-xs font-bold bg-amber-500 text-white rounded-full">{{ $pendingPayments->count() }}</span>
            @endif
        </a>
        <a href="{{ route('admin.payments.index', ['tab' => 'all']) }}" 
           class="px-6 py-3 font-bold text-sm border-b-2 transition-colors {{ $activeTab === 'all' ? 'border-primary-600 text-primary-600' : 'border-transparent text-ink-500 hover:text-ink-700' }}">
            All Payment Records (History)
        </a>
    </div>

    {{-- ── TAB 1: PENDING CONFIRMATION QUEUE ── --}}
    @if($activeTab === 'pending')
    <div class="panel overflow-hidden">
        <div class="p-4 border-b border-surface-200 bg-surface-50 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-ink-900">Awaiting Admin Confirmation</h3>
                <p class="text-xs text-ink-500">Payments here have NOT yet credited the dealer's ledger. Confirming will apply the credit.</p>
            </div>
            <span class="text-xs font-bold text-ink-600">{{ $pendingPayments->count() }} pending</span>
        </div>

        <div class="table-wrap">
            <table class="data-table w-full">
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
                    @forelse($pendingPayments as $payment)
                    <tr>
                        <td class="font-mono text-xs font-bold text-primary-700">
                            {{ $payment->receipt_no }}
                        </td>
                        <td>
                            <div class="font-bold text-ink-900">{{ $payment->party?->name ?? '—' }}</div>
                            <div class="text-xs text-ink-500">{{ $payment->party?->mobile ?? '' }}</div>
                        </td>
                        <td>
                            <div class="font-semibold text-ink-800">{{ $payment->collectedBy?->name ?? '—' }}</div>
                            <div class="text-xs text-ink-400">ID: {{ $payment->collectedBy?->employee_id ?? $payment->collected_by }}</div>
                        </td>
                        <td class="font-extrabold text-emerald-600 text-base">
                            ₹{{ number_format($payment->amount, 2) }}
                        </td>
                        <td>
                            <span class="inline-block px-2 py-0.5 text-xs font-bold rounded uppercase bg-surface-200 text-ink-800">
                                {{ $payment->payment_mode }}
                            </span>
                            @if($payment->reference_number)
                                <div class="text-xs font-mono text-ink-600 mt-1">Ref: {{ $payment->reference_number }}</div>
                            @endif
                        </td>
                        <td>
                            @if($payment->proof_photo_url)
                                <a href="{{ asset($payment->proof_photo_url) }}" target="_blank" class="inline-block relative group">
                                    <img src="{{ asset($payment->proof_photo_url) }}" alt="Proof" class="w-12 h-12 object-cover rounded border border-surface-200">
                                    <span class="absolute inset-0 bg-black/40 text-white text-[9px] font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 rounded">View</span>
                                </a>
                            @else
                                <span class="text-xs text-ink-400 italic">No photo</span>
                            @endif
                        </td>
                        <td class="text-xs text-ink-600">
                            <div>{{ $payment->collection_datetime ? $payment->collection_datetime->format('d M Y') : $payment->created_at->format('d M Y') }}</div>
                            <div class="text-ink-400">{{ $payment->collection_datetime ? $payment->collection_datetime->format('h:i A') : $payment->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Confirm Form --}}
                                <form method="POST" action="{{ route('admin.payments.confirm', $payment->id) }}" onsubmit="return confirm('Confirm payment receipt of ₹{{ number_format($payment->amount, 2) }} from {{ addslashes($payment->party?->name) }}? This will credit their ledger balance.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                                        Confirm
                                    </button>
                                </form>

                                {{-- Reject Trigger --}}
                                <button type="button" 
                                        onclick="openRejectModal('{{ $payment->id }}', '{{ $payment->receipt_no }}', '{{ addslashes($payment->party?->name) }}', '{{ number_format($payment->amount, 2) }}')" 
                                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition-colors">
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-ink-400">
                            <div class="text-sm font-semibold">No pending payments in queue.</div>
                            <p class="text-xs mt-1">All field collections have been processed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── TAB 2: ALL PAYMENT RECORDS (HISTORY & FILTERS) ── --}}
    @if($activeTab === 'all')
    <div class="panel p-6 mb-4">
        <h3 class="font-extrabold text-ink-900 mb-3">Filter Payments</h3>
        <form method="GET" action="{{ route('admin.payments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <input type="hidden" name="tab" value="all">

            <div>
                <label class="form-label text-xs">Dealer</label>
                <select name="party_id" class="form-input text-xs">
                    <option value="">All Dealers</option>
                    @foreach($parties as $p)
                        <option value="{{ $p->id }}" {{ $partyId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Field Rep</label>
                <select name="collected_by" class="form-input text-xs">
                    <option value="">All Field Reps</option>
                    @foreach($reps as $r)
                        <option value="{{ $r->id }}" {{ $collectedBy == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Status</label>
                <select name="status" class="form-input text-xs">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="confirmed" {{ $status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Receipt or Ref No..." class="form-input text-xs">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary w-full py-2 text-xs font-bold">Apply Filter</button>
                <a href="{{ route('admin.payments.index', ['tab' => 'all']) }}" class="px-3 py-2 text-xs font-bold rounded-lg border border-surface-300 text-ink-600 hover:bg-surface-100 text-center">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="data-table w-full">
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
                    @forelse($allPayments as $payment)
                    <tr>
                        <td class="font-mono text-xs font-bold text-primary-700">
                            {{ $payment->receipt_no }}
                        </td>
                        <td>
                            <div class="font-bold text-ink-900">{{ $payment->party?->name ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="font-semibold text-ink-800">{{ $payment->collectedBy?->name ?? '—' }}</div>
                        </td>
                        <td class="font-extrabold text-ink-900 text-sm">
                            ₹{{ number_format($payment->amount, 2) }}
                        </td>
                        <td>
                            <span class="inline-block px-2 py-0.5 text-xs font-bold rounded uppercase bg-surface-200 text-ink-800">
                                {{ $payment->payment_mode }}
                            </span>
                        </td>
                        <td>
                            @if($payment->status === 'confirmed')
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded bg-emerald-100 text-emerald-800">CONFIRMED</span>
                            @elseif($payment->status === 'pending')
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded bg-amber-100 text-amber-800">PENDING</span>
                            @elseif($payment->status === 'rejected')
                                <span class="px-2 py-0.5 text-xs font-extrabold rounded bg-rose-100 text-rose-800" title="{{ $payment->rejection_reason }}">REJECTED</span>
                            @endif
                        </td>
                        <td class="text-xs text-ink-600">
                            {{ $payment->collection_datetime ? $payment->collection_datetime->format('d M Y, h:i A') : $payment->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td class="text-xs text-ink-600">
                            @if($payment->confirmedBy)
                                <div class="font-semibold text-ink-800">{{ $payment->confirmedBy->name }}</div>
                                <div class="text-ink-400 text-[11px]">{{ $payment->confirmed_at?->format('d M Y, h:i A') }}</div>
                            @elseif($payment->status === 'rejected' && $payment->rejection_reason)
                                <div class="text-rose-700 font-semibold italic">Reason: {{ Str::limit($payment->rejection_reason, 30) }}</div>
                            @else
                                <span class="text-ink-400 italic">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-ink-400">
                            <div class="text-sm font-semibold">No payment records found.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($allPayments->hasPages())
        <div class="p-4 border-t border-surface-200">
            {{ $allPayments->links() }}
        </div>
        @endif
    </div>
    @endif

</div>

{{-- ── Reject Reason Modal ── --}}
<div id="rejectModal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="text-base font-bold text-rose-700">Reject Payment</h3>
            <button type="button" onclick="closeRejectModal()" class="text-ink-400 hover:text-ink-700 font-bold">&times;</button>
        </div>

        <p class="text-xs text-ink-600">
            Rejecting payment <span id="rejectReceiptNo" class="font-mono font-bold text-ink-900"></span> of 
            <span id="rejectAmount" class="font-bold text-ink-900"></span> from <span id="rejectPartyName" class="font-bold text-ink-900"></span>.
        </p>

        <form id="rejectForm" method="POST" action="" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="form-label text-xs font-bold text-ink-700">Reason for Rejection <span class="text-rose-500">*</span></label>
                <textarea name="rejection_reason" rows="3" required placeholder="e.g. UTR number not matching bank statement, cheque signature mismatch..." class="form-input text-xs"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-xs font-bold rounded-lg border border-surface-300 text-ink-600 hover:bg-surface-100">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-rose-600 hover:bg-rose-700 text-white">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, receiptNo, partyName, amount) {
    document.getElementById('rejectReceiptNo').innerText = receiptNo;
    document.getElementById('rejectPartyName').innerText = partyName;
    document.getElementById('rejectAmount').innerText = '₹' + amount;
    document.getElementById('rejectForm').action = '/admin/payments/' + id + '/reject';
    const modal = document.getElementById('rejectModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection
