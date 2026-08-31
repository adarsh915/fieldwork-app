@extends('admin.layouts.app')

@section('title', 'Day Start Details')
@section('breadcrumb', 'Admin / Day Starts / Details')
@section('page_title', 'Day Start Details')

@section('content')
<div x-data="{ lightboxOpen: false, lightboxImage: '' }">
    <!-- Lightbox Modal -->
    <div x-show="lightboxOpen" style="display: none; position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.9); backdrop-filter: blur(4px);" x-transition>
        <button type="button" @click="lightboxOpen = false" style="position: absolute; top: 2rem; right: 2rem; color: white; background: none; border: none; cursor: pointer; z-index: 10000;">
            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <img :src="lightboxImage" style="max-width: 90vw; max-height: 90vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); margin: auto; display: block;" @click.away="lightboxOpen = false">
    </div>

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
                    <div class="mt-1 flex items-center gap-2">
                        @php
                            $statusClass = match ($dayStart->status) {
                                'approved' => 'badge-success',
                                'flagged', 'rejected' => 'badge-danger',
                                default => 'badge-muted',
                            };
                        @endphp
                        <span class="{{ $statusClass }}">{{ ucfirst($dayStart->status) }}</span>
                        
                        @if($dayStart->status === 'pending' && auth()->user()->can('day-start.approve'))
                            <div class="flex gap-1.5 ml-2">
                                <form action="{{ route('admin.day-starts.updateStatus', $dayStart->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <x-button type="submit" variant="success" class="px-2 py-1 text-[10px]">Approve</x-button>
                                </form>
                                <form action="{{ route('admin.day-starts.updateStatus', $dayStart->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <x-button type="submit" variant="danger" class="px-2 py-1 text-[10px]">Reject</x-button>
                                </form>
                            </div>
                        @endif
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
                @if ($dayStart->opening_km_photo)
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening KM Photo</div>
                        <button type="button" @click="lightboxImage = '{{ asset('storage/' . $dayStart->opening_km_photo) }}'; lightboxOpen = true;" class="mt-2 block">
                            <img src="{{ asset('storage/' . $dayStart->opening_km_photo) }}" alt="Opening KM" style="width: 192px; height: 128px; object-fit: cover;" class="rounded-lg border border-slate-200 shadow-sm hover:opacity-90 transition">
                        </button>
                    </div>
                @else
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening KM Photo</div>
                        <div class="mt-2 h-32 w-48 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200 border-dashed">
                            <span class="text-xs text-slate-400">No Photo</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($dayStart->end_time)
            <div class="border-t border-slate-200 p-5">
                <h3 class="font-semibold text-slate-950 mb-4">Day End Details</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Ended At</div>
                            <div class="mt-1 text-slate-900">{{ \Carbon\Carbon::parse($dayStart->end_time)->format('h:i A') }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing KM</div>
                            <div class="mt-1 text-slate-900">{{ $dayStart->closing_km ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Distance Travelled</div>
                            <div class="mt-1 font-semibold text-brand-600">
                                @if($dayStart->closing_km && $dayStart->opening_km)
                                    {{ max(0, $dayStart->closing_km - $dayStart->opening_km) }} KM
                                @else
                                    —
                                @endif
                            </div>
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
                        @if ($dayStart->closing_km_photo)
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing KM Photo</div>
                                <button type="button" @click="lightboxImage = '{{ asset('storage/' . $dayStart->closing_km_photo) }}'; lightboxOpen = true;" class="mt-2 block">
                                    <img src="{{ asset('storage/' . $dayStart->closing_km_photo) }}" alt="Closing KM" style="width: 192px; height: 128px; object-fit: cover;" class="rounded-lg border border-slate-200 shadow-sm hover:opacity-90 transition">
                                </button>
                            </div>
                        @else
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Closing KM Photo</div>
                                <div class="mt-2 h-32 w-48 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200 border-dashed">
                                    <span class="text-xs text-slate-400">No Photo</span>
                                </div>
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

    {{-- Visits Timeline --}}
    <section class="panel mt-6">
        <div class="panel-header border-b border-slate-200 p-5">
            <h3 class="font-semibold text-slate-950">Visits Timeline</h3>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Party Visited</th>
                        <th>Status</th>
                        <th>Purpose / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td>
                                <div class="font-medium text-slate-900">
                                    {{ \Carbon\Carbon::parse($visit->check_in_time)->format('h:i A') }}
                                </div>
                                @if($visit->check_out_time)
                                    <div class="text-xs text-slate-500">
                                        to {{ \Carbon\Carbon::parse($visit->check_out_time)->format('h:i A') }}
                                    </div>
                                @endif
                            </td>
                            <td class="font-semibold">{{ $visit->party->name ?? 'Unknown' }}</td>
                            <td>
                                @if($visit->status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                @else
                                    <span class="badge badge-warning">{{ ucfirst($visit->status) }}</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-600">{{ $visit->purpose ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-6 text-slate-500">No visits recorded today.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Orders Placed --}}
    <section class="panel mt-6">
        <div class="panel-header border-b border-slate-200 p-5">
            <h3 class="font-semibold text-slate-950">Orders Placed</h3>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Party</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-right">View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>{{ $order->created_at->format('h:i A') }}</td>
                            <td class="font-semibold">{{ $order->party->name ?? 'Unknown' }}</td>
                            <td class="font-bold text-brand-600">₹{{ number_format($order->total_amount, 2) }}</td>
                            <td>
                                <x-status-badge :status="$order->status" :map="['approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger']" />
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn-secondary px-2 py-1 text-xs" target="_blank">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-6 text-slate-500">No orders placed today.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Expenses Claimed --}}
    <section class="panel mt-6">
        <div class="panel-header border-b border-slate-200 p-5">
            <h3 class="font-semibold text-slate-950">Expenses Claimed</h3>
        </div>

        <div class="table-wrap" x-data="{ expandedExpense: null }">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Photo</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>
                                @if($expense->is_system_generated)
                                    <span class="badge badge-primary">System Travel</span>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $expense->category->name ?? 'N/A' }}</span>
                                        @if($expense->items->count() > 0)
                                            <button @click="expandedExpense = (expandedExpense === {{ $expense->id }} ? null : {{ $expense->id }})" type="button" class="text-brand-600 hover:text-brand-700 flex items-center gap-0.5 text-xs font-semibold bg-brand-50 px-1.5 py-0.5 rounded border border-brand-100 transition-colors">
                                                <span>{{ $expense->items->count() }} {{ $expense->items->count() === 1 ? 'item' : 'items' }}</span>
                                                <x-lucide-chevron-down class="w-3.5 h-3.5 transition-transform duration-200" ::class="expandedExpense === {{ $expense->id }} ? 'rotate-180' : ''" />
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="font-bold text-slate-900">₹{{ number_format($expense->amount, 2) }}</td>
                            <td>
                                @if($expense->bill_photo)
                                    <button type="button" @click="lightboxImage = '{{ asset('storage/' . $expense->bill_photo) }}'; lightboxOpen = true;" class="text-brand-600 underline text-sm font-semibold hover:text-brand-700">View Proof</button>
                                @elseif($expense->is_system_generated)
                                    <button @click="expandedExpense = (expandedExpense === {{ $expense->id }} ? null : {{ $expense->id }})" type="button" class="btn-secondary px-2 py-1 text-xs">
                                        View Meter
                                    </button>
                                @else
                                    <span class="text-slate-400 text-sm">No Photo</span>
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
                                @if($expense->status === 'pending')
                                <div class="flex gap-2 justify-end">
                                    <form action="{{ route('admin.expenses.updateStatus', $expense->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="redirect_back" value="1">
                                        <input type="hidden" name="status" value="approved">
                                        <x-button type="submit" variant="success" class="px-2 py-1 text-xs">Approve</x-button>
                                    </form>
                                    <form action="{{ route('admin.expenses.updateStatus', $expense->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="redirect_back" value="1">
                                        <input type="hidden" name="status" value="rejected">
                                        <x-button type="submit" variant="danger" class="px-2 py-1 text-xs">Reject</x-button>
                                    </form>
                                </div>
                                @else
                                    <span class="text-xs text-slate-500">Processed</span>
                                @endif
                            </td>
                        </tr>
                        @if($expense->is_system_generated)
                            {{-- Expandable Meter Summary Row --}}
                            <tr x-show="expandedExpense === {{ $expense->id }}" style="display: none;">
                                <td colspan="5" class="bg-slate-50 p-6 border-b border-slate-200 shadow-inner">
                                    <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-4">Meter & Travel Summary</h4>
                                    <div class="flex flex-wrap gap-8 items-start">
                                        
                                        {{-- Opening --}}
                                        <div class="flex items-center gap-4">
                                            @if ($dayStart->opening_km_photo)
                                                <button type="button" @click="lightboxImage = '{{ asset('storage/' . $dayStart->opening_km_photo) }}'; lightboxOpen = true;" class="shrink-0 block">
                                                    <img src="{{ asset('storage/' . $dayStart->opening_km_photo) }}" alt="Opening KM" style="width: 128px; height: 80px; object-fit: cover;" class="rounded border border-slate-300 shadow-sm hover:opacity-90 transition">
                                                </button>
                                            @else
                                                <div class="h-20 w-32 rounded bg-slate-200 flex items-center justify-center border border-slate-300 border-dashed shrink-0">
                                                    <span class="text-xs font-semibold text-slate-400">No Photo</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-[10px] uppercase font-bold text-slate-500">Start KM</p>
                                                <p class="font-black text-slate-900 text-xl mt-0.5">{{ $dayStart->opening_km ?? '—' }}</p>
                                            </div>
                                        </div>
                        
                                        {{-- Closing --}}
                                        <div class="flex items-center gap-4">
                                            @if ($dayStart->closing_km_photo)
                                                <button type="button" @click="lightboxImage = '{{ asset('storage/' . $dayStart->closing_km_photo) }}'; lightboxOpen = true;" class="shrink-0 block">
                                                    <img src="{{ asset('storage/' . $dayStart->closing_km_photo) }}" alt="Closing KM" style="width: 128px; height: 80px; object-fit: cover;" class="rounded border border-slate-300 shadow-sm hover:opacity-90 transition">
                                                </button>
                                            @else
                                                <div class="h-20 w-32 rounded bg-slate-200 flex items-center justify-center border border-slate-300 border-dashed shrink-0">
                                                    <span class="text-xs font-semibold text-slate-400">No Photo</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-[10px] uppercase font-bold text-slate-500">End KM</p>
                                                <p class="font-black text-slate-900 text-xl mt-0.5">{{ $dayStart->closing_km ?? '—' }}</p>
                                            </div>
                                        </div>
                        
                                        {{-- Distance --}}
                                        <div class="flex items-center gap-3 ml-auto bg-brand-50 border border-brand-200 shadow-sm rounded-xl px-5 py-3">
                                            <div>
                                                <p class="text-[10px] uppercase font-bold text-brand-600">Total Distance</p>
                                                <p class="font-black text-brand-600 text-2xl mt-0.5">
                                                    @if($dayStart->closing_km && $dayStart->opening_km)
                                                        {{ max(0, $dayStart->closing_km - $dayStart->opening_km) }} KM
                                                    @else
                                                        —
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                        
                                    </div>
                                </td>
                            </tr>
                        @endif

                        @if(!$expense->is_system_generated && $expense->items->count() > 0)
                            {{-- Expandable Itemized Row --}}
                            <tr x-show="expandedExpense === {{ $expense->id }}" style="display: none;">
                                <td colspan="5" class="bg-slate-50 p-6 border-b border-slate-200 shadow-inner">
                                    <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-4">Itemized Breakdown</h4>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-left text-slate-600">
                                            <thead>
                                                <tr class="border-b border-slate-200 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                                                    <th class="pb-2">Item Name</th>
                                                    <th class="pb-2 text-center">Qty</th>
                                                    <th class="pb-2 text-right">Unit Price</th>
                                                    <th class="pb-2 text-right">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($expense->items as $item)
                                                    <tr class="border-b border-slate-100 last:border-0">
                                                        <td class="py-2.5 font-medium text-slate-900">{{ $item->item_name }}</td>
                                                        <td class="py-2.5 text-center">{{ $item->quantity }}</td>
                                                        <td class="py-2.5 text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                                                        <td class="py-2.5 text-right font-semibold text-slate-900">₹{{ number_format($item->total_price, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-center py-6 text-slate-500">No expenses claimed today.</td></tr>
                    @endforelse
                </tbody>
                @if($expenses->count() > 0)
                <tfoot>
                    <tr class="bg-slate-50 border-t border-slate-200">
                        <td class="font-bold text-slate-900 text-right">Total Bill Amount:</td>
                        <td class="font-bold text-brand-600 text-lg">₹{{ number_format($expenses->sum('amount'), 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </section>
</div>
@endsection
