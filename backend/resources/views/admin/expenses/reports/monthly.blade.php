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
        <table id="monthly-expense-table" class="data-table">
            <thead>
                <tr>
                    <th>Field Worker</th>
                    <th class="text-right">Travel (Auto)</th>
                    <th class="text-right">Final Approved</th>
                    <th class="text-right">Total Payable</th>
                    <th class="text-right text-blue-700">Verified (L1)</th>
                    <th class="text-right text-ink-500">Pending L1</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $allTravel = 0;
                    $allManualApproved = 0;
                    $allGrandTotal = 0;
                    $allManualVerified = 0;
                    $allManualPending = 0;
                @endphp
                @forelse($summary as $row)
                @php
                    $allTravel += $row['total_travel'];
                    $allManualApproved += $row['total_manual_approved'];
                    $allGrandTotal += $row['grand_total'];
                    $allManualVerified += $row['total_manual_verified'];
                    $allManualPending += $row['total_manual_pending'];
                @endphp
                <tr>
                    <td>
                        <span class="font-bold text-ink-900">{{ $row['user']->name }}</span>
                    </td>
                    <td class="text-right text-brand-700" data-order="{{ $row['total_travel'] }}">₹{{ number_format($row['total_travel'], 2) }}</td>
                    <td class="text-right text-success-text font-semibold" data-order="{{ $row['total_manual_approved'] }}">₹{{ number_format($row['total_manual_approved'], 2) }}</td>
                    <td class="text-right font-black text-ink-900 text-lg" data-order="{{ $row['grand_total'] }}">₹{{ number_format($row['grand_total'], 2) }}</td>
                    <td class="text-right text-blue-700 font-medium" data-order="{{ $row['total_manual_verified'] }}">
                        @if($row['total_manual_verified'] > 0)
                            <span class="px-2 py-0.5 rounded bg-blue-50 border border-blue-200">
                                ₹{{ number_format($row['total_manual_verified'], 2) }}
                            </span>
                        @else
                            <span class="text-ink-400">—</span>
                        @endif
                    </td>
                    <td class="text-right text-warning-text" data-order="{{ $row['total_manual_pending'] }}">
                        @if($row['total_manual_pending'] > 0)
                            ₹{{ number_format($row['total_manual_pending'], 2) }}
                        @else
                            <span class="text-ink-400">—</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <a href="{{ route('admin.expenses.reports.statement', ['user_id' => $row['user']->id, 'month' => $monthInput]) }}" class="btn-primary text-xs px-3 py-1 inline-flex items-center gap-1">
                            <x-lucide-file-text class="w-3 h-3" /> Statement
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-6 text-ink-500">No field workers found.</td>
                </tr>
                @endforelse
            </tbody>
            @if(count($summary) > 0)
            <tfoot class="bg-gray-50 font-bold border-t border-gray-200">
                <tr>
                    <td class="py-3 px-4 text-ink-900 font-extrabold uppercase">Total (All Workers):</td>
                    <td class="text-right text-brand-700">₹{{ number_format($allTravel, 2) }}</td>
                    <td class="text-right text-success-text">₹{{ number_format($allManualApproved, 2) }}</td>
                    <td class="text-right text-ink-900 text-xl font-black">₹{{ number_format($allGrandTotal, 2) }}</td>
                    <td class="text-right text-blue-700">₹{{ number_format($allManualVerified, 2) }}</td>
                    <td class="text-right text-warning-text">₹{{ number_format($allManualPending, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function getDT() {
            return window.DataTable || (typeof DataTable !== 'undefined' ? DataTable : null);
        }

        function initMonthlyDT() {
            const tableEl = document.getElementById('monthly-expense-table');
            if (!tableEl) return;
            const DT = getDT();
            if (!DT) return;

            const hasEmptyRow = tableEl.querySelector('tbody tr td[colspan]') !== null;
            if (!hasEmptyRow && tableEl.querySelectorAll('tbody tr').length > 0) {
                new DT('#monthly-expense-table', {
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                    order: [[3, 'desc']], // Default: Total Payable descending
                    columnDefs: [
                        { targets: [6], orderable: false, searchable: false }
                    ],
                    dom: '<"flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-3 border-b border-surface-border"<"text-xs text-ink-600 flex items-center gap-2"l><"text-xs"f>>rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>',
                    language: {
                        search: "",
                        searchPlaceholder: "Search worker...",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ field workers",
                        emptyTable: "No field workers found."
                    }
                });
            }
        }

        if (getDT()) {
            initMonthlyDT();
        } else {
            let tries = 0;
            const timer = setInterval(() => {
                tries++;
                if (getDT()) {
                    clearInterval(timer);
                    initMonthlyDT();
                } else if (tries > 50) {
                    clearInterval(timer);
                }
            }, 50);
        }
    });
</script>
@endpush