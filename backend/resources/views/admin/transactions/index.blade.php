@extends('admin.layouts.app')

@section('title', 'Transactions')
@section('breadcrumb', 'ADMIN / FINANCE')
@section('page_title', 'Transactions')

@section('content')
<div class="panel">
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-section-title">Transaction Ledger</h2>
                <p class="text-caption mt-1">Filter by customer, field user, type, and approval status.</p>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                <input name="q" value="{{ $search }}" class="form-input pl-9" placeholder="Customer, user, notes…">
            </div>
            <select name="status" class="form-input">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($selectedStatus === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="type" class="form-input">
                <option value="">All types</option>
                @foreach ($types as $t)
                    <option value="{{ $t }}" @selected($selectedType === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <x-button type="submit" variant="primary" class="flex-1 justify-center">
                    <x-lucide-filter class="w-4 h-4" />
                    Apply
                </x-button>
                @if ($search || $selectedStatus || $selectedType)
                    <x-button href="{{ route('admin.transactions.index') }}" variant="secondary">Reset</x-button>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-right tabular-nums">Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>
                            <div class="text-body-emphasis">TXN-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</div>
                            <div class="text-caption">{{ $transaction->notes ? str($transaction->notes)->limit(42) : 'No notes' }}</div>
                        </td>
                        <td>
                            <div class="text-body-emphasis">{{ $transaction->customer?->name ?? '—' }}</div>
                            <div class="text-caption">{{ $transaction->customer?->mobile ?? '' }}</div>
                        </td>
                        <td>
                            <div class="text-body font-medium">{{ $transaction->user?->name_with_role ?? '—' }}</div>
                            <div class="text-caption">{{ $transaction->user?->email ?? '' }}</div>
                        </td>
                        <td><span class="badge badge-neutral">{{ ucfirst($transaction->type) }}</span></td>
                        <td>
                            <x-status-badge :status="$transaction->status" :map="['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning']" />
                        </td>
                        <td class="text-right font-bold tabular-nums text-ink-900">₹{{ number_format((float) $transaction->amount, 2) }}</td>
                        <td class="text-caption">{{ $transaction->created_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-receipt class="w-10 h-10" />
                                <p class="text-sm font-medium">No transactions found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-surface-border px-6 py-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
