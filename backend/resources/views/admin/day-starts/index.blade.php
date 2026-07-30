@extends('admin.layouts.app')

@section('title', 'Day Starts')
@section('breadcrumb', 'ADMIN / DAY START')
@section('page_title', 'Day Start / Tour Plan')

@section('content')
<div class="panel">
    {{-- Header --}}
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">Day Start Records</h2>
                <p class="text-caption mt-1">View submitted tour plans for your scope.</p>
            </div>
            @can('day-start.create')
                <x-button href="{{ route('admin.day-starts.create') }}" variant="primary">
                    <x-lucide-plus class="w-4 h-4" />
                    Start My Day
                </x-button>
            @endcan
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.day-starts.index') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
            </div>
            @canany(['day-start.view-all', 'day-start.view-team'])
                <div>
                    <label class="form-label">Employee</label>
                    <select name="employee_id" class="form-input">
                        <option value="">All employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee['id'] ?? $employee->id }}" @selected(request('employee_id') == ($employee['id'] ?? $employee->id))>
                                {{ $employee['name'] ?? $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endcanany
            <div class="flex items-end gap-2">
                <x-button type="submit" variant="primary">
                    <x-lucide-filter class="w-4 h-4" />
                    Filter
                </x-button>
                <x-button href="{{ route('admin.day-starts.index') }}" variant="secondary">Reset</x-button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Tour Purpose</th>
                    <th>Opening KM</th>
                    <th>Closing KM</th>
                    <th>Status</th>
                    <th class="text-right">View</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dayStarts as $dayStart)
                    <tr>
                        <td class="text-body-emphasis">{{ $dayStart->user->name }}</td>
                        <td class="text-body">{{ $dayStart->date->format('d M Y') }}</td>
                        <td class="text-body">{{ $dayStart->tourPurpose->name }}</td>
                        <td class="text-body">{{ $dayStart->opening_km ?? '—' }}</td>
                        <td class="text-body">{{ $dayStart->closing_km ?? '—' }}</td>
                        <td>
                            <div class="flex flex-col gap-1">
                                <x-status-badge :status="$dayStart->status" :map="['approved' => 'success', 'flagged' => 'danger', 'submitted' => 'warning']" />
                                @if($dayStart->end_time)
                                    <span class="badge badge-neutral text-[10px]">Ended: {{ $dayStart->end_time }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-right">
                            <x-button href="{{ route('admin.day-starts.show', $dayStart->id) }}" variant="secondary" class="text-xs px-2.5 py-1.5">
                                <x-lucide-eye class="w-4 h-4" />
                                View
                            </x-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-inbox class="w-10 h-10" />
                                <p class="text-sm font-medium">No day start records found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($dayStarts->hasPages())
        <div class="border-t border-surface-border px-6 py-4">
            {{ $dayStarts->links() }}
        </div>
    @endif
</div>
@endsection
