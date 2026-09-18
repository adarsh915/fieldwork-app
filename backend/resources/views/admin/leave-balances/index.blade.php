@extends('admin.layouts.app')

@section('title', 'Employee Leave Balances')
@section('breadcrumb', 'ADMIN / LEAVE BALANCES')
@section('page_title', 'Employee Leave Balances & Manual Adjustments')

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('success') }}</p>
    </div>
@endif

<div x-data="{ adjustModalOpen: false, editItem: {} }">

    {{-- Filter Panel --}}
    <div class="panel p-6 mb-6">
        <form method="GET" action="{{ route('admin.leave-balances.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="w-full md:w-1/4">
                <label class="form-label">Year</label>
                <select name="year" onchange="this.form.submit()" class="form-input">
                    @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="w-full md:w-1/3">
                <label class="form-label">Filter Employee</label>
                <select name="user_id" onchange="this.form.submit()" class="form-input">
                    <option value="">All Employees</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->employee_id ?? 'ID: ' . $u->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-1/4">
                <label class="form-label">Leave Type</label>
                <select name="leave_type_id" onchange="this.form.submit()" class="form-input">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>
                            {{ $lt->name }} ({{ $lt->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <x-button type="submit" variant="primary">Filter</x-button>

            @if(request()->anyFilled(['user_id', 'leave_type_id']))
                <a href="{{ route('admin.leave-balances.index', ['year' => $year]) }}" class="btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    {{-- Data Table Panel --}}
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Leave Type</th>
                        <th>Year</th>
                        <th class="text-center">Allocated</th>
                        <th class="text-center">Carry Forward</th>
                        <th class="text-center">Used</th>
                        <th class="text-center">Available</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $b)
                    <tr>
                        <td class="font-bold text-ink-900">
                            {{ $b->user->name ?? 'Unknown' }}
                            <span class="text-xs text-ink-400 font-normal block">{{ $b->user->employee_id ?? '' }}</span>
                        </td>
                        <td>
                            <span class="badge badge-primary font-mono font-bold">
                                {{ $b->leaveType->code ?? 'L' }} — {{ $b->leaveType->name ?? 'Leave' }}
                            </span>
                        </td>
                        <td class="font-medium text-ink-600">
                            {{ $b->year }}
                        </td>
                        <td class="text-center font-bold text-ink-900">
                            {{ $b->allocated }}
                        </td>
                        <td class="text-center text-blue-600 font-semibold">
                            {{ $b->carried_forward }}
                        </td>
                        <td class="text-center font-bold text-rose-600">
                            {{ $b->used }}
                        </td>
                        <td class="text-center font-extrabold text-emerald-600">
                            {{ $b->available }}
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <x-button type="button" variant="secondary" class="px-3 py-1 text-xs"
                                      @click="adjustModalOpen = true; editItem = { id: {{ $b->id }}, user_name: '{{ addslashes($b->user->name ?? 'Employee') }}', code: '{{ addslashes($b->leaveType->code ?? '') }}', allocated: {{ $b->allocated }}, used: {{ $b->used }}, carried_forward: {{ $b->carried_forward }} }">
                                <x-lucide-sliders-horizontal class="w-3.5 h-3.5 inline mr-1" /> Adjust Balance
                            </x-button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-6 text-ink-500">No leave balance records found for {{ $year }}.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($balances->hasPages())
            <div class="p-4 border-t border-surface-border">
                {{ $balances->links() }}
            </div>
        @endif
    </div>

    {{-- Adjust Balance Modal --}}
    <div x-show="adjustModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="adjustModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-ink-900">Adjust Leave Balance</h3>
                <button type="button" @click="adjustModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>
            <p class="text-xs text-ink-500 mb-4">
                Adjusting balance for <span class="font-bold text-ink-900" x-text="editItem.user_name"></span> (<span x-text="editItem.code"></span>).
            </p>
            <form :action="'{{ url('admin/leave-balances') }}/' + editItem.id" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="form-label">Allocated Days</label>
                    <input type="number" step="0.5" name="allocated" x-model="editItem.allocated" required class="form-input" />
                </div>
                <div class="mb-4">
                    <label class="form-label">Carry Forward Days</label>
                    <input type="number" step="0.5" name="carried_forward" x-model="editItem.carried_forward" required class="form-input" />
                </div>
                <div class="mb-6">
                    <label class="form-label">Used Days</label>
                    <input type="number" step="0.5" name="used" x-model="editItem.used" required class="form-input" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="adjustModalOpen = false">Cancel</x-button>
                    <x-button type="submit" variant="primary">Save Changes</x-button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
