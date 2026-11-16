@extends('admin.layouts.app')

@section('title', 'System Audit Trail & Action Logs')
@section('breadcrumb', 'ADMIN / AUDIT LOGS')
@section('page_title', 'System Audit Trail & Activity Logs')

@section('content')
<div class="space-y-6">

    {{-- ── 1. KPI Stats Row ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Logs --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-blue-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Audit Records</p>
                <h3 class="text-2xl font-bold text-ink-900 mt-1">{{ number_format($stats['total']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <x-lucide-history class="w-6 h-6" />
            </div>
        </div>

        {{-- Today's Actions --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-emerald-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Actions Today</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['today']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <x-lucide-activity class="w-6 h-6" />
            </div>
        </div>

        {{-- This Week --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-brand-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Actions This Week</p>
                <h3 class="text-2xl font-bold text-brand-600 mt-1">{{ number_format($stats['this_week']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                <x-lucide-calendar class="w-6 h-6" />
            </div>
        </div>

        {{-- Active Admins --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-purple-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Active Staff / Admins</p>
                <h3 class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['admin_count']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <x-lucide-shield-check class="w-6 h-6" />
            </div>
        </div>
    </div>

    {{-- ── 2. Filter & Search Panel ── --}}
    <div class="panel p-5">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" id="audit-filter-form" class="space-y-4">
            {{-- Top row: Quick Search and Reset Filter --}}
            <div class="flex flex-wrap gap-3 items-end">
                <div class="relative flex-1 min-w-[260px]">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text" name="search" id="audit-search-input" value="{{ request('search') }}"
                           placeholder="Search action reason, admin name, model ID, IP..."
                           class="form-input text-xs pl-9 pr-8 w-full"
                           onkeydown="if(event.key === 'Enter') { event.preventDefault(); this.form.submit(); }" />
                    @if(request('search'))
                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                           class="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-400 hover:text-ink-600"
                           title="Clear search">
                            <x-lucide-x class="w-4 h-4" />
                        </a>
                    @endif
                </div>

                {{-- Reset Filters Button --}}
                @if(request()->anyFilled(['search', 'event', 'model', 'causer_id', 'date_from', 'date_to']))
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="btn-secondary text-xs py-2 px-3.5 font-semibold inline-flex items-center gap-1.5 shrink-0 hover:bg-surface-muted transition"
                       title="Reset all filters">
                        <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-500" />
                        <span>Reset Filters</span>
                    </a>
                @else
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="btn-secondary text-xs py-2 px-3.5 font-semibold inline-flex items-center gap-1.5 shrink-0 opacity-50 cursor-default"
                       title="No filters active">
                        <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-400" />
                        <span>Reset Filters</span>
                    </a>
                @endif
            </div>

            {{-- Filter Select Dropdowns Grid (Live Auto-Submit on change, no filter button needed) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 pt-3 border-t border-surface-border">
                <x-filter-select
                    name="event"
                    label="Action Type"
                    placeholder="All Actions"
                    :options="[
                        'created' => 'Created (Add)',
                        'updated' => 'Updated (Edit)',
                        'deleted' => 'Deleted (Remove)',
                        'approved' => 'Approved / Verified',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]"
                    :selected="request('event', '')"
                    change="this.$refs.hiddenInput.form.submit()"
                />

                <x-filter-select
                    name="model"
                    label="Module"
                    placeholder="All Modules"
                    :options="$knownModels"
                    :selected="request('model', '')"
                    change="this.$refs.hiddenInput.form.submit()"
                />

                <x-filter-select
                    name="causer_id"
                    label="Admin / Staff"
                    placeholder="All Staff / Admins"
                    :options="$causers->mapWithKeys(fn($a) => [$a->id => $a->name])"
                    :selected="request('causer_id', '')"
                    change="this.$refs.hiddenInput.form.submit()"
                />

                {{-- From Date --}}
                <div>
                    <label class="form-label text-[11px] font-semibold uppercase tracking-wider text-ink-600 mb-1 block">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-xs w-full" onchange="this.form.submit()" />
                </div>

                {{-- To Date --}}
                <div>
                    <label class="form-label text-[11px] font-semibold uppercase tracking-wider text-ink-600 mb-1 block">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-xs w-full" onchange="this.form.submit()" />
                </div>
            </div>
        </form>
    </div>

    {{-- ── 3. Audit Logs Table ── --}}
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">Timestamp</th>
                        <th class="whitespace-nowrap">Admin User</th>
                        <th class="whitespace-nowrap">Action</th>
                        <th class="whitespace-nowrap">Target Entity</th>
                        <th>Reason / Description</th>
                        <th>Details (Diff)</th>
                        <th class="text-right whitespace-nowrap">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border text-ink-900">
                    @forelse($logs as $log)
                        @php
                            $event = $log->event;
                            $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                            if (str_ends_with($event, '.created')) {
                                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            } elseif (str_ends_with($event, '.updated') || str_ends_with($event, '.adjusted')) {
                                $badgeClass = 'bg-sky-50 text-sky-700 border-sky-200';
                            } elseif (str_ends_with($event, '.deleted')) {
                                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                            } elseif (str_contains($event, 'approved') || str_contains($event, 'verified')) {
                                $badgeClass = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            } elseif (str_contains($event, 'rejected')) {
                                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                            } elseif (str_contains($event, 'cancelled')) {
                                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                            }
                            $modelName = class_basename($log->auditable_type ?? 'System');
                        @endphp
                        <tr class="hover:bg-surface-muted transition-colors">
                            {{-- Timestamp --}}
                            <td class="whitespace-nowrap text-xs text-ink-500 font-mono">
                                <div class="font-bold text-ink-900">{{ $log->created_at->format('d M Y, h:i:s A') }}</div>
                                <div class="text-[11px] text-ink-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            {{-- Admin / Causer --}}
                            <td class="whitespace-nowrap">
                                @if($log->causer)
                                    <div class="font-bold text-ink-900 text-xs">{{ $log->causer->name }}</div>
                                    <div class="text-[11px] text-ink-400 font-mono">{{ $log->causer->roles->pluck('name')->join(', ') ?: $log->causer->email }}</div>
                                @else
                                    <span class="text-xs text-ink-400 italic">System / Auto</span>
                                @endif
                            </td>

                            {{-- Action Badge --}}
                            <td class="whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                    {{ $log->event }}
                                </span>
                            </td>

                            {{-- Target Entity --}}
                            <td class="whitespace-nowrap text-xs">
                                <span class="font-bold text-ink-800">{{ $modelName }}</span>
                                @if($log->auditable_id)
                                    <span class="text-ink-400 font-mono">#{{ $log->auditable_id }}</span>
                                @endif
                            </td>

                            {{-- Reason / Description --}}
                            <td class="text-xs text-ink-700 max-w-xs truncate" title="{{ $log->reason }}">
                                {{ $log->reason ?: '—' }}
                            </td>

                            {{-- Details / Changes Diff --}}
                            <td class="text-xs font-mono">
                                @if($log->old_values || $log->new_values)
                                    <details class="cursor-pointer group">
                                        <summary class="text-brand-600 hover:text-brand-700 font-medium select-none flex items-center gap-1">
                                            <span>View Diff</span>
                                            <span class="text-[10px] text-ink-400">({{ count((array)$log->new_values ?: (array)$log->old_values) }} fields)</span>
                                        </summary>
                                        <div class="mt-2 p-3 rounded-lg bg-surface-muted border border-surface-border text-xs space-y-2 max-w-md max-h-64 overflow-y-auto shadow-sm">
                                            @php
                                                $keys = array_unique(array_merge(array_keys((array)$log->old_values), array_keys((array)$log->new_values)));
                                            @endphp
                                            <div class="space-y-1.5 divide-y divide-surface-border">
                                                @foreach($keys as $key)
                                                    @php
                                                        $oldVal = $log->old_values[$key] ?? null;
                                                        $newVal = $log->new_values[$key] ?? null;
                                                        if (is_array($oldVal)) $oldVal = json_encode($oldVal);
                                                        if (is_array($newVal)) $newVal = json_encode($newVal);
                                                    @endphp
                                                    <div class="pt-1 first:pt-0">
                                                        <span class="font-semibold text-ink-700">{{ $key }}:</span>
                                                        @if($oldVal !== null && $newVal !== null)
                                                            <div class="text-[11px] text-rose-600 line-through">Old: {{ Str::limit((string)$oldVal, 80) }}</div>
                                                            <div class="text-[11px] text-emerald-600 font-semibold">New: {{ Str::limit((string)$newVal, 80) }}</div>
                                                        @elseif($newVal !== null)
                                                            <div class="text-[11px] text-emerald-600 font-semibold">Set: {{ Str::limit((string)$newVal, 80) }}</div>
                                                        @elseif($oldVal !== null)
                                                            <div class="text-[11px] text-rose-600">Was: {{ Str::limit((string)$oldVal, 80) }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </details>
                                @else
                                    <span class="text-ink-400">—</span>
                                @endif
                            </td>

                            {{-- IP Address --}}
                            <td class="text-right whitespace-nowrap text-xs font-mono text-ink-400">
                                {{ $log->ip_address ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-ink-400">
                                <x-lucide-history class="w-10 h-10 mx-auto text-ink-300 mb-2" />
                                <p class="text-sm font-semibold text-ink-800">No audit logs matching your search criteria.</p>
                                <p class="text-xs text-ink-400 mt-1">Try resetting the filters or modifying your search terms.</p>
                                @if(request()->anyFilled(['search', 'event', 'model', 'causer_id', 'date_from', 'date_to']))
                                    <div class="mt-4">
                                        <a href="{{ route('admin.audit-logs.index') }}" class="btn-secondary text-xs px-3.5 py-1.5">
                                            Clear All Filters
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Unified Server-Side Pagination (Prev, numbers, Next) --}}
        {{ $logs->links() }}
    </div>
</div>
@endsection
