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
    <style>[x-cloak] { display: none !important; }</style>
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
                    @canany(['customers.view', 'customers.manage', 'parties.view', 'parties.view-team', 'parties.view-all', 'parties.view-own'])
                    <a href="{{ route('admin.parties.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.parties.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Parties' : ''">
                        <x-lucide-users class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Parties</span>
                    </a>
                    @endcanany

                    @canany(['day-start.view-team', 'day-start.view-all', 'day-start.view-own', 'day-start.view', 'day-start.manage', 'day-start.approve'])
                    <a href="{{ route('admin.day-starts.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.day-starts.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Day Start' : ''">
                        <x-lucide-calendar-check class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Day Start</span>
                    </a>
                    @endcanany

                    @canany(['orders.view', 'orders.view-team', 'orders.view-all', 'orders.view-own', 'orders.manage'])
                    <a href="{{ route('admin.orders.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Orders' : ''">
                        <x-lucide-shopping-cart class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Orders</span>
                    </a>
                    @endcanany

                    @canany(['payments.view', 'payments.view-team', 'payments.view-all', 'payments.view-own', 'payments.manage'])
                    <a href="{{ route('admin.payments.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Payments' : ''">
                        <x-lucide-banknote class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Payments</span>
                    </a>
                    @endcanany

                    @canany(['visits.view', 'visits.view-team', 'visits.view-all', 'visits.view-own', 'visits.manage'])
                    <a href="{{ route('admin.visits.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.visits.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Visits' : ''">
                        <x-lucide-map-pin class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Visits</span>
                    </a>
                    @endcanany

                    @canany(['reports.view', 'reports.view-team', 'reports.view-all', 'reports.view-own', 'reports.manage'])
                    <a href="{{ route('admin.reports.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Reports' : ''">
                        <x-lucide-bar-chart-2 class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Reports</span>
                    </a>
                    @endcanany

                    @canany(['expenses.view', 'expenses.view-team', 'expenses.view-all', 'expenses.view-own', 'expenses.approve', 'expenses.final_approve', 'expenses.manage'])
                    <a href="{{ route('admin.expenses.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.expenses.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Expenses' : ''">
                        <x-lucide-receipt class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Expense Bill</span>
                    </a>
                    @endcanany

                    @canany(['fuel-rates.view', 'fuel-rates.manage'])
                    <a href="{{ route('admin.fuel-rates.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.fuel-rates.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Fuel Rates' : ''">
                        <x-lucide-fuel class="w-5 h-5 shrink-0 text-amber-500" />
                        <span x-show="!sidebarCollapsed" class="truncate">Fuel Rates (Per KM)</span>
                    </a>
                    @endcanany

                    @canany(['leaves.view', 'leaves.view-team', 'leaves.view-all', 'leaves.view-own', 'leaves.approve', 'leaves.manage'])
                    <a href="{{ route('admin.leave.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.leave.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Leave' : ''">
                        <x-lucide-calendar class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Leave</span>
                    </a>
                    @endcanany

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

                    @canany(['dealers.view', 'dealers.manage'])
                    <a href="{{ route('admin.dealers.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.dealers.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Dealers' : ''">
                        <x-lucide-store class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Dealer List</span>
                    </a>
                    @endcanany

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
                    @canany(['employees.view', 'employees.view-team', 'employees.view-all', 'employees.view-own', 'employees.manage'])
                    <a href="{{ route('admin.employees.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.employees.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Employees' : ''">
                        <x-lucide-id-card class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Employees</span>
                    </a>
                    @endcanany

                    @if(auth()->user()->hasRole('Super Admin') || auth()->user()->hasAnyPermission(['roles.view', 'roles.manage']))
                    <a href="{{ route('admin.roles.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Roles & Permissions' : ''">
                        <x-lucide-shield-check class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Roles & Permissions</span>
                    </a>
                    @endif

                    <a href="{{ route('admin.notifications.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.notifications.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'All Notifications' : ''">
                        <x-lucide-bell class="w-5 h-5 shrink-0 text-blue-500" />
                        <span x-show="!sidebarCollapsed" class="truncate">All Notifications</span>
                    </a>

                    @canany(['settings.manage', 'master-data.manage'])
                    <a href="{{ route('admin.push-notifications.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.push-notifications.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Push Notifications' : ''">
                        <x-lucide-bell-ring class="w-5 h-5 shrink-0 text-indigo-500" />
                        <span x-show="!sidebarCollapsed" class="truncate">Push Notifications</span>
                    </a>
                    @endcanany

                    @canany(['attendance.view', 'attendance.view-team', 'attendance.view-all', 'attendance.view-own', 'attendance.manage'])
                    <a href="{{ route('admin.attendance.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.attendance.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Attendance' : ''">
                        <x-lucide-calendar-check class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Attendance Calendar</span>
                    </a>
                    @endcanany

                    @can('leaves.manage')
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
                    @endcan

                    @can('master-data.manage')
                    <a href="{{ route('admin.master-data.index', 'tour-purposes') }}"
                       class="sidebar-link {{ request()->routeIs('admin.master-data.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Master Data' : ''">
                        <x-lucide-database class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Master Data</span>
                    </a>
                    @endcan

                    @canany(['locations.view', 'locations.manage'])
                    <a href="{{ route('admin.locations.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.locations.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Districts & Areas' : ''">
                        <x-lucide-map-pin class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Districts & Areas</span>
                    </a>
                    @endcanany

                    @can('master-data.manage')
                    <a href="{{ route('admin.holidays.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.holidays.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Holidays' : ''">
                        <x-lucide-calendar-days class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Holidays</span>
                    </a>
                    @endcan

                    @can('settings.manage')
                    <a href="{{ route('admin.settings.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'System Settings' : ''">
                        <x-lucide-sliders class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">System Settings</span>
                    </a>
                    @endcan

                    @can('audit-logs.view')
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Audit Logs' : ''">
                        <x-lucide-history class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Audit Trail & Logs</span>
                    </a>
                    @endcan

                    @canany(['customers.view', 'customers.manage', 'parties.view', 'parties.view-team', 'parties.view-all', 'parties.view-own'])
                    <a href="{{ route('admin.customers.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.customers.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Customers' : ''">
                        <x-lucide-users-round class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Customers</span>
                    </a>
                    @endcanany

                    @can('products.view')
                    <a href="{{ route('admin.products.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Products' : ''">
                        <x-lucide-package class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Products</span>
                    </a>
                    @endcan

                    @canany(['products.manage', 'master-data.manage'])
                    <a href="{{ route('admin.product-attributes.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.product-attributes.*') ? 'sidebar-link-active' : '' }}"
                       :title="sidebarCollapsed ? 'Attributes' : ''">
                        <x-lucide-tag class="w-5 h-5 shrink-0" />
                        <span x-show="!sidebarCollapsed" class="truncate">Attributes</span>
                    </a>
                    @endcanany

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

{{-- ── Global Toast Notification Container (Fixed Top-Right) ── --}}
<div id="global-toast-container" style="
    position: fixed; top: 20px; right: 20px; z-index: 99999;
    display: flex; flex-direction: column; gap: 10px;
    max-width: 380px; width: calc(100vw - 40px);
    pointer-events: none;
"></div>

<style>
    @keyframes toast-slide-in {
        from { opacity: 0; transform: translateX(40px) scale(0.95); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toast-progress-shrink {
        from { width: 100%; }
        to   { width: 0%; }
    }
    .admin-toast-card {
        pointer-events: auto;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: #ffffff;
        border-radius: 14px;
        padding: 12px 14px;
        box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.15), 0 0 0 1px rgba(15, 23, 42, 0.05);
        animation: toast-slide-in 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        position: relative;
        overflow: hidden;
        transition: opacity 0.25s ease, transform 0.25s ease;
    }
    .admin-toast-card.toast-success { border-left: 4px solid #10b981; }
    .admin-toast-card.toast-error   { border-left: 4px solid #f43f5e; }
    .admin-toast-card.toast-warning { border-left: 4px solid #f59e0b; }
    .admin-toast-card.toast-info    { border-left: 4px solid #6366f1; }
</style>

<script>
    (function() {
        const icons = {
            success: `<svg width="20" height="20" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
            error: `<svg width="20" height="20" fill="none" stroke="#e11d48" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>`,
            warning: `<svg width="20" height="20" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
            info: `<svg width="20" height="20" fill="none" stroke="#4f46e5" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
        };

        const iconBgs = {
            success: '#ecfdf5',
            error: '#fff1f2',
            warning: '#fffbeb',
            info: '#eef2ff'
        };

        const titles = {
            success: 'Success',
            error: 'Action Failed',
            warning: 'Warning',
            info: 'Notice'
        };

        window.toast = function(message, type = 'success', customTitle = null, durationMs = 4500) {
            const container = document.getElementById('global-toast-container');
            if (!container) return;

            const safeType = ['success', 'error', 'danger', 'warning', 'info'].includes(type) ? (type === 'danger' ? 'error' : type) : 'info';
            const title = customTitle || titles[safeType];

            const card = document.createElement('div');
            card.className = `admin-toast-card toast-${safeType}`;

            card.innerHTML = `
                <span style="flex-shrink:0; width:34px; height:34px; background:${iconBgs[safeType]}; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-top:1px;">
                    ${icons[safeType]}
                </span>
                <div style="flex:1; min-width:0;">
                    <p style="margin:0; font-size:12.5px; font-weight:700; color:#0f172a; line-height:1.3;">${title}</p>
                    <p style="margin:2px 0 0; font-size:12px; color:#475569; line-height:1.4; word-break:break-word;">${message}</p>
                </div>
                <button type="button" class="toast-close-btn" style="flex-shrink:0; background:none; border:none; cursor:pointer; padding:3px; border-radius:6px; color:#94a3b8; line-height:0; margin-top:1px;" title="Dismiss">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="toast-progress" style="position:absolute; bottom:0; left:0; height:3px; background:${safeType === 'success' ? '#6ee7b7' : (safeType === 'error' ? '#fda4af' : (safeType === 'warning' ? '#fde68a' : '#c7d2fe'))}; width:100%; animation: toast-progress-shrink ${durationMs}ms linear forwards;"></div>
            `;

            container.appendChild(card);

            const removeToast = () => {
                card.style.opacity = '0';
                card.style.transform = 'translateX(40px) scale(0.95)';
                setTimeout(() => { card.remove(); }, 250);
            };

            const closeBtn = card.querySelector('.toast-close-btn');
            if (closeBtn) closeBtn.addEventListener('click', removeToast);

            const timeoutId = setTimeout(removeToast, durationMs);
            card.addEventListener('mouseenter', () => {
                const prog = card.querySelector('.toast-progress');
                if (prog) prog.style.animationPlayState = 'paused';
                clearTimeout(timeoutId);
            });
        };

        window.showToast = window.toast;

        // Listen for standard custom events
        window.addEventListener('notify', function(e) {
            if (e.detail) {
                window.toast(e.detail.message || e.detail.text, e.detail.type || 'success', e.detail.title);
            }
        });
        window.addEventListener('toast', function(e) {
            if (e.detail) {
                window.toast(e.detail.message || e.detail.text, e.detail.type || 'success', e.detail.title);
            }
        });

        // Trigger Laravel session flash messages on page load
        @if(session('status') || session('success'))
            window.toast(@json(session('status') ?? session('success')), 'success', 'Success');
        @endif
        @if(session('error'))
            window.toast(@json(session('error')), 'error', 'Error');
        @endif
        @if(session('warning'))
            window.toast(@json(session('warning')), 'warning', 'Warning');
        @endif
        @if(session('permission_denied'))
            window.toast(@json(session('permission_denied')), 'error', 'Access Denied');
        @endif
    })();
</script>
</body>
</html>
