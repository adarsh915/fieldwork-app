@extends('admin.layouts.app')

@section('title', 'Visits - ' . $fieldWorker->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('admin.field-workers.index') }}" class="p-2 bg-slate-200 rounded-lg hover:bg-slate-300 transition">
                    <x-lucide-arrow-left class="w-6 h-6 text-slate-700" />
                </a>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-slate-900">{{ $fieldWorker->name }} ({{ $fieldWorker->role_name }}) - Visits</h1>
                </div>
            </div>
        </div>

        <!-- Upcoming Visits -->
        @if($upcomingVisits->count() > 0)
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <x-lucide-calendar-check class="w-6 h-6 text-green-600" />
                    Upcoming Visits ({{ $upcomingVisits->count() }})
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($upcomingVisits as $visit)
                        <div class="bg-white rounded-lg p-6 shadow-md border-l-4 border-green-500">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $visit->party->name }}</p>
                                    <p class="text-sm text-slate-600">{{ $visit->party->city ?? '—' }}</p>
                                </div>
                                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">
                                    Upcoming
                                </span>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-lucide-clock class="w-4 h-4" />
                                    {{ $visit->check_in_time->format('M d, Y h:i A') }}
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-lucide-tag class="w-4 h-4" />
                                    {{ $visit->purpose ?? 'General Visit' }}
                                </div>
                            </div>
                            <button onclick="viewVisitDetails({{ $fieldWorker->id }}, {{ $visit->id }})" class="w-full mt-4 px-3 py-2 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition text-sm font-semibold">
                                View Details
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Recent Visits -->
        <div>
            <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                <x-lucide-history class="w-6 h-6 text-slate-600" />
                Recent Visits
            </h2>

            @if($visits->count() > 0)
                <div class="space-y-4">
                    @foreach($visits as $visit)
                        <div class="bg-white rounded-lg p-6 shadow-md border border-slate-200 hover:shadow-lg transition">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg text-slate-900">{{ $visit->party->name }}</h3>
                                    <p class="text-sm text-slate-600">{{ $visit->party->city ?? '—' }} • {{ $visit->party->party_code ?? 'N/A' }}</p>
                                </div>
                                <span class="px-3 py-1 rounded-full @class([
                                    'bg-green-100 text-green-700' => $visit->status === 'completed',
                                    'bg-yellow-100 text-yellow-700' => $visit->status === 'pending',
                                ]) text-xs font-bold">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">CHECK-IN</p>
                                    <p class="font-semibold text-slate-900">{{ $visit->check_in_time?->format('M d, Y') ?? '—' }}</p>
                                    <p class="text-sm text-slate-600">{{ $visit->check_in_time?->format('h:i A') ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">CHECK-OUT</p>
                                    <p class="font-semibold text-slate-900">{{ $visit->check_out_time?->format('h:i A') ?? '—' }}</p>
                                    @if($visit->check_in_time && $visit->check_out_time)
                                        <p class="text-sm text-slate-600">{{ $visit->check_in_time->diffInMinutes($visit->check_out_time) }} mins</p>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">PURPOSE</p>
                                    <p class="font-semibold text-slate-900">{{ $visit->purpose ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">LOCATION</p>
                                    <p class="font-semibold text-slate-900">
                                        @if($visit->check_in_lat && $visit->check_in_lng)
                                            <a href="https://maps.google.com/?q={{ $visit->check_in_lat }},{{ $visit->check_in_lng }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                                                View on Map
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($visit->notes)
                                <div class="mb-4 p-3 bg-slate-50 rounded border border-slate-200">
                                    <p class="text-xs text-slate-600 mb-1">NOTES</p>
                                    <p class="text-sm text-slate-900">{{ $visit->notes }}</p>
                                </div>
                            @endif

                            <button onclick="viewVisitDetails({{ $fieldWorker->id }}, {{ $visit->id }})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-sm flex items-center gap-2">
                                <x-lucide-eye class="w-4 h-4" />
                                View Full Details
                            </button>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $visits->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
                    <x-lucide-inbox class="w-16 h-16 text-slate-300 mx-auto mb-4" />
                    <p class="text-slate-600">No visits found</p>
                </div>
            @endif
        </div>

        <!-- Back Button -->
        <div class="mt-8">
            <a href="{{ route('admin.field-workers.metrics', $fieldWorker->id) }}" class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition font-semibold flex items-center gap-2 w-fit">
                <x-lucide-arrow-left class="w-4 h-4" />
                Back to Metrics
            </a>
        </div>
    </div>
</div>

<!-- Visit Details Modal -->
<div id="visitDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-2xl w-full shadow-xl my-8">
        <div class="sticky top-0 px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-white rounded-t-lg">
            <h3 class="text-lg font-bold text-slate-900">Visit Details</h3>
            <button onclick="closeVisitDetails()" class="text-slate-400 hover:text-slate-600">
                <x-lucide-x class="w-6 h-6" />
            </button>
        </div>
        <div id="visitDetailsContent" class="p-6">
            <p class="text-slate-600">Loading...</p>
        </div>
    </div>
</div>

<script>
    function viewVisitDetails(fieldWorkerId, visitId) {
        document.getElementById('visitDetailsModal').classList.remove('hidden');
        fetch(`/admin/field-workers/${fieldWorkerId}/visits/${visitId}`)
            .then(r => r.json())
            .then(data => {
                let html = `
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">Party Information</h4>
                            <div class="bg-slate-50 p-4 rounded">
                                <p class="font-semibold text-slate-900">${data.party.name}</p>
                                <p class="text-sm text-slate-600">${data.party.city} • ${data.party.party_code}</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">Visit Timeline</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-50 p-4 rounded">
                                    <p class="text-xs text-slate-600 mb-1">CHECK-IN</p>
                                    <p class="font-semibold text-slate-900">${new Date(data.visit.check_in_time).toLocaleString()}</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded">
                                    <p class="text-xs text-slate-600 mb-1">CHECK-OUT</p>
                                    <p class="font-semibold text-slate-900">${data.visit.check_out_time ? new Date(data.visit.check_out_time).toLocaleString() : '—'}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">Recent Orders from this Party</h4>
                            ${data.recent_orders.length > 0 ? `
                                <div class="space-y-2">
                                    ${data.recent_orders.map(order => `
                                        <div class="bg-slate-50 p-3 rounded border border-slate-200">
                                            <p class="font-semibold text-sm">${order.order_no}</p>
                                            <p class="text-xs text-slate-600">₹ ${order.total_amount.toLocaleString()}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : '<p class="text-slate-600">No recent orders</p>'}
                        </div>

                        <div>
                            <h4 class="font-semibold text-slate-900 mb-2">Recent Transactions</h4>
                            ${data.recent_transactions.length > 0 ? `
                                <div class="space-y-2">
                                    ${data.recent_transactions.map(txn => `
                                        <div class="bg-slate-50 p-3 rounded border border-slate-200">
                                            <p class="font-semibold text-sm">${txn.transaction_type.toUpperCase()}</p>
                                            <p class="text-xs text-slate-600">₹ ${txn.amount.toLocaleString()}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : '<p class="text-slate-600">No recent transactions</p>'}
                        </div>
                    </div>
                `;
                document.getElementById('visitDetailsContent').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('visitDetailsContent').innerHTML = '<p class="text-red-600">Error loading details</p>';
            });
    }

    function closeVisitDetails() {
        document.getElementById('visitDetailsModal').classList.add('hidden');
    }
</script>
@endsection
