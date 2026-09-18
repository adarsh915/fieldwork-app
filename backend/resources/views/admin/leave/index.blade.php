@extends('admin.layouts.app')

@section('title', 'Leave Management')
@section('breadcrumb', 'ADMIN / LEAVE')
@section('page_title', 'Leave Management')

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('success') }}</p>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-danger-bg px-4 py-3">
        <x-lucide-alert-circle class="w-5 h-5 text-danger-text shrink-0" />
        <p class="text-sm font-medium text-danger-text">{{ session('error') }}</p>
    </div>
@endif

{{-- Stats Row --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="panel p-5 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Pending Requests</p>
            <h3 class="text-2xl font-extrabold text-amber-600 mt-1">{{ $pendingCount }}</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 border border-amber-200">
            <x-lucide-clock class="w-6 h-6" />
        </div>
    </div>

    <div class="panel p-5 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Approved Leaves</p>
            <h3 class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $approvedCount }}</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-200">
            <x-lucide-check-circle class="w-6 h-6" />
        </div>
    </div>

    <div class="panel p-5 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Rejected Requests</p>
            <h3 class="text-2xl font-extrabold text-rose-600 mt-1">{{ $rejectedCount }}</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-200">
            <x-lucide-x-circle class="w-6 h-6" />
        </div>
    </div>

    <div class="panel p-5 flex items-center justify-between {{ $conflictCount > 0 ? 'border-2 border-rose-400 bg-rose-50/20' : '' }}">
        <div>
            <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Work Conflicts</p>
            <div class="flex items-center gap-2 mt-1">
                <h3 class="text-2xl font-extrabold {{ $conflictCount > 0 ? 'text-rose-600' : 'text-ink-700' }}">{{ $conflictCount }}</h3>
                @if($conflictCount > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 animate-pulse">Needs Review</span>
                @endif
            </div>
        </div>
        <div class="w-12 h-12 rounded-xl {{ $conflictCount > 0 ? 'bg-rose-100 text-rose-600 border border-rose-300' : 'bg-surface-subtle text-ink-500 border border-surface-border' }} flex items-center justify-center">
            <x-lucide-alert-triangle class="w-6 h-6" />
        </div>
    </div>
</div>

{{-- Navigation Tabs --}}
<div class="flex border-b border-surface-border mb-6">
    <a href="{{ route('admin.leave.index', ['view' => 'applications']) }}" 
       class="px-5 py-3 font-semibold text-sm border-b-2 {{ request('view') !== 'conflicts' ? 'border-primary-600 text-primary-600' : 'border-transparent text-ink-500 hover:text-ink-800' }}">
        📋 Leave Applications
    </a>
    <a href="{{ route('admin.leave.index', ['view' => 'conflicts']) }}" 
       class="px-5 py-3 font-semibold text-sm border-b-2 flex items-center gap-2 {{ request('view') === 'conflicts' ? 'border-rose-600 text-rose-600' : 'border-transparent text-ink-500 hover:text-ink-800' }}">
        ⚠️ Work Conflicts
        @if($conflictCount > 0)
            <span class="bg-rose-600 text-white text-xs px-2 py-0.5 rounded-full font-bold">{{ $conflictCount }}</span>
        @endif
    </a>
</div>

@if(request('view') === 'conflicts')

{{-- Conflicts Sub-View --}}
<div class="panel p-6 mb-6" x-data="{ resolveModalOpen: false, conflictId: null, conflictEmp: '', conflictDate: '', conflictLeaveDates: '' }">
    <div class="flex justify-between items-center mb-5 pb-3 border-b border-surface-border">
        <div>
            <h2 class="text-lg font-bold text-ink-900">Attendance Work Conflicts</h2>
            <p class="text-xs text-ink-500">Flagged when an employee performs Day Start on a date falling within an approved leave range.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.leave.index', ['view' => 'conflicts', 'conflict_tab' => 'pending']) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold {{ request('conflict_tab', 'pending') === 'pending' ? 'bg-rose-600 text-white' : 'bg-surface-subtle text-ink-600' }}">
                Pending Review ({{ $conflictCount }})
            </a>
            <a href="{{ route('admin.leave.index', ['view' => 'conflicts', 'conflict_tab' => 'resolved']) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold {{ request('conflict_tab') === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-surface-subtle text-ink-600' }}">
                Resolved History
            </a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Approved Leave Range</th>
                    <th>Conflict Date (DayStart)</th>
                    <th>Status</th>
                    <th>Resolution</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conflicts as $c)
                <tr>
                    <td class="font-semibold text-ink-900">{{ $c->user->name ?? 'Unknown' }}</td>
                    <td>
                        <span class="badge badge-primary font-mono font-bold">{{ $c->leaveType->code ?? 'L' }}</span>
                    </td>
                    <td class="whitespace-nowrap text-sm text-ink-600">
                        {{ $c->from_date->format('d M Y') }} → {{ $c->to_date->format('d M Y') }}
                    </td>
                    <td class="whitespace-nowrap font-bold text-rose-600">
                        🗓️ {{ \Carbon\Carbon::parse($c->conflict_date)->format('d M Y') }}
                    </td>
                    <td>
                        @if($c->conflict_status === 'pending_review')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                ⚠️ Pending Review
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                ✅ Resolved
                            </span>
                        @endif
                    </td>
                    <td class="max-w-xs text-xs text-ink-700">
                        @if($c->conflict_status === 'resolved')
                            <div class="font-bold {{ $c->resolved_action === 'marked_present' ? 'text-emerald-700' : 'text-ink-800' }}">
                                {{ $c->resolved_action === 'marked_present' ? 'Marked Present & Refunded' : 'Kept as Leave' }}
                            </div>
                            <div class="text-ink-500 italic mt-0.5">"{{ $c->resolution_note }}"</div>
                            <div class="text-[10px] text-ink-400 mt-0.5">By {{ $c->resolvedBy->name ?? 'Admin' }} on {{ $c->resolved_at?->format('d M Y H:i') }}</div>
                        @else
                            <span class="text-ink-400 font-italic">Awaiting Admin Action</span>
                        @endif
                    </td>
                    <td class="text-right whitespace-nowrap">
                        @if($c->conflict_status === 'pending_review')
                            <button type="button" 
                                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow-sm"
                                    @click="resolveModalOpen = true; conflictId = {{ $c->id }}; conflictEmp = '{{ addslashes($c->user->name ?? 'Employee') }}'; conflictDate = '{{ \Carbon\Carbon::parse($c->conflict_date)->format('d M Y') }}'; conflictLeaveDates = '{{ $c->from_date->format('d M Y') }} to {{ $c->to_date->format('d M Y') }}'">
                                ⚖️ Resolve Conflict
                            </button>
                        @else
                            <span class="text-xs text-ink-400">Completed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-ink-500">
                        No work conflicts found in this category.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Conflict Resolution Modal --}}
    <div x-show="resolveModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="resolveModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-left border border-surface-border">
            <div class="flex items-center justify-between mb-4 border-b border-surface-border pb-3">
                <h3 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    ⚠️ Resolve Attendance Conflict
                </h3>
                <button type="button" @click="resolveModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 mb-4 text-xs text-rose-900">
                <p><strong>Employee:</strong> <span x-text="conflictEmp"></span></p>
                <p><strong>Conflict Date (DayStart):</strong> <span x-text="conflictDate"></span></p>
                <p><strong>Approved Leave Range:</strong> <span x-text="conflictLeaveDates"></span></p>
            </div>

            <form :action="'{{ url('admin/leave') }}/' + conflictId + '/resolve-conflict'" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label class="form-label font-bold text-ink-900">Choose Resolution Action <span class="text-rose-500">*</span></label>
                    <div class="space-y-2 mt-2">
                        <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer hover:bg-surface-subtle transition">
                            <input type="radio" name="resolved_action" value="marked_present" required class="mt-1 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="block text-xs font-bold text-emerald-700">Mark Present & Refund Leave Day</span>
                                <span class="block text-[11px] text-ink-500">Overrides calendar status to Present for this date and refunds 1 day back to employee leave balance.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer hover:bg-surface-subtle transition">
                            <input type="radio" name="resolved_action" value="kept_as_leave" required class="mt-1 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="block text-xs font-bold text-ink-800">Keep as Approved Leave</span>
                                <span class="block text-[11px] text-ink-500">No balance refund. Select if Day Start was accidental or unapproved.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label font-bold text-ink-900">Resolution Note / Reason <span class="text-rose-500">*</span></label>
                    <textarea name="resolution_note" required rows="3" class="form-input text-xs" placeholder="Type explicit justification for this decision (e.g. Employee performed full field visit on 04 Sep, verified with manager)..."></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="resolveModalOpen = false">Cancel</x-button>
                    <x-button type="submit" variant="primary">Confirm & Apply Resolution</x-button>
                </div>
            </form>
        </div>
    </div>
</div>

@else

{{-- Standard Leave Applications Table --}}
<div class="panel p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-ink-900">Filter Leave Requests</h2>
    </div>
    <form method="GET" action="{{ route('admin.leave.index') }}" class="flex flex-wrap gap-4 items-end">
        <div class="w-full md:w-1/4">
            <label class="form-label">Employee</label>
            <select name="user_id" class="form-input">
                <option value="">All Employees</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full md:w-1/4">
            <label class="form-label">Status</label>
            <select name="status" class="form-input">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div class="flex gap-2">
            <x-button type="submit" variant="primary">Filter</x-button>
            @if(request()->anyFilled(['status', 'user_id']))
                <a href="{{ route('admin.leave.index') }}" class="btn-secondary">Reset</a>
            @endif
        </div>
    </form>
</div>

{{-- Data Table Panel --}}
<div class="panel overflow-hidden" x-data="{ rejectModalOpen: false, activeLeaveId: null, employeeName: '' }">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Date Range</th>
                    <th>Duration / Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                <tr>
                    <td class="font-semibold text-ink-900">
                        {{ $leave->user->name ?? 'Unknown' }}
                    </td>
                    <td>
                        <span class="badge badge-primary font-mono font-bold">
                            {{ $leave->leaveType->code ?? 'L' }} — {{ $leave->leaveType->name ?? 'Leave' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap text-sm text-ink-600">
                        {{ $leave->from_date->format('d M Y') }} → {{ $leave->to_date->format('d M Y') }}
                    </td>
                    <td class="whitespace-nowrap">
                        <span class="font-medium text-ink-900 capitalize">{{ str_replace('_', ' ', $leave->duration) }}</span>
                        <span class="text-ink-500 text-xs">({{ $leave->days_count }} {{ $leave->days_count == 1 ? 'day' : 'days' }})</span>
                    </td>
                    <td class="max-w-xs text-sm text-ink-700">
                        <div>{{ $leave->reason ?? '-' }}</div>
                        @if($leave->status === 'rejected' && $leave->rejected_reason)
                            <div class="text-xs text-rose-600 font-semibold mt-1">Rejection: {{ $leave->rejected_reason }}</div>
                        @endif
                    </td>
                    <td>
                        @if($leave->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @elseif($leave->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($leave->status === 'cancellation_requested')
                            <span class="badge badge-warning" style="background-color: #f59e0b20; color: #d97706; border: 1px solid #f59e0b40; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">Cancel Requested</span>
                        @elseif($leave->status === 'cancelled')
                            <span class="badge" style="background-color: #64748b20; color: #64748b; border: 1px solid #64748b40; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">Cancelled</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td class="text-right whitespace-nowrap">
                        @if($leave->status === 'pending')
                        <div class="flex gap-2 justify-end items-center">
                            {{-- Approve Button --}}
                            <form action="{{ route('admin.leave.approve', $leave->id) }}" method="POST" onsubmit="return confirm('Approve leave for {{ addslashes($leave->user->name ?? 'employee') }}?')">
                                @csrf
                                <x-button type="submit" variant="success" class="px-3 py-1 text-xs">
                                    <x-lucide-check class="w-3.5 h-3.5 inline mr-1" /> Approve
                                </x-button>
                            </form>

                            {{-- Reject Button --}}
                            <x-button type="button" variant="danger" class="px-3 py-1 text-xs"
                                      @click="rejectModalOpen = true; activeLeaveId = {{ $leave->id }}; employeeName = '{{ addslashes($leave->user->name ?? 'employee') }}'">
                                <x-lucide-x class="w-3.5 h-3.5 inline mr-1" /> Reject
                            </x-button>
                        </div>
                        @elseif(in_array($leave->status, ['approved', 'cancellation_requested']))
                        <div class="flex gap-2 justify-end items-center">
                            <form action="{{ route('admin.leave.cancel', $leave->id) }}" method="POST" onsubmit="return confirm('Cancel approved leave for {{ addslashes($leave->user->name ?? 'employee') }}? This will refund {{ $leave->days_count }} day(s) back to employee balance.')">
                                @csrf
                                <x-button type="submit" variant="warning" class="px-3 py-1 text-xs" style="background-color: #f59e0b; color: #fff; border: none;">
                                    <x-lucide-rotate-ccw class="w-3.5 h-3.5 inline mr-1" /> Cancel & Refund
                                </x-button>
                            </form>
                        </div>
                        @else
                            <span class="text-xs text-ink-500">Processed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-6 text-ink-500">No leave requests found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leaves->hasPages())
        <div class="p-4 border-t border-surface-border">
            {{ $leaves->links() }}
        </div>
    @endif

    {{-- Rejection Modal --}}
    <div x-show="rejectModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="rejectModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-ink-900">Reject Leave Application</h3>
                <button type="button" @click="rejectModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>
            <p class="text-sm text-ink-600 mb-4">
                Please provide a reason for rejecting leave for <span class="font-bold text-ink-900" x-text="employeeName"></span>.
            </p>
            <form :action="'{{ url('admin/leave') }}/' + activeLeaveId + '/reject'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Rejection Reason <span class="text-rose-500">*</span></label>
                    <textarea name="rejected_reason" required rows="3" class="form-input" placeholder="e.g. High workload scheduled for these dates..."></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="rejectModalOpen = false">Cancel</x-button>
                    <x-button type="submit" variant="danger">Confirm Rejection</x-button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif

@endsection
