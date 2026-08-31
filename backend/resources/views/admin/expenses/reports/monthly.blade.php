@extends('admin.layouts.app')

@section('title', 'Monthly Expense Summary')
@section('breadcrumb', 'ADMIN / EXPENSES / MONTHLY')
@section('page_title', 'Monthly Expense Summary')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="panel p-4 flex-1">
        <form method="GET" action="{{ route('admin.expenses.reports.monthly') }}" class="flex items-end gap-4">
            <div>
                <label class="form-label">Select Month</label>
                <input type="month" name="month" value="{{ $monthInput }}" class="form-input" required>
            </div>
            <div>
                <x-button type="submit" variant="primary">Generate Summary</x-button>
            </div>
        </form>
    </div>
    <div class="ml-4">
        <a href="{{ route('admin.expenses.index') }}" class="btn-secondary">Back to Expenses</a>
    </div>
</div>

<div class="panel overflow-hidden">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Field Worker</th>
                    <th class="text-right">Travel Expenses (Auto)</th>
                    <th class="text-right">Manual Approved</th>
                    <th class="text-right">Total Approved</th>
                    <th class="text-right text-ink-500">Manual Pending</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summary as $row)
                <tr>
                    <td>
                        <span class="font-bold text-ink-900">{{ $row['user']->name }}</span>
                    </td>
                    <td class="text-right text-brand-700">₹{{ number_format($row['total_travel'], 2) }}</td>
                    <td class="text-right text-success-text">₹{{ number_format($row['total_manual_approved'], 2) }}</td>
                    <td class="text-right font-black text-ink-900 text-lg">₹{{ number_format($row['grand_total'], 2) }}</td>
                    <td class="text-right text-warning-text">₹{{ number_format($row['total_manual_pending'], 2) }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.expenses.reports.statement', ['user_id' => $row['user']->id, 'month' => $monthInput]) }}" class="btn-primary text-xs px-3 py-1 inline-flex items-center gap-1">
                            <x-lucide-file-text class="w-3 h-3" /> Statement
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-6 text-ink-500">No field workers found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
