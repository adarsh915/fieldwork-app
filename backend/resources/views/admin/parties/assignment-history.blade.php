@extends('admin.layouts.app')

@section('title', 'Assignment History - ' . $party->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-6xl mx-auto">

        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="p-4 bg-blue-100 rounded-lg">
                    <x-lucide-building-2 class="w-8 h-8 text-blue-600" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">{{ $party->name }}</h1>
                    <p class="text-slate-600 mt-1">Party Code: <span class="font-semibold">{{ $party->party_code ?? 'N/A' }}</span></p>
                </div>
            </div>

            <!-- Party Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg p-4 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-600 mb-1">Party Type</p>
                    <p class="font-semibold text-slate-900">{{ $party->party_type ?? '—' }}</p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-600 mb-1">Category</p>
                    <p class="font-semibold text-slate-900">
                        <span class="inline-block px-2 py-1 rounded-full text-sm font-bold"
                            @class([
                                'bg-red-100 text-red-700' => $party->category === 'A',
                                'bg-yellow-100 text-yellow-700' => $party->category === 'B',
                                'bg-green-100 text-green-700' => $party->category === 'C',
                            ])>
                            {{ $party->category ?? '—' }}
                        </span>
                    </p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-600 mb-1">City</p>
                    <p class="font-semibold text-slate-900">{{ $party->city ?? '—' }}</p>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-600 mb-1">Status</p>
                    <p>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold"
                            @class([
                                'bg-green-100 text-green-700' => $party->is_active,
                                'bg-slate-100 text-slate-700' => !$party->is_active,
                            ])>
                            {{ $party->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Current Assignment Section -->
        @if($history->where('is_active', true)->first())
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-md border-l-4 border-green-500 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
                        <div class="flex items-center gap-3">
                            <x-lucide-check-circle class="w-6 h-6 text-green-600" />
                            <h2 class="text-xl font-bold text-green-900">Current Assignment</h2>
                        </div>
                    </div>
                    
                    @php
                        $current = $history->where('is_active', true)->first();
                    @endphp
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Employee Info -->
                            <div>
                                <p class="text-sm text-slate-600 mb-2">Assigned To</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                        {{ strtoupper(substr($current->employee->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $current->employee->name }} ({{ $current->employee->role_name }})</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Date -->
                            <div>
                                <p class="text-sm text-slate-600 mb-2">Assigned On</p>
                                <p class="font-semibold text-slate-900">{{ $current->assigned_at->format('M d, Y') }}</p>
                                <p class="text-sm text-slate-600">{{ $current->assigned_at->format('h:i A') }}</p>
                            </div>

                            <!-- Duration -->
                            <div>
                                <p class="text-sm text-slate-600 mb-2">Duration</p>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-bold text-blue-600">{{ $current->assigned_at->diffInDays(now()) }}</p>
                                    <p class="text-slate-600">days (Active)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned By -->
                        <div class="mt-6 pt-6 border-t border-slate-200">
                            <p class="text-sm text-slate-600 mb-2">Assigned By</p>
                            <p class="font-semibold text-slate-900">{{ $current->assignedBy ? $current->assignedBy->name . ' (' . $current->assignedBy->role_name . ')' : 'System' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Assignment History Timeline -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-3">
                <x-lucide-history class="w-6 h-6 text-slate-600" />
                Assignment History
            </h2>

            @if($history->count() > 0)
                <div class="space-y-0">
                    @foreach($history as $index => $assignment)
                        <div class="bg-white border border-slate-200 @if($index === 0) rounded-t-lg @elseif($loop->last) rounded-b-lg border-t-0 @else border-t-0 @endif overflow-hidden hover:shadow-md transition-shadow">
                            <div class="p-6 border-l-4 @class([
                                'border-green-500 bg-green-50' => $assignment->is_active,
                                'border-slate-300 hover:bg-slate-50' => !$assignment->is_active,
                            ])">
                                <div class="flex items-start justify-between mb-4">
                                    <!-- Assignment Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-10 h-10 rounded-full @class([
                                                'bg-green-100' => $assignment->is_active,
                                                'bg-slate-100' => !$assignment->is_active,
                                            ]) flex items-center justify-center">
                                                <span class="text-sm font-bold @class([
                                                    'text-green-600' => $assignment->is_active,
                                                    'text-slate-600' => !$assignment->is_active,
                                                ])">
                                                    {{ strtoupper(substr($assignment->employee->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ $assignment->employee->name }} ({{ $assignment->employee->role_name }})</p>
                                            </div>
                                            @if($assignment->is_active)
                                                <span class="inline-flex items-center gap-1 ml-auto px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-semibold">
                                                    <x-lucide-circle-check-big class="w-4 h-4" />
                                                    Active
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Timeline -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 ml-13">
                                            <!-- Assigned Date -->
                                            <div>
                                                <p class="text-xs text-slate-600 mb-1">ASSIGNED ON</p>
                                                <p class="font-semibold text-slate-900">{{ $assignment->assigned_at->format('M d, Y') }}</p>
                                                <p class="text-sm text-slate-600">{{ $assignment->assigned_at->format('h:i A') }}</p>
                                            </div>

                                            <!-- Unassigned Date -->
                                            <div>
                                                <p class="text-xs text-slate-600 mb-1">UNASSIGNED ON</p>
                                                @if($assignment->unassigned_at)
                                                    <p class="font-semibold text-slate-900">{{ $assignment->unassigned_at->format('M d, Y') }}</p>
                                                    <p class="text-sm text-slate-600">{{ $assignment->unassigned_at->format('h:i A') }}</p>
                                                @else
                                                    <p class="text-slate-600 italic">—</p>
                                                @endif
                                            </div>

                                            <!-- Duration -->
                                            <div>
                                                <p class="text-xs text-slate-600 mb-1">DURATION</p>
                                                @php
                                                    $durationDays = $assignment->unassigned_at 
                                                        ? $assignment->assigned_at->diffInDays($assignment->unassigned_at)
                                                        : $assignment->assigned_at->diffInDays(now());
                                                @endphp
                                                <p class="font-semibold text-slate-900">{{ $durationDays }} days</p>
                                                @if($assignment->unassigned_at)
                                                    <p class="text-sm text-slate-600">Completed</p>
                                                @else
                                                    <p class="text-sm text-slate-600">Ongoing</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assigned By Section -->
                                <div class="ml-13 pt-4 border-t border-slate-200">
                                    <p class="text-xs text-slate-600 mb-1">ASSIGNED BY</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ $assignment->assignedBy ? $assignment->assignedBy->name . ' (' . $assignment->assignedBy->role_name . ')' : 'System' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
                    <x-lucide-inbox class="w-16 h-16 text-slate-300 mx-auto mb-4" />
                    <p class="text-slate-600 mb-2">No assignment history found</p>
                    <p class="text-sm text-slate-500">This party has not been assigned to any field worker yet.</p>
                </div>
            @endif
        </div>

        <!-- Stats Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <p class="text-sm text-slate-600 mb-2">Total Assignments</p>
                <p class="text-3xl font-bold text-slate-900">{{ $history->count() }}</p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <p class="text-sm text-slate-600 mb-2">Total Field Workers Assigned</p>
                <p class="text-3xl font-bold text-slate-900">{{ $history->pluck('employee_id')->unique()->count() }}</p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <p class="text-sm text-slate-600 mb-2">Average Assignment Duration</p>
                @php
                    $avgDays = $history->filter(fn($h) => $h->unassigned_at)->avg(fn($h) => $h->assigned_at->diffInDays($h->unassigned_at));
                @endphp
                <p class="text-3xl font-bold text-slate-900">{{ round($avgDays, 1) }} days</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <a href="{{ route('admin.parties.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                Back to Parties
            </a>
            <a href="{{ route('admin.parties.show', $party->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <x-lucide-eye class="w-4 h-4" />
                View Profile
            </a>
        </div>
    </div>
</div>

<style>
    .ml-13 {
        margin-left: 3.25rem;
    }
</style>
@endsection
