@extends('admin.layouts.app')

@section('title', 'Transactions - ' . $fieldWorker->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('admin.field-workers.index') }}" class="p-2 bg-slate-200 rounded-lg hover:bg-slate-300 transition">
                    <x-lucide-arrow-left class="w-6 h-6 text-slate-700" />
                </a>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-slate-900">{{ $fieldWorker->name }} - Transactions</h1>
                    <p class="text-slate-600">{{ $fieldWorker->employee_id }}</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-red-500">
                    <p class="text-sm text-slate-600 mb-2">Pending Payments</p>
                    <p class="text-2xl font-bold text-red-600">₹ {{ number_format($pendingAmount, 0) }}</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-green-500">
                    <p class="text-sm text-slate-600 mb-2">Total Transactions</p>
                    <p class="text-2xl font-bold text-green-600">{{ $transactions->total() }}</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-blue-500">
                    <p class="text-sm text-slate-600 mb-2">Per Page</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $transactions->perPage() }}</p>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        @if($transactions->count() > 0)
            <div class="bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Party</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Type</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Reference</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Mode</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Status</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($transactions as $transaction)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-sm text-slate-900 font-semibold">
                                    {{ $transaction->transaction_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $transaction->party->name }}</p>
                                        <p class="text-xs text-slate-600">{{ $transaction->party->city ?? '—' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold @class([
                                        'bg-blue-100 text-blue-700' => $transaction->transaction_type === 'payment',
                                        'bg-green-100 text-green-700' => $transaction->transaction_type === 'credit',
                                        'bg-red-100 text-red-700' => $transaction->transaction_type === 'debit',
                                    ])">
                                        {{ ucfirst($transaction->transaction_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $transaction->reference_number ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $transaction->payment_mode ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold @class([
                                        'bg-green-100 text-green-700' => $transaction->status === 'completed',
                                        'bg-yellow-100 text-yellow-700' => $transaction->status === 'pending',
                                        'bg-red-100 text-red-700' => $transaction->status === 'failed',
                                    ])">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-900 text-right">
                                    ₹ {{ number_format($transaction->amount, 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
                <x-lucide-inbox class="w-16 h-16 text-slate-300 mx-auto mb-4" />
                <p class="text-slate-600">No transactions found</p>
            </div>
        @endif

        <!-- Back Button -->
        <div class="mt-8">
            <a href="{{ route('admin.field-workers.metrics', $fieldWorker->id) }}" class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition font-semibold flex items-center gap-2 w-fit">
                <x-lucide-arrow-left class="w-4 h-4" />
                Back to Metrics
            </a>
        </div>
    </div>
</div>
@endsection
