@extends('admin.layouts.app')

@section('title', 'System Audit Logs')
@section('breadcrumb', 'ADMIN / AUDIT LOGS')
@section('page_title', 'System Audit Trail & History')

@section('content')

<div class="panel p-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-ink-900">Admin Action & System Audit Trail</h2>
            <p class="text-caption mt-1">Complete immutable record of all admin overrides, attendance fixes, leave edits, and setting changes.</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-surface-border">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-subtle text-ink-700 font-semibold border-b border-surface-border">
                <tr>
                    <th class="p-3">Timestamp</th>
                    <th class="p-3">Admin</th>
                    <th class="p-3">Event Action</th>
                    <th class="p-3">Target Model</th>
                    <th class="p-3">Reason / Rationale</th>
                    <th class="p-3">Details (Old $\rightarrow$ New)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border text-ink-900">
                @forelse($logs as $log)
                    <tr class="hover:bg-surface-hover transition-colors">
                        <td class="p-3 font-mono text-xs whitespace-nowrap text-ink-500">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="p-3 font-medium">
                            {{ $log->causer?->name ?? 'System / Unknown' }}
                        </td>
                        <td class="p-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $log->event }}
                            </span>
                        </td>
                        <td class="p-3 text-xs text-ink-600">
                            {{ class_basename($log->auditable_type ?? '') }} #{{ $log->auditable_id }}
                        </td>
                        <td class="p-3 text-sm italic text-ink-700">
                            "{{ $log->reason ?? '—' }}"
                        </td>
                        <td class="p-3 text-xs font-mono">
                            @if($log->old_values || $log->new_values)
                                <details class="cursor-pointer">
                                    <summary class="text-primary-600 hover:underline">View Changes</summary>
                                    <div class="mt-2 p-2 rounded bg-surface-ground text-xs space-y-1">
                                        @if($log->old_values)
                                            <div class="text-rose-600"><strong>Old:</strong> {{ json_encode($log->old_values) }}</div>
                                        @endif
                                        @if($log->new_values)
                                            <div class="text-emerald-600"><strong>New:</strong> {{ json_encode($log->new_values) }}</div>
                                        @endif
                                    </div>
                                </details>
                            @else
                                <span class="text-ink-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-ink-400">No audit logs recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>

@endsection
