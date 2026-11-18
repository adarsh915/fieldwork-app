{{-- ═════ Notification Feed List (AJAX Refreshable) ═════ --}}
<div class="divide-y divide-slate-100" id="notification-feed-list">
    @forelse ($notifications as $notification)
        @php
            $data = $notification->data;
            $isUnread = is_null($notification->read_at);
            $cat = $data['category'] ?? 'general';

            $badgeStyles = match($cat) {
                'order'     => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500', 'label' => 'Order Booked'],
                'visit'     => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'dot' => 'bg-blue-500', 'label' => 'Party Visit'],
                'expense'   => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'dot' => 'bg-amber-500', 'label' => 'Expense Claim'],
                'payment'   => ['bg' => 'bg-teal-50 text-teal-700 border-teal-200', 'dot' => 'bg-teal-500', 'label' => 'Payment'],
                'leave'     => ['bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'dot' => 'bg-purple-500', 'label' => 'Leave App'],
                'day_start' => ['bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'dot' => 'bg-indigo-500', 'label' => 'Tour Plan / Day'],
                default     => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'dot' => 'bg-slate-400', 'label' => 'General Alert'],
            };

            $actionUrl = $data['url'] ?? null;
            if (!$actionUrl && !empty($data['day_start_id'])) {
                $anchor = match ($data['category'] ?? '') {
                    'visit'   => '#visits',
                    'expense' => '#expenses',
                    'order'   => '#orders',
                    default   => '',
                };
                $actionUrl = route('admin.day-starts.show', $data['day_start_id']) . $anchor;
            }
        @endphp

        <div class="notification-item p-4 sm:px-6 hover:bg-slate-50/70 transition-colors {{ $isUnread ? 'bg-amber-50/30 is-unread' : '' }}" data-id="{{ $notification->id }}">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3.5 min-w-0">
                    {{-- Status Indicator Dot --}}
                    <div class="pt-1.5 shrink-0 status-dot">
                        @if($isUnread)
                            <span class="flex h-2.5 w-2.5 rounded-full bg-rose-500 ring-4 ring-rose-100" title="Unread"></span>
                        @else
                            <span class="flex h-2 w-2 rounded-full bg-slate-300" title="Read"></span>
                        @endif
                    </div>

                    {{-- Notification Content --}}
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $badgeStyles['bg'] }}">
                                {{ $badgeStyles['label'] }}
                            </span>
                            @if(!empty($data['title']))
                                <span class="text-xs font-bold text-slate-900">{{ $data['title'] }}</span>
                            @endif
                            @if(!empty($data['is_updated']))
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    EDITED
                                </span>
                            @endif
                            <span class="text-slate-300 text-xs">&bull;</span>
                            <span class="text-xs text-slate-400 font-medium">{{ $notification->created_at->format('d M Y, h:i A') }} ({{ $notification->created_at->diffForHumans() }})</span>
                        </div>

                        <p class="text-sm text-slate-800 font-medium leading-relaxed">
                            {{ $data['message'] ?? 'User activity recorded.' }}
                        </p>

                        @if(!empty($data['user_name']))
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span>Logged by: <strong class="text-slate-700">{{ $data['user_name'] }}</strong></span>
                                @if(!empty($data['amount']))
                                    <span>&bull;</span>
                                    <span>Amount: <strong class="text-emerald-700">₹{{ number_format((float) $data['amount'], 2) }}</strong></span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 shrink-0">
                    @if ($actionUrl)
                        <a href="{{ $actionUrl }}"
                           class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1 shadow-sm font-semibold">
                            <span>View</span>
                            <span class="text-[10px]">↗</span>
                        </a>
                    @endif

                    @if($isUnread)
                        <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" class="mark-single-read-form">
                            @csrf
                            <button type="submit" class="btn-secondary text-xs py-1.5 px-2.5 text-slate-600 hover:text-slate-900" title="Mark as Read">
                                <x-lucide-check class="w-3.5 h-3.5" />
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.notifications.destroy', $notification->id) }}" class="delete-single-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-slate-300 hover:text-rose-600 p-1.5 rounded transition-colors" title="Delete Notification">
                            <x-lucide-trash class="w-3.5 h-3.5" />
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="py-16 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
                <x-lucide-bell-off class="w-7 h-7" />
            </div>
            <h3 class="text-base font-bold text-slate-800">No Notifications Found</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">There are no user activity notifications matching your current filter criteria.</p>
            @if(request()->anyFilled(['category', 'status', 'search', 'date_from', 'date_to']))
                <div class="mt-4">
                    <button type="button" onclick="window.resetNotificationFilters()" class="btn-secondary text-xs px-4 py-2">
                        Clear All Filters
                    </button>
                </div>
            @endif
        </div>
    @endforelse
</div>

{{-- Unified Server-Side Pagination --}}
<div id="notification-pagination" class="border-t border-slate-100 px-4 py-3">
    {{ $notifications->links() }}
</div>

