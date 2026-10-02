<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — AMPL Fieldwork</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Quill Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="admin-shell">

<div
    class="min-h-screen lg:flex"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: $persist(false).as('sidebar_collapsed')
    }"
>
    {{-- ════════════════════════════════════════════════════════════ --}}
    {{--  SIDEBAR                                                      --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <aside
        class="admin-sidebar"
        :class="{
            'w-64': !sidebarCollapsed,
            'w-16': sidebarCollapsed,
            '-translate-x-full lg:translate-x-0': !sidebarOpen && !sidebarCollapsed,
            'translate-x-0': sidebarOpen
        }"
    >
        <div class="flex h-full flex-col overflow-hidden">

            {{-- Logo / brand --}}
            <div class="flex h-16 items-center justify-between border-b border-surface-border px-4">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-black text-white shadow-sm">AF</span>
                    <span x-show="!sidebarCollapsed" class="min-w-0">
                        <span class="block text-sm font-bold text-ink-900 truncate">AMPL Fieldwork</span>
                        <span class="block text-xs font-medium text-ink-400 truncate">Control Center</span>
                    </span>
                </a>
                <button
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="hidden lg:flex h-8 w-8 items-center justify-center rounded-lg hover:bg-surface-muted transition-colors"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                >
                    <x-lucide-panel-left class="w-4 h-4 text-ink-400" />
                </button>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 space-y-0.5">

                {{-- Overview --}}
                <p x-show="!sidebarCollapsed" class="sidebar-section-label uppercase tracking-wider">Overview</p>
                <div class="px-2 space-y-0.5">
                    <a href="{{ route('admin.dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Dashboard' : ''">
                        <x-lucide-layout-dashboard class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Dashboard</span>
                    </a>
                </div>

                {{-- Fieldwork --}}
                <p x-show="!sidebarCollapsed" class="sidebar-section-label uppercase tracking-wider">Fieldwork</p>
                <div class="px-2 space-y-0.5">
                    @can('customers.view')
                    <a href="{{ route('admin.parties.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.parties.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Parties' : ''">
                        <x-lucide-users class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Parties</span>
                    </a>
                    @endcan

                    @canany(['day-start.view-team', 'day-start.view-all', 'day-start.view-own', 'day-start.view'])
                    <a href="{{ route('admin.day-starts.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.day-starts.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Day Start' : ''">
                        <x-lucide-calendar-check class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Day Start</span>
                    </a>
                    @endcanany

                    @can('orders.view')
                    <a href="{{ route('admin.orders.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Orders' : ''">
                        <x-lucide-shopping-cart class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Orders</span>
                    </a>
                    @endcan

                    @can('payments.view')
                    <a href="{{ route('admin.payments.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Payments' : ''">
                        <x-lucide-banknote class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Payments</span>
                    </a>
                    @endcan

                    @can('visits.view')
                    <a href="{{ route('admin.visits.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.visits.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Visits' : ''">
                        <x-lucide-map-pin class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Visits</span>
                    </a>
                    @endcan

                    @can('reports.view')
                    <a href="{{ route('admin.reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Reports' : ''">
                        <x-lucide-bar-chart-2 class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Reports</span>
                    </a>
                    @endcan

                    @can('expenses.view')
                    <a href="{{ route('admin.expenses.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.expenses.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Expenses' : ''">
                        <x-lucide-receipt class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Expense Bill</span>
                    </a>
                    @endcan

                    @can('leave.view')
                    <a href="{{ route('admin.leave.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.leave.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Leave' : ''">
                        <x-lucide-calendar class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Leave</span>
                    </a>
                    @endcan

                    @can('meetings.view')
                    <a href="{{ route('admin.meetings.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.meetings.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Meetings' : ''">
                        <x-lucide-calendar-clock class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">My Meeting</span>
                    </a>
                    @endcan

                    @can('product-demo.view')
                    <a href="{{ route('admin.product-demo.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.product-demo.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Product Demo' : ''">
                        <x-lucide-presentation class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Product Demo</span>
                    </a>
                    @endcan

                    @can('deals.view')
                    <a href="{{ route('admin.deals.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.deals.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'New Deal' : ''">
                        <x-lucide-handshake class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">New Deal</span>
                    </a>
                    @endcan

                    @can('dealers.view')
                    <a href="{{ route('admin.dealers.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.dealers.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Dealers' : ''">
                        <x-lucide-store class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Dealer List</span>
                    </a>
                    @endcan

                    @can('activities.view')
                    <a href="{{ route('admin.activities.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.activities.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Activities' : ''">
                        <x-lucide-activity class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Activities</span>
                    </a>
                    @endcan
                </div>

                {{-- Management --}}
                <p x-show="!sidebarCollapsed" class="sidebar-section-label uppercase tracking-wider">Management</p>
                <div class="px-2 space-y-0.5">
                    @can('employees.view')
                    <a href="{{ route('admin.employees.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.employees.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Employees' : ''">
                        <x-lucide-id-card class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Employees</span>
                    </a>
                    @endcan

                    @can('users.view')
                    <a href="{{ route('admin.users.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Users' : ''">
                        <x-lucide-user-cog class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Users</span>
                    </a>
                    @endcan

                    @role('Super Admin')
                    <a href="{{ route('admin.roles.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Roles & Permissions' : ''">
                        <x-lucide-shield-check class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Roles & Permissions</span>
                    </a>
                    @endrole

                    @can('master-data.manage')
                    <a href="{{ route('admin.master-data.index', 'tour-purposes') }}"
                       class="sidebar-link {{ request()->routeIs('admin.master-data.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Master Data' : ''">
                        <x-lucide-database class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Master Data</span>
                    </a>

                    <a href="{{ route('admin.holidays.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.holidays.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Holidays' : ''">
                        <x-lucide-calendar-days class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Holidays</span>
                    </a>

                    <a href="{{ route('admin.attendance.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.attendance.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Attendance' : ''">
                        <x-lucide-calendar-check class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Attendance Calendar</span>
                    </a>

                    <a href="{{ route('admin.leave-types.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.leave-types.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Leave Rules' : ''">
                        <x-lucide-settings-2 class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Leave Rules & Policy</span>
                    </a>

                    <a href="{{ route('admin.leave-balances.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.leave-balances.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Leave Balances' : ''">
                        <x-lucide-wallet class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Leave Balances</span>
                    </a>

                    <a href="{{ route('admin.settings.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'System Settings' : ''">
                        <x-lucide-sliders class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">System Settings</span>
                    </a>

                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Audit Logs' : ''">
                        <x-lucide-history class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Audit Trail & Logs</span>
                    </a>
                    @endcan

                    @can('customers.view')
                    <a href="{{ route('admin.customers.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.customers.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Customers' : ''">
                        <x-lucide-users-round class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Customers</span>
                    </a>

                    <a href="{{ route('admin.products.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Products' : ''">
                        <x-lucide-package class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Products</span>
                    </a>

                    <a href="{{ route('admin.product-attributes.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.product-attributes.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Attributes' : ''">
                        <x-lucide-tag class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Attributes</span>
                    </a>
                    @endcan

                    @can('transactions.view')
                    <a href="{{ route('admin.transactions.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.transactions.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Transactions' : ''">
                        <x-lucide-receipt class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Transactions</span>
                    </a>
                    @endcan
                </div>

            </nav>

            {{-- User profile block (bottom-pinned) --}}
            <div class="border-t border-surface-border p-3" x-data="{ userMenuOpen: false }">
                <div class="relative">
                    <button
                        @click="userMenuOpen = !userMenuOpen"
                        class="flex w-full items-center gap-3 rounded-lg p-2 hover:bg-surface-muted transition-colors text-left"
                        :class="{ 'justify-center': sidebarCollapsed }"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                        <span x-show="!sidebarCollapsed" class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-ink-900 truncate">{{ auth()->user()->name ?? 'User' }}</span>
                            <span class="block text-xs text-ink-400 truncate">{{ auth()->user()->email ?? '' }}</span>
                        </span>
                        <x-lucide-chevrons-up-down x-show="!sidebarCollapsed" class="w-4 h-4 text-ink-400 shrink-0" />
                    </button>

                    <div
                        x-show="userMenuOpen"
                        @click.outside="userMenuOpen = false"
                        x-transition
                        class="absolute bottom-full left-0 right-0 mb-1 bg-white border border-surface-border rounded-xl shadow-lg z-50 py-1 overflow-hidden"
                        style="display: none;"
                    >
                        <div class="px-3 py-2 border-b border-surface-border">
                            <p class="text-sm font-semibold text-ink-900 truncate">{{ auth()->user()->name ?? 'User' }}</p>
                            <p class="text-xs text-ink-400 truncate">{{ auth()->user()->email ?? '' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-danger-text hover:bg-danger-bg transition-colors">
                                <x-lucide-log-out class="w-4 h-4" />
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-20 bg-black/30 lg:hidden"
        style="display: none;"
    ></div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{--  MAIN CONTENT                                                 --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <main
        class="flex-1 min-h-screen transition-all duration-200"
        :class="{
            'lg:ml-64': !sidebarCollapsed,
            'lg:ml-16': sidebarCollapsed
        }"
    >
        {{-- Top bar --}}
        <header class="topbar">
            <div class="flex items-center gap-3 px-5 py-4 lg:px-8">

                {{-- Mobile hamburger --}}
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden h-9 w-9 flex items-center justify-center rounded-lg hover:bg-surface-muted transition-colors"
                >
                    <x-lucide-menu class="w-5 h-5 text-ink-600" />
                </button>

                {{-- Breadcrumb + Page title --}}
                <div class="flex-1 min-w-0">
                    <p class="text-breadcrumb">@yield('breadcrumb', 'ADMIN')</p>
                    <h1 class="text-page-title mt-0.5">@yield('page_title', 'Dashboard')</h1>
                </div>

                {{-- Right-side topbar actions --}}
                <div class="flex items-center gap-2 shrink-0">
                    @include('admin.partials.notifications-bell')
                </div>

            </div>
        </header>

        {{-- Page content --}}
        <div class="px-5 py-6 lg:px-8">

            {{-- Flash messages --}}
            @if (session('success') || session('status'))
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
                    <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0 mt-0.5" />
                    <p class="text-sm font-medium text-success-text">{{ session('success') ?? session('status') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-danger-bg px-4 py-3">
                    <x-lucide-alert-circle class="w-5 h-5 text-danger-text shrink-0 mt-0.5" />
                    <p class="text-sm font-medium text-danger-text">{{ session('error') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-danger-bg px-4 py-3">
                    <div class="flex items-start gap-3">
                        <x-lucide-alert-circle class="w-5 h-5 text-danger-text shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-semibold text-danger-text mb-1">Please fix the following errors:</p>
                            <ul class="list-disc list-inside text-sm text-danger-text space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

</div>

@stack('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

{{-- ── Permission Denied Toast (pure JS — Alpine loads too early via Vite) ── --}}
@if (session('permission_denied'))
<div id="perm-toast" style="
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    display: flex; align-items: flex-start; gap: 12px;
    max-width: 360px; width: 100%;
    background: #fff; border: 1px solid #fecaca;
    border-radius: 16px; padding: 14px 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
    animation: toast-in 0.3s ease-out;
    overflow: hidden;
">
    {{-- Icon --}}
    <span style="flex-shrink:0; width:36px; height:36px; background:#fff1f2; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-top:1px;">
        <svg width="20" height="20" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286z"/>
        </svg>
    </span>

    {{-- Text --}}
    <div style="flex:1; min-width:0;">
        <p style="margin:0; font-size:13px; font-weight:600; color:#b91c1c; line-height:1.3;">Access Denied</p>
        <p style="margin:4px 0 0; font-size:13px; color:#dc2626; line-height:1.4;">{{ session('permission_denied') }}</p>
    </div>

    {{-- Close --}}
    <button onclick="document.getElementById('perm-toast').remove()"
        style="flex-shrink:0; background:none; border:none; cursor:pointer; padding:4px; border-radius:6px; line-height:0; margin-top:1px;">
        <svg width="16" height="16" fill="none" stroke="#f87171" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    {{-- Progress bar --}}
    <div id="perm-toast-bar" style="
        position:absolute; bottom:0; left:0; height:3px;
        background:#fca5a5; border-radius:2px;
        width:100%; animation: toast-shrink 4.5s linear forwards;
    "></div>
</div>

<style>
    @@keyframes toast-in {
        from { opacity: 0; transform: translateX(20px) translateY(-8px); }
        to   { opacity: 1; transform: translateX(0) translateY(0); }
    }
    @@keyframes toast-shrink {
        from { width: 100%; }
        to   { width: 0%; }
    }
</style>

<script>
    // Auto-dismiss after 4.5s with fade-out
    setTimeout(function() {
        var t = document.getElementById('perm-toast');
        if (!t) return;
        t.style.transition = 'opacity 0.3s, transform 0.3s';
        t.style.opacity = '0';
        t.style.transform = 'translateX(20px)';
        setTimeout(function() { if (t) t.remove(); }, 300);
    }, 4500);
</script>
@endif



</body>
</html>
