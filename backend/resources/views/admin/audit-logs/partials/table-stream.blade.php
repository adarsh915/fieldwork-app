<div class="table-wrap">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="whitespace-nowrap">Timestamp</th>
                <th class="whitespace-nowrap">Performed By</th>
                <th class="whitespace-nowrap">Action</th>
                <th class="whitespace-nowrap">Target Entity</th>
                <th>Activity / What Happened</th>
                <th>Changes & Details</th>
                <th class="text-right whitespace-nowrap">IP Address</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-surface-border text-ink-900">
            @forelse($logs as $log)
                @php
                    $badge = \App\Services\AuditLogPresenter::formatActionBadge($log);
                    $story = \App\Services\AuditLogPresenter::formatStory($log);
                    $diff  = \App\Services\AuditLogPresenter::formatDiff($log);
                    $url   = \App\Services\AuditLogPresenter::getEntityUrl($log);
                    $rawModelName = class_basename($log->auditable_type ?? 'System');
                    $friendlyEntityName = match($rawModelName) {
                        'DayStart'              => 'Tour Day',
                        'LeaveBalance'          => 'Leave Quota',
                        'Leave'                 => 'Leave Request',
                        'ExpenseBill', 'Expense'=> 'Expense Claim',
                        'Order'                 => 'Order',
                        'User'                  => 'Employee',
                        'Customer', 'Dealer'    => 'Party',
                        'LeaveType'             => 'Leave Policy',
                        'AttendanceAdjustment'  => 'Attendance Adjustment',
                        default                 => \Illuminate\Support\Str::headline($rawModelName),
                    };
                @endphp
                <tr class="hover:bg-surface-muted transition-colors">
                    {{-- Timestamp --}}
                    <td class="whitespace-nowrap text-xs text-ink-500 font-mono">
                        <div class="font-bold text-ink-900">{{ $log->created_at->format('d M Y, h:i:s A') }}</div>
                        <div class="text-[11px] text-ink-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                    </td>

                    {{-- Admin / Actor --}}
                    <td class="whitespace-nowrap">
                        @if($log->causer)
                            <div class="font-bold text-ink-900 text-xs flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center text-[10px] font-bold">
                                    {{ strtoupper(substr($log->causer->name, 0, 1)) }}
                                </span>
                                <span>{{ $log->causer->name }}</span>
                            </div>
                            <div class="text-[11px] text-ink-400 font-mono ml-6.5">
                                {{ $log->causer->roles->pluck('name')->join(', ') ?: $log->causer->email }}
                            </div>
                        @else
                            <div class="flex items-center gap-1.5 text-xs text-ink-400 italic">
                                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-[10px]">⚙</span>
                                <span>System / Automated</span>
                            </div>
                        @endif
                    </td>

                    {{-- Action Badge (Human Readable) --}}
                    <td class="whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badge['class'] }}" title="Technical event: {{ $log->event }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                            <span>{{ $badge['label'] }}</span>
                        </span>
                        <div class="text-[10px] text-ink-400 font-mono mt-0.5 ml-1">
                            {{ $log->event }}
                        </div>
                    </td>

                    {{-- Target Entity --}}
                    <td class="whitespace-nowrap text-xs">
                        <div class="font-bold text-ink-800">{{ $friendlyEntityName }}</div>
                        @if($log->auditable_id)
                            @if($url)
                                <a href="{{ $url }}" class="text-brand-600 hover:text-brand-700 hover:underline font-mono text-[11px] inline-flex items-center gap-0.5">
                                    <span>#{{ $log->auditable_id }}</span>
                                    <svg class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @else
                                <span class="text-ink-400 font-mono text-[11px]">#{{ $log->auditable_id }}</span>
                            @endif
                        @endif
                    </td>

                    {{-- Activity / What Happened (Plain English Story) --}}
                    <td class="text-xs text-ink-800 max-w-sm">
                        <div class="font-medium leading-relaxed text-ink-900">
                            {{ $story }}
                        </div>
                        @if(!empty($log->reason) && !str_starts_with($log->reason, 'Created ') && !str_starts_with($log->reason, 'Updated ') && !str_starts_with($log->reason, 'Deleted '))
                            <div class="text-[11px] text-ink-500 mt-1 italic bg-surface-muted/50 px-2 py-0.5 rounded border border-surface-border/60 inline-block">
                                Remark: {{ $log->reason }}
                            </div>
                        @endif
                    </td>

                    {{-- Details & Changes (Humanized Diff) --}}
                    <td class="text-xs">
                        @if(!empty($diff))
                            <details class="cursor-pointer group">
                                <summary class="text-brand-600 hover:text-brand-700 font-medium select-none inline-flex items-center gap-1.5 py-1 px-2 rounded hover:bg-brand-50 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-brand-600 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span class="font-semibold">View Details</span>
                                    <span class="text-[10px] text-ink-500 bg-surface-muted px-1.5 py-0.5 rounded-full border border-surface-border">
                                        {{ count($diff) }} {{ \Illuminate\Support\Str::plural('field', count($diff)) }}
                                    </span>
                                </summary>
                                <div class="mt-2 p-3 rounded-lg bg-surface-muted border border-surface-border text-xs space-y-2 max-w-md max-h-64 overflow-y-auto shadow-sm">
                                    <div class="space-y-2 divide-y divide-surface-border">
                                        @foreach($diff as $item)
                                            <div class="pt-2 first:pt-0">
                                                <div class="flex items-center justify-between text-[11px] text-ink-500 mb-0.5">
                                                    <span class="font-bold text-ink-800">{{ $item['label'] }}</span>
                                                    <span class="font-mono text-[10px] text-ink-400">{{ $item['raw_key'] }}</span>
                                                </div>
                                                @if($item['has_old'] && $item['has_new'])
                                                    <div class="text-[11px] text-rose-600 line-through">
                                                        Was: {{ \Illuminate\Support\Str::limit($item['old'], 100) }}
                                                    </div>
                                                    <div class="text-[11px] text-emerald-700 font-semibold">
                                                        Now: {{ \Illuminate\Support\Str::limit($item['new'], 100) }}
                                                    </div>
                                                @elseif($item['has_new'])
                                                    <div class="text-[11px] text-emerald-700 font-semibold">
                                                        Set: {{ \Illuminate\Support\Str::limit($item['new'], 100) }}
                                                    </div>
                                                @elseif($item['has_old'])
                                                    <div class="text-[11px] text-rose-600">
                                                        Cleared: {{ \Illuminate\Support\Str::limit($item['old'], 100) }}
                                                    </div>
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
                                <button type="button" onclick="window.resetAuditLogFilters()" class="btn-secondary text-xs px-3.5 py-1.5">
                                    Clear All Filters
                                </button>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Unified Server-Side Pagination (Prev, numbers, Next) --}}
<div id="audit-logs-pagination" class="border-t border-surface-border px-4 py-3">
    {{ $logs->links() }}
</div>
