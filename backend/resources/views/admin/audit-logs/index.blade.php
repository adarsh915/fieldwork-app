@extends('admin.layouts.app')

@section('title', 'System Audit Trail & Action Logs')
@section('breadcrumb', 'ADMIN / AUDIT LOGS')
@section('page_title', 'System Audit Trail & Activity Logs')

@section('content')
<div class="space-y-6">

    {{-- ── 1. KPI Stats Row ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Logs --}}
        <div class="panel p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Audit Records</p>
                <h3 class="text-2xl font-bold text-ink-900 mt-1">{{ number_format($stats['total']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <x-lucide-history class="w-6 h-6" />
            </div>
        </div>

        {{-- Today's Actions --}}
        <div class="panel p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Actions Today</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['today']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <x-lucide-activity class="w-6 h-6" />
            </div>
        </div>

        {{-- This Week --}}
        <div class="panel p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Actions This Week</p>
                <h3 class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($stats['this_week']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <x-lucide-calendar class="w-6 h-6" />
            </div>
        </div>

        {{-- Active Admins --}}
        <div class="panel p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Active Staff / Admins</p>
                <h3 class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['admin_count']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <x-lucide-shield-check class="w-6 h-6" />
            </div>
        </div>
    </div>

    {{-- ── 2. Filter & Search Panel ── --}}
    <div class="panel p-6">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">
                {{-- Search text --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-ink-700 mb-1">Search Keywords</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search reason, admin, ID, IP..."
                               class="form-input text-xs w-full pl-9" />
                        <x-lucide-search class="w-4 h-4 text-ink-400 absolute left-2.5 top-2.5" />
                    </div>
                </div>

                {{-- Action / Event --}}
                <div>
                    <label class="block text-xs font-semibold text-ink-700 mb-1">Action Type</label>
                    <select name="event" class="form-select text-xs w-full">
                        <option value="">All Actions</option>
                        <option value="created"   {{ request('event') === 'created'   ? 'selected' : '' }}>Created (Add)</option>
                        <option value="updated"   {{ request('event') === 'updated'   ? 'selected' : '' }}>Updated (Edit)</option>
                        <option value="deleted"   {{ request('event') === 'deleted'   ? 'selected' : '' }}>Deleted (Remove)</option>
                        <option value="approved"  {{ request('event') === 'approved'  ? 'selected' : '' }}>Approved / Verified</option>
                        <option value="rejected"  {{ request('event') === 'rejected'  ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('event') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                {{-- Module / Model --}}
                <div>
                    <label class="block text-xs font-semibold text-ink-700 mb-1">Module</label>
                    <select name="model" class="form-select text-xs w-full">
                        <option value="">All Modules</option>
                        @foreach($knownModels as $key => $label)
                            <option value="{{ $key }}" {{ request('model') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Causer / Admin --}}
                <div>
                    <label class="block text-xs font-semibold text-ink-700 mb-1">Admin / User</label>
                    <select name="causer_id" class="form-select text-xs w-full">
                        <option value="">All Admins</option>
                        @foreach($causers as $admin)
                            <option value="{{ $admin->id }}" {{ (string)request('causer_id') === (string)$admin->id ? 'selected' : '' }}>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range --}}
                <div>
                    <label class="block text-xs font-semibold text-ink-700 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-xs w-full" />
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-surface-border">
                <div class="text-xs text-ink-500">
                    Showing <strong>{{ $logs->firstItem() ?? 0 }}</strong> to <strong>{{ $logs->lastItem() ?? 0 }}</strong> of <strong>{{ $logs->total() }}</strong> log entries
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn-secondary text-xs py-2 px-3">
                        Reset Filters
                    </a>
                    <button type="submit" class="btn-primary text-xs py-2 px-4 inline-flex items-center gap-1.5">
                        <x-lucide-filter class="w-3.5 h-3.5" />
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── 3. Audit Logs Table ── --}}
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-subtle text-ink-700 font-semibold border-b border-surface-border">
                    <tr>
                        <th class="p-3.5 text-xs uppercase tracking-wider">Timestamp</th>
                        <th class="p-3.5 text-xs uppercase tracking-wider">Admin User</th>
                        <th class="p-3.5 text-xs uppercase tracking-wider">Action</th>
                        <th class="p-3.5 text-xs uppercase tracking-wider">Target Entity</th>
                        <th class="p-3.5 text-xs uppercase tracking-wider">Reason / Description</th>
                        <th class="p-3.5 text-xs uppercase tracking-wider">Details (Diff)</th>
                        <th class="p-3.5 text-xs uppercase tracking-wider text-right">IP Address</th>
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
                        <tr class="hover:bg-surface-hover transition-colors">
                            {{-- Timestamp --}}
                            <td class="p-3.5 text-xs whitespace-nowrap text-ink-500 font-mono">
                                <div>{{ $log->created_at->format('d M Y, h:i:s A') }}</div>
                                <div class="text-[11px] text-ink-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            {{-- Admin / Causer --}}
                            <td class="p-3.5 whitespace-nowrap">
                                @if($log->causer)
                                    <div class="font-semibold text-ink-900 text-xs">{{ $log->causer->name }}</div>
                                    <div class="text-[11px] text-ink-500">{{ $log->causer->roles->pluck('name')->join(', ') ?: $log->causer->email }}</div>
                                @else
                                    <span class="text-xs text-ink-400 italic">System / Auto</span>
                                @endif
                            </td>

                            {{-- Action Badge --}}
                            <td class="p-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                    {{ $log->event }}
                                </span>
                            </td>

                            {{-- Target Entity --}}
                            <td class="p-3.5 whitespace-nowrap text-xs">
                                <span class="font-bold text-ink-800">{{ $modelName }}</span>
                                @if($log->auditable_id)
                                    <span class="text-ink-400">#{{ $log->auditable_id }}</span>
                                @endif
                            </td>

                            {{-- Reason / Description --}}
                            <td class="p-3.5 text-xs text-ink-700 max-w-xs truncate" title="{{ $log->reason }}">
                                {{ $log->reason ?: '—' }}
                            </td>

                            {{-- Details / Changes Diff --}}
                            <td class="p-3.5 text-xs font-mono">
                                @if($log->old_values || $log->new_values)
                                    <details class="cursor-pointer group">
                                        <summary class="text-primary-600 hover:text-primary-700 font-medium select-none flex items-center gap-1">
                                            <span>View Diff</span>
                                            <span class="text-[10px] text-ink-400">({{ count((array)$log->new_values ?: (array)$log->old_values) }} fields)</span>
                                        </summary>
                                        <div class="mt-2 p-3 rounded-lg bg-surface-ground border border-surface-border text-xs space-y-2 max-w-md max-h-64 overflow-y-auto">
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
                            <td class="p-3.5 text-right whitespace-nowrap text-xs font-mono text-ink-500">
                                {{ $log->ip_address ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-ink-400">
                                <x-lucide-history class="w-10 h-10 mx-auto text-ink-300 mb-2" />
                                <p class="text-sm font-medium">No audit logs matching your search criteria.</p>
                                <p class="text-xs text-ink-400 mt-1">Try resetting the filters or modifying your search terms.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="p-4 border-t border-surface-border">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
