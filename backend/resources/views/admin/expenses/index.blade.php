@extends('admin.layouts.app')

@section('title', 'Expenses')
@section('breadcrumb', 'ADMIN / EXPENSES')
@section('page_title', 'Expense Tracking')

@section('content')
<div class="panel p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-ink-900">Expense Filter</h2>
        <a href="{{ route('admin.expenses.reports.monthly') }}" class="btn-primary flex items-center gap-2">
            <x-lucide-calendar class="w-4 h-4" /> Monthly Summary
        </a>
    </div>
    <form method="GET" action="{{ route('admin.expenses.index') }}" class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-1/2 md:w-1/5">
            <label class="form-label">Field Worker</label>
            <select name="user_id" class="form-input">
                <option value="">All Workers</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-1/2 md:w-1/5">
            <label class="form-label">Status</label>
            <select name="status" class="form-input">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="w-full sm:w-1/2 md:w-1/5">
            <label class="form-label">Submission Timing</label>
            <select name="timing" class="form-input">
                <option value="">All Timings</option>
                <option value="during_shift" {{ request('timing') == 'during_shift' ? 'selected' : '' }}>During Shift</option>
                <option value="after_day_end" {{ request('timing') == 'after_day_end' ? 'selected' : '' }}>Post Day-End / Backdated</option>
            </select>
        </div>
        <div class="w-full sm:w-1/2 md:w-1/5">
            <label class="form-label">Month</label>
            <input type="month" name="month" value="{{ request('month') }}" class="form-input">
        </div>
        <div>
            <x-button type="submit" variant="primary">Filter</x-button>
        </div>
    </form>
</div>

<div class="panel overflow-hidden">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Worker</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Timing</th>
                    <th>Photo</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->date->format('d M Y') }}</td>
                    <td>
                        <span class="font-medium text-ink-900">{{ $expense->user->name }}</span>
                    </td>
                    <td>
                        @if($expense->is_system_generated)
                            <span class="badge badge-primary">System Travel</span>
                        @else
                            {{ $expense->category->name ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="font-bold">₹{{ number_format($expense->amount, 2) }}</td>
                    <td>
                        @if($expense->is_after_day_end)
                            <span class="badge badge-warning inline-flex items-center gap-1" title="Submitted after day end or backdated">
                                ⚠️ After Day End
                            </span>
                        @else
                            <span class="badge badge-success inline-flex items-center gap-1" title="Submitted during active shift">
                                ✓ During Shift
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($expense->bill_photo)
                            <a href="{{ asset('storage/' . $expense->bill_photo) }}" target="_blank" class="text-brand-600 underline text-sm">View Proof</a>
                        @elseif($expense->is_system_generated)
                            <span class="text-ink-500 text-sm italic">Check Day Receipt</span>
                        @else
                            <span class="text-ink-400 text-sm">No Photo</span>
                        @endif
                    </td>
                    <td>
                        @if($expense->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @elseif($expense->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($expense->day_start_id)
                            <a href="{{ route('admin.expenses.reports.daily', $expense->day_start_id) }}" class="btn-secondary px-2 py-1 text-xs mb-2 block text-center" title="View Full Day Receipt">View Day</a>
                        @endif
                        @if($expense->status === 'pending')
                        <div class="flex gap-2 justify-end">
                            <form action="{{ route('admin.expenses.updateStatus', $expense->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <x-button type="submit" variant="success" class="px-2 py-1 text-xs">Approve</x-button>
                            </form>
                            <form action="{{ route('admin.expenses.updateStatus', $expense->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <x-button type="submit" variant="danger" class="px-2 py-1 text-xs">Reject</x-button>
                            </form>
                        </div>
                        @else
                            <span class="text-xs text-ink-500">Processed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-6 text-ink-500">No expenses found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-surface-border">
        {{ $expenses->links() }}
    </div>
</div>
@endsection
