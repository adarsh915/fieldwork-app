@php
    use Illuminate\Support\Facades\Schema;
    $notificationsEnabled = Schema::hasTable('notifications');
    $initialNotifications = ($notificationsEnabled && auth()->check())
        ? auth()->user()->unreadNotifications()->latest()->take(10)->get()->map(function($n) {
            $data = $n->data;
            $url = $data['url'] ?? null;
            if (!$url && !empty($data['day_start_id'])) {
                $anchor = match ($data['category'] ?? '') {
                    'visit'   => '#visits',
                    'expense' => '#expenses',
                    'order'   => '#orders',
                    default   => '',
                };
                $url = route('admin.day-starts.show', $data['day_start_id']) . $anchor;
            }

            return [
                'id'         => $n->id,
                'title'      => $data['title'] ?? 'Activity Notification',
                'message'    => $data['message'] ?? 'New activity recorded',
                'category'   => $data['category'] ?? 'general',
                'icon'       => $data['icon'] ?? 'bell',
                'url'        => $url,
                'read_url'   => route('admin.notifications.read', $n->id),
                'is_updated' => !empty($data['is_updated']),
                'created_at' => $n->created_at->diffForHumans(null, false, true),
            ];
        })->values()->all()
        : [];
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        unreadCount: {{ auth()->check() ? auth()->user()->unreadNotifications()->count() : 0 }},
        notifications: {{ Js::from($initialNotifications) }},
        csrfToken: '{{ csrf_token() }}',
        readAllUrl: '{{ route('admin.notifications.read-all') }}',
        feedUrl: '{{ route('admin.notifications.feed') }}',

        async fetchFeed() {
            if (document.hidden) return;
            try {
                const res = await fetch(this.feedUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        this.unreadCount = data.unread_count;
                        this.notifications = data.notifications;
                    }
                }
            } catch (e) {
                // Silently ignore network errors during background polling
            }
        },

        async markOneRead(item, event) {
            if (event) event.stopPropagation();
            this.notifications = this.notifications.filter(n => n.id !== item.id);
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            try {
                await fetch(item.read_url, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } catch (e) {
                console.error('Failed to mark notification read', e);
            }
        },

        async markAllRead() {
            this.notifications = [];
            this.unreadCount = 0;
            try {
                await fetch(this.readAllUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } catch (e) {
                console.error('Failed to mark all read', e);
            }
        },

        getCatStyle(cat) {
            const map = {
                order:     { label: 'Order Booked', bg: 'bg-emerald-50 text-emerald-600 border-emerald-200/60', icon: 'order' },
                visit:     { label: 'Party Visit',  bg: 'bg-blue-50 text-blue-600 border-blue-200/60',          icon: 'visit' },
                expense:   { label: 'Expense Claim', bg: 'bg-amber-50 text-amber-600 border-amber-200/60',     icon: 'expense' },
                payment:   { label: 'Payment',       bg: 'bg-teal-50 text-teal-600 border-teal-200/60',          icon: 'payment' },
                leave:     { label: 'Leave',         bg: 'bg-purple-50 text-purple-600 border-purple-200/60',      icon: 'leave' },
                day_start: { label: 'Tour / Day',    bg: 'bg-indigo-50 text-indigo-600 border-indigo-200/60',      icon: 'tour' },
            };
            return map[cat] || { label: 'System', bg: 'bg-slate-100 text-slate-600 border-slate-200/60', icon: 'bell' };
        }
    }"
    x-init="
        setInterval(() => fetchFeed(), 30000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) fetchFeed(); });
    "
    @click.outside="open = false"
>
    {{-- Bell Icon Trigger --}}
    <button
        type="button"
        @click="open = !open; if(open) fetchFeed()"
        class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-surface-border bg-white hover:bg-surface-muted transition-colors focus:outline-none cursor-pointer"
        aria-label="Notifications"
        title="Activity Notifications"
    >
        <x-lucide-bell class="h-4 w-4 text-ink-600" />
        <span
            x-show="unreadCount > 0"
            x-text="unreadCount > 99 ? '99+' : unreadCount"
            style="position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 9999px; font-size: 10px; font-weight: 700; background-color: #ef4444; color: #ffffff; display: flex; align-items: center; justify-content: center; border: 2px solid #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.15);"
            class="animate-pulse"
        >
            {{ count($initialNotifications) }}
        </span>
    </button>

    {{-- Dropdown Card --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl"
        style="display: none; width: 380px; max-width: calc(100vw - 2rem);"
    >
        {{-- Card Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 bg-slate-50/80">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-slate-900 tracking-tight">Activity Alerts</h3>
                <span
                    x-show="unreadCount > 0"
                    x-text="unreadCount + ' new'"
                    style="padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe;"
                >
                    {{ count($initialNotifications) }} new
                </span>
            </div>

            <template x-if="unreadCount > 0">
                <button
                    type="button"
                    @click="markAllRead()"
                    class="text-xs font-semibold text-brand-600 hover:text-brand-800 hover:underline cursor-pointer"
                >
                    Mark all read
                </button>
            </template>
        </div>

        {{-- Notifications List --}}
        <div class="max-h-[22rem] overflow-y-auto divide-y divide-slate-100">
            {{-- Dynamic list rendering in real-time --}}
            <template x-for="item in notifications" :key="item.id">
                <div
                    class="p-3.5 hover:bg-slate-50/80 transition-colors"
                    :class="item.url ? 'cursor-pointer' : ''"
                    @click="if (item.url) { markOneRead(item); window.location.href = item.url; }"
                >
                    <div class="flex items-start gap-3">
                        {{-- Category Icon Circle --}}
                        <div class="shrink-0 mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl border" :class="getCatStyle(item.category).bg">
                            {{-- Visit --}}
                            <template x-if="getCatStyle(item.category).icon === 'visit'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </template>
                            {{-- Order --}}
                            <template x-if="getCatStyle(item.category).icon === 'order'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </template>
                            {{-- Expense --}}
                            <template x-if="getCatStyle(item.category).icon === 'expense'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </template>
                            {{-- Payment --}}
                            <template x-if="getCatStyle(item.category).icon === 'payment'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2" stroke-width="2"/><circle cx="12" cy="12" r="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M6 12h.01M18 12h.01"/></svg>
                            </template>
                            {{-- Leave --}}
                            <template x-if="getCatStyle(item.category).icon === 'leave'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke-width="2"/></svg>
                            </template>
                            {{-- Tour --}}
                            <template x-if="getCatStyle(item.category).icon === 'tour'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0m-14-3h16l-1.5-6h-13zM3 14h18v3H3z"/></svg>
                            </template>
                            {{-- Bell Fallback --}}
                            <template x-if="!['visit','order','expense','payment','leave','tour'].includes(getCatStyle(item.category).icon)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </template>
                        </div>

                        {{-- Notification Body --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                <div class="flex items-center gap-1.5 min-w-0 truncate">
                                    <span class="text-xs font-bold text-slate-900 truncate" x-text="item.title || getCatStyle(item.category).label"></span>
                                    <template x-if="item.is_updated">
                                        <span style="font-size: 9px; padding: 1px 4px; border-radius: 4px; background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 700; letter-spacing: 0.02em;">EDITED</span>
                                    </template>
                                </div>
                                <span class="text-[10px] font-medium text-slate-400 shrink-0" x-text="item.created_at"></span>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed break-words" x-text="item.message"></p>

                            {{-- Action Link Bar --}}
                            <div class="mt-2.5 flex items-center justify-between gap-2 pt-1 border-t border-slate-100/70">
                                <template x-if="item.url">
                                    <a
                                        :href="item.url"
                                        @click.stop="markOneRead(item)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors"
                                    >
                                        <span>View Details</span>
                                        <span class="text-[10px]">➔</span>
                                    </a>
                                </template>
                                <template x-if="!item.url">
                                    <span></span>
                                </template>

                                <button
                                    type="button"
                                    @click.stop="markOneRead(item, $event)"
                                    class="text-[11px] font-medium text-slate-400 hover:text-slate-600 hover:underline cursor-pointer"
                                >
                                    Mark read
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="notifications.length === 0">
                <div class="px-4 py-10 text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-2">
                        <x-lucide-bell-off class="w-5 h-5" />
                    </div>
                    <p class="text-xs font-semibold text-slate-700">No unread notifications</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">You're all caught up with user activity.</p>
                </div>
            </template>
        </div>

        {{-- Card Footer --}}
        <div class="border-t border-slate-100 p-2.5 bg-slate-50/60">
            <a href="{{ route('admin.notifications.index') }}"
               class="flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-lg text-xs font-bold text-brand-600 bg-white border border-slate-200/80 hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700 transition-all shadow-xs">
                <span>View Full Notification Center</span>
                <span class="text-xs">➔</span>
            </a>
        </div>
    </div>
</div>
