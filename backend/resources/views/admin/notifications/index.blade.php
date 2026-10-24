@extends('admin.layouts.app')

@section('title', 'Activity Notification Center')
@section('breadcrumb', 'Admin / Notifications')
@section('page_title', 'Activity Notification Center')

@section('content')
<div class="space-y-6">

    {{-- ═════ Stats Overview Cards ═════ --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {{-- Total Unread --}}
        <div class="panel p-4 flex items-center gap-3.5 border-l-4 border-l-rose-500">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                <x-lucide-bell class="w-5 h-5" />
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Unread Alerts</p>
                <h3 class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['unread']) }}</h3>
            </div>
        </div>

        {{-- Today's Total Activities --}}
        <div class="panel p-4 flex items-center gap-3.5 border-l-4 border-l-brand-500">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <x-lucide-activity class="w-5 h-5" />
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Today's Activities</p>
                <h3 class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['today']) }}</h3>
            </div>
        </div>

        {{-- Visits Today --}}
        <div class="panel p-4 flex items-center gap-3.5 border-l-4 border-l-blue-500">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <x-lucide-map-pin class="w-5 h-5" />
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Visits Today</p>
                <h3 class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['visits']) }}</h3>
            </div>
        </div>

        {{-- Orders Today --}}
        <div class="panel p-4 flex items-center gap-3.5 border-l-4 border-l-emerald-500">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <x-lucide-shopping-bag class="w-5 h-5" />
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Orders Today</p>
                <h3 class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['orders']) }}</h3>
            </div>
        </div>
    </div>

    {{-- ═════ Filters & Actions Panel ═════ --}}
    <div class="panel">
        <div class="panel-header border-b border-slate-200/80 p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-950">User Activity Stream</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Real-time alerts for tours, dealer check-ins, bookings, claims & collections.</p>
                </div>

                {{-- Global Actions --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if($stats['unread'] > 0)
                        <form method="POST" action="{{ route('admin.notifications.read-all') }}" id="markAllReadForm">
                            @csrf
                            <button type="submit" class="btn-primary text-xs px-3.5 py-2 flex items-center gap-1.5 shadow-sm">
                                <x-lucide-check-check class="w-4 h-4" />
                                <span>Mark All Read</span>
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.notifications.clear-read') }}" id="clearReadForm">
                        @csrf
                        <button type="submit" class="btn-secondary text-xs px-3.5 py-2 text-slate-600 hover:text-slate-800 flex items-center gap-1.5">
                            <x-lucide-trash-2 class="w-3.5 h-3.5 text-slate-400" />
                            <span>Clear Read</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Category Filter Tabs --}}
            @php
                $currentCat = request('category', 'all');
                $currentStatus = request('status', 'all');
            @endphp
            <div class="flex items-center gap-1.5 overflow-x-auto pt-4 border-t border-slate-100 mt-4 text-xs font-semibold no-scrollbar">
                <a href="{{ request()->fullUrlWithQuery(['category' => 'all', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $currentCat === 'all' ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    All Activities
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'unread', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap flex items-center gap-1.5 {{ $currentStatus === 'unread' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    <span>Unread Alerts</span>
                    @if($stats['unread'] > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'unread' ? 'bg-rose-800 text-white' : 'bg-rose-200 text-rose-900' }}">{{ $stats['unread'] }}</span>
                    @endif
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'visit', 'status' => 'all', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $currentCat === 'visit' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    📍 Visits
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'order', 'status' => 'all', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $currentCat === 'order' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    🛒 Orders
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'day_start', 'status' => 'all', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $currentCat === 'day_start' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    🚗 Day Starts / Ends
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'expense', 'status' => 'all', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $currentCat === 'expense' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    🧾 Expenses
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'payment', 'status' => 'all', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $currentCat === 'payment' ? 'bg-teal-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    💰 Payments
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'leave', 'status' => 'all', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $currentCat === 'leave' ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    🏖️ Leaves
                </a>
            </div>

            {{-- Filter Form --}}
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 mt-4 pt-3 border-t border-slate-100">
                <input type="hidden" name="category" value="{{ request('category', 'all') }}">
                <input type="hidden" name="status" value="{{ request('status', 'all') }}">

                {{-- Search --}}
                <div class="sm:col-span-5">
                    <div class="relative">
                        <x-lucide-search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search by employee, party, message..."
                               class="form-input text-xs pl-9 w-full rounded-lg">
                    </div>
                </div>

                {{-- From Date --}}
                <div class="sm:col-span-3">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-xs w-full rounded-lg" title="From Date">
                </div>

                {{-- To Date --}}
                <div class="sm:col-span-3">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-xs w-full rounded-lg" title="To Date">
                </div>

                {{-- Submit & Reset --}}
                <div class="sm:col-span-1 flex items-center gap-1.5">
                    <button type="submit" class="btn-primary text-xs py-2 px-3 w-full justify-center">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                        <a href="{{ route('admin.notifications.index') }}" class="btn-secondary text-xs py-2 px-2.5 text-slate-500 hover:text-slate-800" title="Reset Filters">
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ═════ Notification Feed List ═════ --}}
        <div class="divide-y divide-slate-100">
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
                            <a href="{{ route('admin.notifications.index') }}" class="btn-secondary text-xs px-4 py-2">
                                Clear All Filters
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($notifications->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Mark All Read
    const markAllForm = document.getElementById('markAllReadForm');
    if (markAllForm) {
        markAllForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = markAllForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                const res = await fetch(markAllForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(markAllForm)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.toast) window.toast(data.message || 'All notifications marked as read.', 'success');
                    // Visually mark all items as read
                    document.querySelectorAll('.notification-item.is-unread').forEach(item => {
                        item.classList.remove('bg-amber-50/30', 'is-unread');
                        const dot = item.querySelector('.status-dot');
                        if (dot) dot.innerHTML = '<span class="flex h-2 w-2 rounded-full bg-slate-300" title="Read"></span>';
                        const singleReadBtn = item.querySelector('.mark-single-read-form');
                        if (singleReadBtn) singleReadBtn.remove();
                    });
                    markAllForm.remove();
                }
            } catch (err) {
                console.error(err);
                if (window.toast) window.toast('Network error.', 'error');
                btn.disabled = false;
            }
        });
    }

    // 2. Clear Read
    const clearReadForm = document.getElementById('clearReadForm');
    if (clearReadForm) {
        clearReadForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!confirm('Clear all read notifications from your history?')) return;
            const btn = clearReadForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                const res = await fetch(clearReadForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(clearReadForm)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.toast) window.toast(data.message || 'Read notifications cleared.', 'success');
                    // Remove all read items from the list
                    document.querySelectorAll('.notification-item:not(.is-unread)').forEach(item => item.remove());
                } else {
                    if (window.toast) window.toast(data.message || 'Failed to clear read notifications.', 'error');
                }
            } catch (err) {
                console.error(err);
                if (window.toast) window.toast('Network error.', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    }

    // 3. Mark Single Read
    document.addEventListener('submit', async function (e) {
        const singleReadForm = e.target.closest('.mark-single-read-form');
        if (!singleReadForm) return;
        e.preventDefault();
        const item = singleReadForm.closest('.notification-item');
        try {
            const res = await fetch(singleReadForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(singleReadForm)
            });
            const data = await res.json();
            if (res.ok && data.success) {
                if (window.toast) window.toast(data.message || 'Marked as read.', 'success');
                if (item) {
                    item.classList.remove('bg-amber-50/30', 'is-unread');
                    const dot = item.querySelector('.status-dot');
                    if (dot) dot.innerHTML = '<span class="flex h-2 w-2 rounded-full bg-slate-300" title="Read"></span>';
                    singleReadForm.remove();
                }
            }
        } catch (err) {
            console.error(err);
        }
    });

    // 4. Delete Single Notification
    document.addEventListener('submit', async function (e) {
        const deleteForm = e.target.closest('.delete-single-form');
        if (!deleteForm) return;
        e.preventDefault();
        if (!confirm('Delete this notification?')) return;
        const item = deleteForm.closest('.notification-item');
        try {
            const res = await fetch(deleteForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(deleteForm)
            });
            const data = await res.json();
            if (res.ok && data.success) {
                if (window.toast) window.toast(data.message || 'Notification deleted.', 'success');
                if (item) item.remove();
            } else {
                if (window.toast) window.toast(data.message || 'Failed to delete notification.', 'error');
            }
        } catch (err) {
            console.error(err);
            if (window.toast) window.toast('Network error.', 'error');
        }
    });
});
</script>
@endpush
