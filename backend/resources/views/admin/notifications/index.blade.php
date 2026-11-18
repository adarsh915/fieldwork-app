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
            <div id="categoryTabsContainer" class="flex items-center gap-1.5 overflow-x-auto pt-4 border-t border-slate-100 mt-4 text-xs font-semibold no-scrollbar">
                <a href="{{ request()->fullUrlWithQuery(['category' => 'all', 'status' => 'all', 'page' => 1]) }}"
                   data-cat="all" data-status="all"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentCat === 'all' && $currentStatus !== 'unread' ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <x-lucide-activity class="w-3.5 h-3.5" />
                    <span>All Activities</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'unread', 'category' => 'all', 'page' => 1]) }}"
                   data-cat="all" data-status="unread"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentStatus === 'unread' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    <x-lucide-bell class="w-3.5 h-3.5" />
                    <span>Unread Alerts</span>
                    @if($stats['unread'] > 0)
                        <span id="unreadTabBadge" class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'unread' ? 'bg-rose-800 text-white' : 'bg-rose-200 text-rose-900' }}">{{ $stats['unread'] }}</span>
                    @endif
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'visit', 'status' => 'all', 'page' => 1]) }}"
                   data-cat="visit" data-status="all"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentCat === 'visit' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <x-lucide-map-pin class="w-3.5 h-3.5" />
                    <span>Visits</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'order', 'status' => 'all', 'page' => 1]) }}"
                   data-cat="order" data-status="all"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentCat === 'order' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <x-lucide-shopping-cart class="w-3.5 h-3.5" />
                    <span>Orders</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'day_start', 'status' => 'all', 'page' => 1]) }}"
                   data-cat="day_start" data-status="all"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentCat === 'day_start' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <x-lucide-calendar-clock class="w-3.5 h-3.5" />
                    <span>Day Starts / Ends</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'expense', 'status' => 'all', 'page' => 1]) }}"
                   data-cat="expense" data-status="all"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentCat === 'expense' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <x-lucide-receipt class="w-3.5 h-3.5" />
                    <span>Expenses</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'payment', 'status' => 'all', 'page' => 1]) }}"
                   data-cat="payment" data-status="all"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentCat === 'payment' ? 'bg-teal-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <x-lucide-banknote class="w-3.5 h-3.5" />
                    <span>Payments</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'leave', 'status' => 'all', 'page' => 1]) }}"
                   data-cat="leave" data-status="all"
                   class="category-tab px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap inline-flex items-center gap-1.5 {{ $currentCat === 'leave' ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <x-lucide-calendar-off class="w-3.5 h-3.5" />
                    <span>Leaves</span>
                </a>
            </div>

            {{-- Filter Form --}}
            <form id="notificationFilterForm" method="GET" action="{{ route('admin.notifications.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 mt-4 pt-3 border-t border-slate-100">
                <input type="hidden" name="category" id="filterCategoryInput" value="{{ request('category', 'all') }}">
                <input type="hidden" name="status" id="filterStatusInput" value="{{ request('status', 'all') }}">

                {{-- Search --}}
                <div class="sm:col-span-5">
                    <div class="relative">
                        <x-lucide-search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input type="text" name="search" id="filterSearchInput" value="{{ request('search') }}"
                               placeholder="Search by employee, party, message..."
                               class="form-input text-xs pl-9 w-full rounded-lg">
                    </div>
                </div>

                {{-- From Date --}}
                <div class="sm:col-span-3">
                    <input type="date" name="date_from" id="filterDateFromInput" value="{{ request('date_from') }}" class="form-input text-xs w-full rounded-lg" title="From Date">
                </div>

                {{-- To Date --}}
                <div class="sm:col-span-3">
                    <input type="date" name="date_to" id="filterDateToInput" value="{{ request('date_to') }}" class="form-input text-xs w-full rounded-lg" title="To Date">
                </div>

                {{-- Submit & Reset --}}
                <div class="sm:col-span-1 flex items-center gap-1.5">
                    <button type="submit" class="btn-primary text-xs py-2 px-3 w-full justify-center">
                        Filter
                    </button>
                    <button type="button" onclick="window.resetNotificationFilters()" class="btn-secondary text-xs py-2 px-2.5 text-slate-500 hover:text-slate-800" title="Reset Filters">
                        ✕
                    </button>
                </div>
            </form>
        </div>

        {{-- ═════ Notification Feed Stream Wrapper (AJAX) ═════ --}}
        <div id="notifications-stream-wrapper" class="relative transition-opacity duration-150">
            @include('admin.notifications.partials.feed-stream')
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const streamWrapper = document.getElementById('notifications-stream-wrapper');
    const filterForm = document.getElementById('notificationFilterForm');

    // ── Live AJAX Stream Loader (Zero page reload) ──
    window.loadNotificationsStream = async function (url, pushState = true) {
        if (!streamWrapper) return;
        streamWrapper.style.opacity = '0.4';
        streamWrapper.style.pointerEvents = 'none';

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await res.json();
            if (res.ok && data.success && data.html) {
                streamWrapper.innerHTML = data.html;

                // Sync URL in address bar without reload
                if (pushState) {
                    window.history.pushState(null, '', url);
                }

                // Update active tab styles based on current URL params
                const parsedUrl = new URL(url, window.location.origin);
                const currentCat = parsedUrl.searchParams.get('category') || 'all';
                const currentStatus = parsedUrl.searchParams.get('status') || 'all';

                // Sync hidden inputs
                const catInput = document.getElementById('filterCategoryInput');
                const statusInput = document.getElementById('filterStatusInput');
                if (catInput) catInput.value = currentCat;
                if (statusInput) statusInput.value = currentStatus;

                // Sync tab active classes
                document.querySelectorAll('.category-tab').forEach(tab => {
                    const tabCat = tab.getAttribute('data-cat');
                    const tabStatus = tab.getAttribute('data-status');
                    const isActive = (tabCat === currentCat) && (tabStatus === currentStatus || (tabStatus === 'all' && currentStatus !== 'unread'));

                    if (isActive) {
                        tab.classList.remove('bg-slate-100', 'text-slate-700', 'bg-rose-50', 'text-rose-700');
                        if (tabStatus === 'unread') {
                            tab.classList.add('bg-rose-600', 'text-white', 'shadow-sm');
                        } else if (tabCat === 'visit') {
                            tab.classList.add('bg-blue-600', 'text-white', 'shadow-sm');
                        } else if (tabCat === 'order') {
                            tab.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');
                        } else if (tabCat === 'day_start') {
                            tab.classList.add('bg-indigo-600', 'text-white', 'shadow-sm');
                        } else if (tabCat === 'expense') {
                            tab.classList.add('bg-amber-600', 'text-white', 'shadow-sm');
                        } else if (tabCat === 'payment') {
                            tab.classList.add('bg-teal-600', 'text-white', 'shadow-sm');
                        } else if (tabCat === 'leave') {
                            tab.classList.add('bg-purple-600', 'text-white', 'shadow-sm');
                        } else {
                            tab.classList.add('bg-brand-600', 'text-white', 'shadow-sm');
                        }
                    } else {
                        tab.classList.remove(
                            'bg-brand-600', 'bg-rose-600', 'bg-blue-600', 'bg-emerald-600', 
                            'bg-indigo-600', 'bg-amber-600', 'bg-teal-600', 'bg-purple-600', 
                            'text-white', 'shadow-sm'
                        );
                        if (tabStatus === 'unread') {
                            tab.classList.add('bg-rose-50', 'text-rose-700');
                        } else {
                            tab.classList.add('bg-slate-100', 'text-slate-700');
                        }
                    }
                });

                // Smooth scroll back to top of feed
                const panel = streamWrapper.closest('.panel');
                if (panel) {
                    const rect = panel.getBoundingClientRect();
                    if (rect.top < 0) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            }
        } catch (err) {
            console.error('Failed to load notifications stream:', err);
            if (window.toast) window.toast('Failed to load notifications.', 'error');
        } finally {
            streamWrapper.style.opacity = '1';
            streamWrapper.style.pointerEvents = 'auto';
        }
    };

    // ── Pagination Clicks (Zero Reload) ──
    document.addEventListener('click', function (e) {
        const pageLink = e.target.closest('#notification-pagination a');
        if (pageLink && pageLink.href) {
            e.preventDefault();
            window.loadNotificationsStream(pageLink.href);
        }
    });

    // ── Category Tab Clicks (Zero Reload) ──
    document.addEventListener('click', function (e) {
        const tab = e.target.closest('.category-tab');
        if (tab && tab.href) {
            e.preventDefault();
            window.loadNotificationsStream(tab.href);
        }
    });

    // ── Filter Form Submit (Zero Reload) ──
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            params.set('page', '1');
            const url = filterForm.action + '?' + params.toString();
            window.loadNotificationsStream(url);
        });
    }

    // ── Reset Filters ──
    window.resetNotificationFilters = function () {
        if (filterForm) {
            filterForm.reset();
            const catInput = document.getElementById('filterCategoryInput');
            const statusInput = document.getElementById('filterStatusInput');
            if (catInput) catInput.value = 'all';
            if (statusInput) statusInput.value = 'all';
        }
        window.loadNotificationsStream('{{ route('admin.notifications.index') }}');
    };

    // ── Browser Back / Forward Button Support ──
    window.addEventListener('popstate', function () {
        window.loadNotificationsStream(window.location.href, false);
    });
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
