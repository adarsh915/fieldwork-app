<div
    class="relative"
    x-data="{ open: false }"
    @click.outside="open = false"
>
    @php
        use Illuminate\Support\Facades\Schema;
        $notificationsEnabled = Schema::hasTable('notifications');
        $unreadNotifications  = ($notificationsEnabled && auth()->check())
            ? (auth()->user()?->unreadNotifications ?? collect())
            : collect();
    @endphp

    <button
        @click="open = !open"
        class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-surface-border bg-white hover:bg-surface-muted transition-colors"
        aria-label="Notifications"
    >
        <x-lucide-bell class="h-4 w-4 text-ink-600" />
        @if ($unreadNotifications->count() > 0)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ $unreadNotifications->count() }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition
        class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-surface-border bg-white shadow-xl"
        style="display: none;"
    >
        <div class="flex items-center justify-between border-b border-surface-border px-4 py-3">
            <span class="text-sm font-semibold text-ink-900">Notifications</span>
            @if ($unreadNotifications->count() > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                        Mark all read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-surface-border">
            @unless ($notificationsEnabled)
                <div class="px-4 py-6 text-center text-sm text-warning-text bg-warning-bg">
                    Notifications table missing. Run <code class="rounded bg-yellow-100 px-1">php artisan migrate</code>.
                </div>
            @else
                @forelse ($unreadNotifications as $notification)
                    <div class="px-4 py-3">
                        <p class="text-sm text-ink-900">{{ $notification->data['message'] ?? 'New notification' }}</p>
                        <p class="mt-1 text-xs text-ink-400">{{ $notification->created_at->diffForHumans() }}</p>
                        <div class="mt-2 flex gap-3">
                            <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Mark read</button>
                            </form>
                            @if (!empty($notification->data['day_start_id']))
                                <a href="{{ route('admin.day-starts.show', $notification->data['day_start_id']) }}"
                                   class="text-xs font-semibold text-ink-600 hover:text-ink-900">View</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-ink-400">No unread notifications.</div>
                @endforelse
            @endunless
        </div>
    </div>
</div>
