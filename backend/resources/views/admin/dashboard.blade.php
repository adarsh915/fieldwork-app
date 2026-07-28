@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'ADMIN / OVERVIEW')
@section('page_title', 'Dashboard')

@section('content')

{{-- Hero --}}
<section class="dashboard-hero mb-6">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px] xl:items-center">
        <div>
            <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white/70">
                Field operations snapshot
            </span>
            <h2 class="mt-3 text-2xl font-bold leading-tight text-white">
                Monitor users, customer records, and finance from one workspace.
            </h2>
            <p class="mt-2 text-sm leading-6 text-white/70 max-w-xl">
                Manage access, review customer master data, and track transaction status across field teams.
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/60">Pending</p>
                <p class="mt-2 text-3xl font-bold text-white">{{ number_format($stats['pending_transactions'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-white/50">transactions</p>
            </div>
            <div class="rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/60">Roles</p>
                <p class="mt-2 text-3xl font-bold text-white">{{ number_format($stats['roles'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-white/50">access levels</p>
            </div>
        </div>
    </div>
</section>

{{-- KPI cards --}}
<div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 xl:grid-cols-4">
    <div class="metric-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="metric-label">Total Users</p>
                <p class="mt-3 text-3xl font-black text-ink-900">{{ number_format($stats['users'] ?? 0) }}</p>
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
                <p class="metric-label">Customers</p>
                <p class="mt-3 text-3xl font-black text-ink-900">{{ number_format($stats['customers'] ?? 0) }}</p>
            </div>
            <span class="metric-icon bg-emerald-50 text-emerald-700">
                <x-lucide-users class="w-5 h-5" />
            </span>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">
            Open records →
        </a>
    </div>

    <div class="metric-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="metric-label">Transactions</p>
                <p class="mt-3 text-3xl font-black text-ink-900">{{ number_format($stats['transactions'] ?? 0) }}</p>
            </div>
            <span class="metric-icon bg-amber-50 text-amber-700">
                <x-lucide-receipt class="w-5 h-5" />
            </span>
        </div>
        <a href="{{ route('admin.transactions.index') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">
            Review ledger →
        </a>
    </div>

    <div class="metric-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="metric-label">Total Value</p>
                <p class="mt-3 text-2xl font-black text-ink-900 tabular-nums">₹{{ number_format((float) ($stats['transaction_value'] ?? 0), 2) }}</p>
            </div>
            <span class="metric-icon bg-rose-50 text-rose-700">
                <x-lucide-banknote class="w-5 h-5" />
            </span>
        </div>
        <p class="mt-5 text-sm text-ink-400">All recorded transaction value</p>
    </div>
</div>

{{-- Activity + Access --}}
<div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_400px]">

    {{-- Recent transactions --}}
    <div class="panel overflow-hidden">
        <div class="panel-header flex items-center justify-between">
            <div>
                <h2 class="text-section-title">Transaction Activity</h2>
                <p class="text-caption mt-0.5">Latest finance records from the field.</p>
            </div>
            <x-button href="{{ route('admin.transactions.index') }}" variant="secondary">View all</x-button>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $transaction)
                        <tr>
                            <td>
                                <div class="text-body-emphasis">TXN-{{ str_pad($transaction->id, 5, '0', STR_PAD_LEFT) }}</div>
                                <div class="text-caption">{{ $transaction->created_at?->format('d M Y, h:i A') }}</div>
                            </td>
                            <td>
                                <div class="text-body-emphasis">{{ $transaction->customer?->name ?? 'Unknown' }}</div>
                                <div class="text-caption">{{ $transaction->customer?->mobile ?? '' }}</div>
                            </td>
                            <td>
                                <div class="text-body">{{ $transaction->user?->name ?? '—' }}</div>
                                <div class="text-caption">{{ $transaction->user?->email ?? '' }}</div>
                            </td>
                            <td>
                                <x-status-badge :status="$transaction->status" :map="['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning']" />
                            </td>
                            <td class="text-right font-black tabular-nums text-ink-900">₹{{ number_format((float) $transaction->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-ink-400">
                                    <x-lucide-inbox class="w-8 h-8" />
                                    <p class="text-sm">No transactions yet.</p>
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
                                    <p class="text-body-emphasis truncate">{{ $user->name }}</p>
                                    <p class="text-caption truncate">{{ $user->email }}</p>
                                </div>
                                <span class="text-caption shrink-0">#{{ $user->id }}</span>
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
            <p class="text-caption mb-4">Jump into common admin tasks.</p>
            <div class="grid grid-cols-1 gap-2">
                <x-button href="{{ route('admin.users.index') }}" variant="secondary" class="justify-between">
                    <span>Assign roles</span>
                    <x-lucide-user-cog class="w-4 h-4 text-ink-400" />
                </x-button>
                <x-button href="{{ route('admin.customers.index') }}" variant="secondary" class="justify-between">
                    <span>Search customers</span>
                    <x-lucide-users class="w-4 h-4 text-ink-400" />
                </x-button>
                <x-button href="{{ route('admin.transactions.index') }}" variant="secondary" class="justify-between">
                    <span>Filter transactions</span>
                    <x-lucide-receipt class="w-4 h-4 text-ink-400" />
                </x-button>
            </div>
        </div>
    </div>

</div>
@endsection
