@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'ADMIN / OVERVIEW')
@section('page_title', 'Dashboard')

@section('content')

{{-- Hero --}}
<section class="dashboard-hero mb-6">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px] xl:items-center">
        <div>
            <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white/70">
                Field operations snapshot
            </span>
            <h2 class="mt-3 text-2xl font-bold leading-tight text-white">
                Monitor field workers, parties, store visits, and phone orders.
            </h2>
            <p class="mt-2 text-sm leading-6 text-white/70 max-w-xl">
                Track live field attendance, customer assignments, store check-in/out visits, and phone call bookings.
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/60">Phone Orders</p>
                <p class="mt-2 text-3xl font-bold text-white kpi-val" data-key="phone_orders">
                    <span class="kpi-skeleton animate-pulse bg-white/20 rounded w-10 h-8 inline-block"></span>
                </p>
                <p class="mt-1 text-xs text-white/50">call bookings</p>
            </div>
            <div class="rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/60">Completed</p>
                <p class="mt-2 text-3xl font-bold text-white kpi-val" data-key="completed_visits">
                    <span class="kpi-skeleton animate-pulse bg-white/20 rounded w-10 h-8 inline-block"></span>
                </p>
                <p class="mt-1 text-xs text-white/50">store visits</p>
            </div>
        </div>
    </div>
</section>

{{-- KPI cards — numbers loaded via AJAX to avoid blocking initial render --}}
<div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 xl:grid-cols-4" id="kpi-cards">
    <div class="metric-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="metric-label">Total Users</p>
                <p class="mt-3 text-3xl font-black text-ink-900 kpi-val" data-key="users">
                    <span class="kpi-skeleton animate-pulse bg-slate-200 rounded w-12 h-8 inline-block"></span>
                </p>
            </div>
            <span class="metric-icon bg-sky-50 text-sky-700">
                <x-lucide-user-cog class="w-5 h-5" />
            </span>
        </div>
        <a href="{{ route('admin.users.index') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">
            Manage users →
        </a>
    </div>

    <div class="metric-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="metric-label">Parties</p>
                <p class="mt-3 text-3xl font-black text-ink-900 kpi-val" data-key="parties">
                    <span class="kpi-skeleton animate-pulse bg-slate-200 rounded w-12 h-8 inline-block"></span>
                </p>
            </div>
            <span class="metric-icon bg-emerald-50 text-emerald-700">
                <x-lucide-users class="w-5 h-5" />
            </span>
        </div>
        <a href="{{ route('admin.parties.index') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">
            Open parties →
        </a>
    </div>

    <div class="metric-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="metric-label">Total Visits</p>
                <p class="mt-3 text-3xl font-black text-ink-900 kpi-val" data-key="visits">
                    <span class="kpi-skeleton animate-pulse bg-slate-200 rounded w-12 h-8 inline-block"></span>
                </p>
            </div>
            <span class="metric-icon bg-amber-50 text-amber-700">
                <x-lucide-map-pin class="w-5 h-5" />
            </span>
        </div>
        <a href="{{ route('admin.visits.index') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">
            Review visits →
        </a>
    </div>

    <div class="metric-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="metric-label">Total Sales Value</p>
                <p class="mt-3 text-2xl font-black text-ink-900 tabular-nums kpi-val" data-key="order_value" data-format="currency">
                    <span class="kpi-skeleton animate-pulse bg-slate-200 rounded w-20 h-8 inline-block"></span>
                </p>
            </div>
            <span class="metric-icon bg-rose-50 text-rose-700">
                <x-lucide-shopping-bag class="w-5 h-5" />
            </span>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">
            View orders →
        </a>
    </div>
</div>

{{-- Activity + Access --}}
<div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_400px]">

    {{-- Recent Store Visits --}}
    <div class="panel overflow-hidden">
        <div class="panel-header flex items-center justify-between">
            <div>
                <h2 class="text-section-title">Recent Store Visits</h2>
                <p class="text-caption mt-0.5">Live store check-in & check-out logs from field workers.</p>
            </div>
            <x-button href="{{ route('admin.visits.index') }}" variant="secondary">View all</x-button>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Party (Store)</th>
                        <th>Field Worker</th>
                        <th>Check-in / Out</th>
                        <th>Status</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentVisits as $visit)
                        <tr>
                            <td>
                                <div class="text-body-emphasis">{{ $visit->party?->name ?? 'Unknown Party' }}</div>
                                <div class="text-caption">{{ $visit->party?->city ?? '—' }} ({{ $visit->party?->party_code ?? '' }})</div>
                            </td>
                            <td>
                                <div class="text-body font-medium">{{ $visit->user ? $visit->user->name . ' (' . $visit->user->role_name . ')' : '—' }}</div>
                            </td>
                            <td>
                                <div class="text-body-emphasis font-medium">
                                    {{ $visit->check_in_time ? $visit->check_in_time->format('d M, h:i A') : '—' }}
                                </div>
                                <div class="text-caption text-slate-400">
                                    Out: {{ $visit->check_out_time ? $visit->check_out_time->format('h:i A') : 'Pending checkout' }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $status = $visit->status ?? ($visit->check_out_time ? 'completed' : 'in_progress');
                                @endphp
                                <x-status-badge :status="$status" :map="['completed' => 'success', 'in_progress' => 'warning', 'cancelled' => 'danger']" />
                            </td>
                            <td class="text-body text-xs text-slate-600 max-w-[150px] truncate" title="{{ $visit->purpose ?? $visit->notes }}">
                                {{ $visit->purpose ?? ($visit->notes ? \Illuminate\Support\Str::limit($visit->notes, 25) : 'General Visit') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-ink-400">
                                    <x-lucide-inbox class="w-8 h-8" />
                                    <p class="text-sm">No store visits recorded yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Recent users --}}
        <div class="panel">
            <div class="panel-header">
                <h2 class="text-section-title">Access Overview</h2>
                <p class="text-caption mt-0.5">Recently created users and roles.</p>
            </div>
            <div class="divide-y divide-surface-border">
                @forelse ($recentUsers as $user)
                    <div class="flex items-start gap-3 px-6 py-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-ink-900 text-sm font-bold text-white">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-body-emphasis truncate">{{ $user->name }} ({{ $user->role_name }})</p>
                                    <p class="text-caption truncate">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @forelse ($user->roles as $role)
                                    <span class="badge badge-info">{{ $role->name }}</span>
                                @empty
                                    <span class="badge badge-neutral">No role</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-ink-400">No users yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="panel p-6">
            <h2 class="text-section-title mb-1">Quick Actions</h2>
            <p class="text-caption mb-4">Jump into common field operations tasks.</p>
            <div class="grid grid-cols-1 gap-2">
                <x-button href="{{ route('admin.parties.index') }}" variant="secondary" class="justify-between">
                    <span>Manage Parties</span>
                    <x-lucide-users class="w-4 h-4 text-ink-400" />
                </x-button>
                <x-button href="{{ route('admin.visits.index') }}" variant="secondary" class="justify-between">
                    <span>Store Visits Log</span>
                    <x-lucide-map-pin class="w-4 h-4 text-ink-400" />
                </x-button>
                <x-button href="{{ route('admin.orders.index') }}" variant="secondary" class="justify-between">
                    <span>Orders & Phone Bookings</span>
                    <x-lucide-shopping-bag class="w-4 h-4 text-ink-400" />
                </x-button>
                <x-button href="{{ route('admin.reports.index') }}" variant="secondary" class="justify-between">
                    <span>360° Performance Reports</span>
                    <x-lucide-trending-up class="w-4 h-4 text-ink-400" />
                </x-button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
// Fetch KPI stats via AJAX — avoids COUNT queries on initial page load
fetch('{{ route('admin.dashboard.stats') }}')
    .then(r => r.json())
    .then(data => {
        document.querySelectorAll('.kpi-val').forEach(el => {
            const key = el.dataset.key;
            if (data[key] === undefined) return;
            const val = data[key];
            el.innerHTML = el.dataset.format === 'currency'
                ? '\u20B9' + Number(val).toLocaleString('en-IN', {minimumFractionDigits: 2})
                : Number(val).toLocaleString('en-IN');
        });
    })
    .catch(() => {
        document.querySelectorAll('.kpi-skeleton').forEach(s => s.textContent = '—');
    });
</script>
@endpush
@endsection

