@extends('admin.layouts.app')

@section('title', 'Daily Receipt')
@section('breadcrumb', 'ADMIN / EXPENSES / DAILY RECEIPT')
@section('page_title', 'Daily Expense Receipt')

@section('content')
<div x-data="{ lightboxOpen: false, lightboxImage: '' }">
    <!-- Lightbox Modal -->
    <div x-show="lightboxOpen" style="display: none; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.9); backdrop-filter: blur(4px);" x-transition>
        <button type="button" @click="lightboxOpen = false" style="position: absolute; top: 2rem; right: 2rem; color: white; background: none; border: none; cursor: pointer; z-index: 10000;">
            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <img :src="lightboxImage" style="max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); margin: auto; display: block;" @click.away="lightboxOpen = false">
    </div>

    <div class="mb-4">
        <a href="{{ url()->previous() }}" class="btn-secondary text-sm px-3 py-1.5 inline-flex items-center gap-2">
            <x-lucide-arrow-left class="w-4 h-4" /> Back
        </a>
    </div>

{{-- Top Section: Day Start Overview --}}
<div class="panel p-6 mb-6 border-t-4 border-brand-500">
    <div class="flex flex-wrap justify-between items-start mb-6 border-b border-surface-border pb-4">
        <div>
            <h2 class="text-xl font-black text-ink-900">{{ $dayStart->user->name }}</h2>
            <p class="text-ink-500 text-sm mt-1">Date: <span class="font-semibold text-ink-900">{{ $dayStart->date->format('d M Y') }}</span></p>
        </div>
        <div class="text-right">
            <p class="text-sm text-ink-500">Vehicle Type</p>
            <p class="font-bold text-ink-900">{{ $dayStart->vehicleType->name ?? 'N/A' }} ({{ $dayStart->vehicle_no ?? 'No Plate' }})</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        {{-- Opening KM --}}
        <div>
            <h3 class="text-sm font-bold text-ink-900 uppercase tracking-wider mb-2">Opening KM</h3>
            <div class="flex items-start gap-4">
                <div class="bg-surface-muted p-4 rounded-xl flex-1 text-center">
                    <p class="text-2xl font-black text-brand-600">{{ $dayStart->opening_km ?? '—' }}</p>
                    <p class="text-xs text-ink-500 mt-1">Time: {{ $dayStart->start_time }}</p>
                </div>
                @if($dayStart->opening_km_photo)
                <button type="button" @click="lightboxImage = '{{ asset('storage/' . $dayStart->opening_km_photo) }}'; lightboxOpen = true;" class="w-24 h-24 rounded-lg overflow-hidden border border-surface-border block hover:opacity-90 transition">
                    <img src="{{ asset('storage/' . $dayStart->opening_km_photo) }}" class="w-full h-full object-cover" alt="Opening KM Photo">
                </button>
                @else
                <div class="w-24 h-24 rounded-lg bg-surface-muted flex items-center justify-center border border-surface-border border-dashed">
                    <span class="text-xs text-ink-400">No Photo</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Closing KM --}}
        <div>
            <h3 class="text-sm font-bold text-ink-900 uppercase tracking-wider mb-2">Closing KM</h3>
            <div class="flex items-start gap-4">
                <div class="bg-surface-muted p-4 rounded-xl flex-1 text-center">
                    <p class="text-2xl font-black text-brand-600">{{ $dayStart->closing_km ?? '—' }}</p>
                    <p class="text-xs text-ink-500 mt-1">Time: {{ $dayStart->end_time ?? '—' }}</p>
                </div>
                @if($dayStart->closing_km_photo)
                <button type="button" @click="lightboxImage = '{{ asset('storage/' . $dayStart->closing_km_photo) }}'; lightboxOpen = true;" class="w-24 h-24 rounded-lg overflow-hidden border border-surface-border block hover:opacity-90 transition">
                    <img src="{{ asset('storage/' . $dayStart->closing_km_photo) }}" class="w-full h-full object-cover" alt="Closing KM Photo">
                </button>
                @else
                <div class="w-24 h-24 rounded-lg bg-surface-muted flex items-center justify-center border border-surface-border border-dashed">
                    <span class="text-xs text-ink-400">No Photo</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Total Distance --}}
        <div>
            <h3 class="text-sm font-bold text-ink-900 uppercase tracking-wider mb-2">Total Distance</h3>
            <div class="flex items-start gap-4">
                <div class="bg-surface-muted p-4 rounded-xl flex-1 text-center h-24 flex flex-col justify-center border border-brand-200 bg-brand-50">
                    <p class="text-3xl font-black text-brand-600">
                        @if($dayStart->closing_km && $dayStart->opening_km)
                            {{ max(0, $dayStart->closing_km - $dayStart->opening_km) }} KM
                        @else
                            —
                        @endif
                    </p>
                    <p class="text-xs text-brand-500 mt-1 uppercase tracking-wide font-bold">Travelled</p>
                </div>
            </div>
        </div>
    </div>

    @if($dayStart->flagged_reason)
    <div class="mt-6 bg-danger-bg border border-red-200 rounded-xl p-4 flex gap-3">
        <x-lucide-alert-triangle class="w-5 h-5 text-danger-text shrink-0" />
        <div>
            <p class="text-sm font-bold text-danger-text">System Flagged</p>
            <p class="text-sm text-danger-text mt-1">{{ $dayStart->flagged_reason }}</p>
        </div>
    </div>
    @endif
</div>

{{-- Middle Section: Visited Parties --}}
<div class="panel p-6 mb-6">
    <h3 class="text-lg font-bold text-ink-900 mb-4 flex items-center gap-2">
        <x-lucide-map-pin class="w-5 h-5 text-brand-500" /> Parties Visited Today
    </h3>
    @if($visits->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($visits as $visit)
                <div class="border border-surface-border rounded-xl p-4 flex gap-3">
                    <div class="bg-brand-50 p-2 rounded-lg h-fit">
                        <x-lucide-store class="w-5 h-5 text-brand-600" />
                    </div>
                    <div>
                        <p class="font-bold text-ink-900 line-clamp-1" title="{{ $visit->party->name ?? 'Unknown' }}">{{ $visit->party->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-ink-500 mt-1">In: {{ $visit->check_in_time ? $visit->check_in_time->format('H:i') : '--' }} | Out: {{ $visit->check_out_time ? $visit->check_out_time->format('H:i') : '--' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-ink-500 italic">No parties visited today.</p>
    @endif
</div>

{{-- Bottom Section: Expenses --}}
<div class="panel p-6">
    <h3 class="text-lg font-bold text-ink-900 mb-4 flex items-center gap-2">
        <x-lucide-receipt class="w-5 h-5 text-brand-500" /> Expense Breakdown
    </h3>
    
    <div class="table-wrap mb-4">
        <table class="data-table">
            <thead>
                <tr class="border-b border-slate-200">
                    <th style="text-align: left; padding: 12px 16px;">Type / Category</th>
                    <th style="text-align: center; padding: 12px 16px;">Timing</th>
                    <th style="text-align: center; padding: 12px 16px;">Status</th>
                    <th style="text-align: center; padding: 12px 16px;">Photo Proof</th>
                    <th style="text-align: right; padding: 12px 16px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $totalAmount = 0; @endphp
                @forelse($expenses as $expense)
                    @php 
                        if ($expense->status === 'approved' || ($expense->is_system_generated && $expense->status !== 'rejected')) {
                            $totalAmount += $expense->amount; 
                        }
                    @endphp
                    <tr>
                        <td style="text-align: left; padding: 12px 16px;">
                            @if($expense->is_system_generated)
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-primary">Auto Travel</span>
                                    <span class="text-xs text-ink-500">(Calculated from Meter)</span>
                                </div>
                            @else
                                <span class="font-semibold text-ink-900">{{ $expense->category->name ?? 'Manual' }}</span>
                                @if($expense->items->count() > 0)
                                    <div class="mt-1 pl-2 border-l-2 border-brand-200 text-xs text-ink-500 space-y-0.5 text-left" style="text-align: left;">
                                        @foreach($expense->items as $item)
                                            <div style="text-align: left;">• {{ $item->item_name }} ({{ $item->quantity }} × ₹{{ number_format($item->unit_price, 2) }})</div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td style="text-align: center; padding: 12px 16px; vertical-align: middle;">
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
                        <td style="text-align: center; padding: 12px 16px; vertical-align: middle;">
                            @if($expense->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @elseif($expense->status === 'approved')
                                <span class="badge badge-success">Approved</span>
                            @else
                                <span class="badge badge-danger">Rejected</span>
                            @endif
                        </td>
                        <td style="text-align: center; padding: 12px 16px; vertical-align: middle;">
                            @if($expense->bill_photo)
                                <button type="button" @click="lightboxImage = '{{ asset('storage/' . $expense->bill_photo) }}'; lightboxOpen = true;" class="inline-block w-16 h-12 rounded border border-slate-300 overflow-hidden hover:opacity-90 transition shadow-sm">
                                    <img src="{{ asset('storage/' . $expense->bill_photo) }}" class="w-full h-full object-cover" alt="Bill Photo">
                                </button>
                            @elseif($expense->is_system_generated)
                                <span class="text-xs text-ink-400 italic">Odometer photos above</span>
                            @else
                                <span class="text-xs text-ink-400 italic">No Photo</span>
                            @endif
                        </td>
                        <td style="text-align: right; padding: 12px 16px; font-weight: bold; vertical-align: middle;" class="text-ink-900">₹{{ number_format($expense->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-ink-500">No expenses recorded for this day.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end pt-4 border-t border-surface-border">
        <div class="text-right">
            <p class="text-sm text-ink-500 uppercase tracking-wider font-bold mb-1">Total (Approved & Auto)</p>
            <p class="text-3xl font-black text-brand-600">₹{{ number_format($totalAmount, 2) }}</p>
        </div>
    </div>
    </div>
</div>
@endsection
