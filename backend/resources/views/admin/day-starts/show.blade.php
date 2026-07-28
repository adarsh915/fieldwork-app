@extends('admin.layouts.app')

@section('title', 'Day Start Details')
@section('breadcrumb', 'Admin / Day Starts / Details')
@section('page_title', 'Day Start Details')

@section('content')
    <section class="panel">
        <div class="panel-header">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">{{ $dayStart->user->name }} — {{ $dayStart->date->format('d M Y') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Tour plan details</p>
                </div>
                <a href="{{ route('admin.day-starts.index') }}" class="btn-secondary">Back to List</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Employee</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $dayStart->user->name }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Date</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Tour Purpose</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->tourPurpose->name }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Tour Type</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->tourType->name }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Vehicle Type</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->vehicleType->name }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</div>
                    <div class="mt-1">
                        @php
                            $statusClass = match ($dayStart->status) {
                                'approved' => 'badge-success',
                                'flagged' => 'badge-danger',
                                default => 'badge-muted',
                            };
                        @endphp
                        <span class="{{ $statusClass }}">{{ ucfirst($dayStart->status) }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Visit Place</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->visit_place ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Party</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->party?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Vehicle No.</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->vehicle_no ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening KM</div>
                    <div class="mt-1 text-slate-900">{{ $dayStart->opening_km ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Location</div>
                    <div class="mt-1 text-slate-900">
                        @if ($dayStart->start_lat && $dayStart->start_lng)
                            {{ $dayStart->start_lat }}, {{ $dayStart->start_lng }}
                        @else
                            —
                        @endif
                    </div>
                </div>
                @if ($dayStart->opening_km_image)
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening KM Photo</div>
                        <img src="{{ asset('storage/' . $dayStart->opening_km_image) }}" alt="Opening KM" class="mt-2 max-h-64 rounded-lg border border-slate-200">
                    </div>
                @endif
            </div>
        </div>

        @if ($dayStart->day_end_at)
            <div class="border-t border-slate-200 p-5">
                <h3 class="font-semibold text-slate-950 mb-4">Day End Details</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Ended At</div>
                            <div class="mt-1 text-slate-900">{{ \Carbon\Carbon::parse($dayStart->day_end_at)->format('d M Y, h:i A') }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing KM</div>
                            <div class="mt-1 text-slate-900">{{ $dayStart->closing_km ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">End Location</div>
                            <div class="mt-1 text-slate-900">
                                @if ($dayStart->end_lat && $dayStart->end_lng)
                                    {{ $dayStart->end_lat }}, {{ $dayStart->end_lng }}
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Remarks</div>
                            <div class="mt-1 text-slate-900">{{ $dayStart->remarks ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        @if ($dayStart->closing_km_image)
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing KM Photo</div>
                                <img src="{{ asset('storage/' . $dayStart->closing_km_image) }}" alt="Closing KM" class="mt-2 max-h-64 rounded-lg border border-slate-200">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="border-t border-slate-200 p-5 bg-slate-50 rounded-b-lg">
                <p class="text-sm text-slate-500">This day has not been ended yet.</p>
            </div>
        @endif
    </section>
@endsection
