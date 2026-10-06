@extends('admin.layouts.app')

@section('title', 'Party Profile — ' . $party->name)
@section('breadcrumb', 'Admin / Parties / Profile')
@section('page_title', $party->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/party-profile.css') }}">
@endpush

@section('content')
    {{-- ═══ Top Bar ═══ --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-lg bg-[var(--ampl-primary)] text-white text-lg font-bold shadow">
                    {{ strtoupper(substr($party->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-950">{{ $party->name }}</h2>
                    <p class="text-xs text-slate-500">Party ID: #{{ $party->id }} &middot; Created
                        {{ $party->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.parties.index') }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </a>
            <a href="{{ route('admin.parties.export-single-excel', $party->id) }}" class="btn-secondary text-green-600 border-green-200 hover:bg-green-50">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Export Excel
            </a>
            <a href="{{ route('admin.parties.export-single-pdf', $party->id) }}" class="btn-secondary text-red-600 border-red-200 hover:bg-red-50">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export PDF
            </a>
        </div>
    </div>

    {{-- ═══ KPI Cards ═══ --}}
    <div class="kpi-grid">
        @php
            $kpis = [
                ['label' => 'Total Visits',   'value' => $party->visits()->count(),                              'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'bg' => 'bg-blue-50',   'text' => 'text-blue-600'],
                ['label' => 'Total Orders',   'value' => $party->orders()->count(),                              'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
                ['label' => 'Revenue',        'value' => '₹' . number_format($party->orders()->sum('total_amount'), 2), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-amber-50',  'text' => 'text-amber-600'],
                ['label' => 'Transactions',   'value' => $party->transactions()->count(),                         'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="metric-card flex items-center gap-4">
                <div class="metric-icon {{ $kpi['bg'] }} {{ $kpi['text'] }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icon'] }}" />
                    </svg>
                </div>
                <div>
                    <div class="metric-label">{{ $kpi['label'] }}</div>
                    <div class="mt-1 text-xl font-bold text-slate-950">{{ $kpi['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ═══ Info Cards Row ═══ --}}
    <div class="info-row">
        {{-- Contact Details --}}
        <div class="panel p-5">
            <h3 class="text-sm font-bold text-slate-950 mb-4 pb-3 border-b border-slate-100">Contact Information</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 font-medium">Mobile</dt>
                    <dd class="text-slate-950 font-semibold">{{ $party->mobile ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 font-medium">Email</dt>
                    <dd class="text-slate-950 font-semibold">{{ $party->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium mb-1">Address</dt>
                    <dd class="text-slate-700 leading-relaxed">{{ $party->address ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Assigned Fieldworker --}}
        <div class="panel p-5">
            <h3 class="text-sm font-bold text-slate-950 mb-4 pb-3 border-b border-slate-100">Assigned Fieldworker</h3>
            @if($party->activeAssignment && $party->activeAssignment->employee)
                <div class="flex items-center gap-3 mt-1">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-lg bg-[var(--ampl-primary)] text-white text-sm font-bold">
                        {{ strtoupper(substr($party->activeAssignment->employee->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-slate-950">{{ $party->activeAssignment->employee->name }} ({{ $party->activeAssignment->employee->role_name }})</div>
                        <div class="text-xs text-slate-500">{{ $party->activeAssignment->employee->email }}</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-400 italic mt-2">No fieldworker assigned</p>
            @endif
        </div>

        {{-- Property & Location --}}
        <div class="panel p-5">
            <h3 class="text-sm font-bold text-slate-950 mb-4 pb-3 border-b border-slate-100">Property & Location</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 font-medium">Land Area</dt>
                    <dd class="text-slate-950 font-semibold">{{ $party->land_area ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium mb-1">Geo Location</dt>
                    <dd>
                        @if($party->latitude && $party->longitude)
                            <a href="https://maps.google.com/?q={{ $party->latitude }},{{ $party->longitude }}" target="_blank"
                                class="inline-flex items-center gap-1 text-[var(--ampl-primary)] hover:underline text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ number_format($party->latitude, 4) }}, {{ number_format($party->longitude, 4) }}
                            </a>
                        @else
                            <span class="text-slate-400">Not provided</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- ═══ Tabbed Data Section ═══ --}}
    <div class="panel" id="dataPanel">
        <div class="panel-header" style="padding-bottom:0;">
            <div class="profile-tabs" id="profileTabs">
                <button class="profile-tab active" data-tab="visits">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Visits <span class="tab-count">{{ $party->visits()->count() }}</span>
                </button>
                <button class="profile-tab" data-tab="orders">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Orders <span class="tab-count">{{ $party->orders()->count() }}</span>
                </button>
                <button class="profile-tab" data-tab="transactions">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Transactions
                <span class="tab-count">{{ $party->transactions()->count() }}</span>
            </button>
            <button class="profile-tab" data-tab="products">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                Products
                <span class="tab-count">{{ $purchasedProducts->count() }}</span>
            </button>
            </div>
        </div>

        {{-- ─── Visit History Tab ─── --}}
        <div class="tab-content active" id="tab-visits">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                <span class="text-xs text-slate-500">Loaded on demand</span>
                <div id="visits-pagination" class="tab-pagination"></div>
            </div>
            <div class="table-wrap" id="visits-table-wrap">
                <table class="data-table" id="visits-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Check-In Date</th><th>Fieldworker</th>
                            <th>Check-In Location</th><th>Check-Out</th><th>Remarks</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="visits-tbody">
                        <tr><td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                            <span class="animate-pulse">Loading visits…</span>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ─── Orders Tab ─── --}}
        <div class="tab-content" id="tab-orders">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                <span class="text-xs text-slate-500">Loaded on demand</span>
                <div id="orders-pagination" class="tab-pagination"></div>
            </div>
            <div class="table-wrap" id="orders-table-wrap">
                <table class="data-table" id="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th><th>Date</th><th>Fieldworker</th>
                            <th>Payment</th><th>Remarks</th><th>Amount</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="orders-tbody">
                        <tr><td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                            <span class="animate-pulse">Loading orders…</span>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ─── Transactions Tab ─── --}}
        <div class="tab-content" id="tab-transactions">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                <span class="text-xs text-slate-500">Loaded on demand</span>
                <div id="transactions-pagination" class="tab-pagination"></div>
            </div>
            <div class="table-wrap" id="transactions-table-wrap">
                <table class="data-table" id="transactions-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Date</th><th>Amount</th>
                            <th>Type</th><th>Notes</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="transactions-tbody">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 text-sm">
                            <span class="animate-pulse">Loading transactions…</span>
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Products Tab ── -->
        <div class="panel tab-content p-0" id="tab-products">
            <div style="padding:16px 20px; display:flex; justify-content:flex-end; border-bottom:1px solid #e2e8f0;">
                <input type="text" class="tab-search" data-target="products-table" placeholder="Search products...">
            </div>
            <div class="table-responsive">
                <table id="products-table" class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th><th>Product Name</th><th>Code</th>
                            <th>Times Bought</th><th>Total Qty</th><th>Total Spent</th><th>Last Purchased</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchasedProducts as $item)
                            <tr>
                                <td>
                                    @if($item->product->image)
                                        <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->name }}" class="w-10 h-10 object-cover rounded shadow-sm border border-slate-200">
                                    @else
                                        <div class="w-10 h-10 bg-slate-100 flex items-center justify-center text-slate-400 font-bold rounded shadow-sm border border-slate-200 text-xs">
                                            {{ substr($item->product->name, 0, 2) }}
                                        </div>
                                    @endif
                                </td>
                                <td style="font-weight:600; color:#334155;">{{ $item->product->name }}</td>
                                <td><span class="badge badge-outline-primary">{{ $item->product->code ?? 'N/A' }}</span></td>
                                <td>{{ $item->times_bought }}</td>
                                <td>{{ $item->total_quantity }}</td>
                                <td style="font-weight:600;">₹{{ number_format($item->total_spent, 2) }}</td>
                                <td class="text-xs text-slate-500 whitespace-nowrap">{{ $item->last_purchased ? $item->last_purchased->format('d M Y, h:i A') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align:center; padding:30px;">No products purchased yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/party-profile.js') }}"></script>
    <script>
    (function () {
        const tabDataUrl = '{{ route('admin.parties.tab-data', $party->id) }}';
        const loaded = {};
        const pages  = { visits: 1, orders: 1, transactions: 1 };

        function renderVisits(data) {
            const tbody = document.getElementById('visits-tbody');
            if (!data.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="py-10 text-center text-slate-400">No visits found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map((v, i) => `
                <tr>
                    <td class="text-slate-400 text-xs">${data.from + i}</td>
                    <td class="whitespace-nowrap font-medium">${v.check_in_time ? new Date(v.check_in_time).toLocaleString('en-IN') : '—'}</td>
                    <td>${v.user ? v.user.name : 'Unknown'}</td>
                    <td class="text-xs">${v.check_in_lat ? `<a href="https://maps.google.com/?q=${v.check_in_lat},${v.check_in_lng}" target="_blank" class="text-blue-600 hover:underline">${Number(v.check_in_lat).toFixed(4)},${Number(v.check_in_lng).toFixed(4)}</a>` : '—'}</td>
                    <td class="whitespace-nowrap text-xs">${v.check_out_time ? new Date(v.check_out_time).toLocaleString('en-IN') : '—'}</td>
                    <td class="max-w-[200px] truncate text-slate-500">${v.remarks ?? '—'}</td>
                    <td><span class="badge badge-${v.status === 'completed' ? 'success' : 'muted'}">${v.status ?? 'pending'}</span></td>
                </tr>`).join('');
            renderPager('visits', data);
        }

        function renderOrders(data) {
            const tbody = document.getElementById('orders-tbody');
            if (!data.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="py-10 text-center text-slate-400">No orders found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(o => `
                <tr>
                    <td class="font-bold text-blue-600">#${o.id}</td>
                    <td class="whitespace-nowrap">${o.order_date ? new Date(o.order_date).toLocaleDateString('en-IN') : '—'}</td>
                    <td>${o.user ? o.user.name ?? '—' : '—'}</td>
                    <td class="text-xs">${o.payment_mode ? o.payment_mode.charAt(0).toUpperCase() + o.payment_mode.slice(1) : '—'}</td>
                    <td class="max-w-[200px] truncate text-slate-500">${o.remarks ?? '—'}</td>
                    <td class="font-bold whitespace-nowrap">₹${Number(o.total_amount ?? 0).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                    <td><span class="badge badge-${o.status === 'delivered' ? 'success' : o.status === 'pending' ? 'warning' : 'info'}">${o.status ?? 'pending'}</span></td>
                </tr>`).join('');
            renderPager('orders', data);
        }

        function renderTransactions(data) {
            const tbody = document.getElementById('transactions-tbody');
            if (!data.data.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="py-10 text-center text-slate-400">No transactions found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map((t, i) => `
                <tr>
                    <td class="text-slate-400 text-xs">${data.from + i}</td>
                    <td class="whitespace-nowrap font-medium">${t.created_at ? new Date(t.created_at).toLocaleDateString('en-IN') : '—'}</td>
                    <td class="font-bold whitespace-nowrap">₹${Number(t.amount ?? 0).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                    <td><span class="badge badge-${t.type === 'payment' ? 'success' : t.type === 'refund' ? 'danger' : 'info'}">${t.type ?? '—'}</span></td>
                    <td class="max-w-[200px] truncate text-slate-500">${t.notes ?? '—'}</td>
                    <td><span class="badge badge-${t.status === 'completed' ? 'success' : 'warning'}">${t.status ?? '—'}</span></td>
                </tr>`).join('');
            renderPager('transactions', data);
        }

        function renderPager(tab, data) {
            const el = document.getElementById(`${tab}-pagination`);
            if (!el) return;
            if (data.last_page <= 1) { el.innerHTML = ''; return; }
            el.innerHTML = `
                <button onclick="loadTab('${tab}',${data.current_page - 1})" ${data.current_page <= 1 ? 'disabled' : ''} class="btn-secondary text-xs py-1 px-2">‹</button>
                <span class="text-xs text-slate-500 mx-2">${data.current_page} / ${data.last_page}</span>
                <button onclick="loadTab('${tab}',${data.current_page + 1})" ${data.current_page >= data.last_page ? 'disabled' : ''} class="btn-secondary text-xs py-1 px-2">›</button>
            `;
        }

        window.loadTab = function(tab, page = 1) {
            pages[tab] = page;
            fetch(`${tabDataUrl}?tab=${tab}&page=${page}`)
                .then(r => r.json())
                .then(data => {
                    loaded[tab] = true;
                    if (tab === 'visits')       renderVisits(data);
                    else if (tab === 'orders')  renderOrders(data);
                    else                        renderTransactions(data);
                });
        };

        // Wire tab buttons to AJAX loader
        document.querySelectorAll('#profileTabs .profile-tab').forEach(btn => {
            btn.addEventListener('click', function () {
                const tab = this.dataset.tab;
                if (['visits', 'orders', 'transactions'].includes(tab) && !loaded[tab]) {
                    loadTab(tab, 1);
                }
            });
        });

        // Auto-load first tab
        loadTab('visits', 1);
    })();
    </script>
@endpush
