@extends('admin.layouts.app')

@section('title', 'System Audit Trail & Action Logs')
@section('breadcrumb', 'ADMIN / AUDIT LOGS')
@section('page_title', 'System Audit Trail & Activity Logs')

@section('content')
<div class="space-y-6">

    {{-- ── 1. KPI Stats Row ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Logs --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-blue-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Total Audit Records</p>
                <h3 class="text-2xl font-bold text-ink-900 mt-1">{{ number_format($stats['total']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <x-lucide-history class="w-6 h-6" />
            </div>
        </div>

        {{-- Today's Actions --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-emerald-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Actions Today</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['today']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <x-lucide-activity class="w-6 h-6" />
            </div>
        </div>

        {{-- This Week --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-brand-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Actions This Week</p>
                <h3 class="text-2xl font-bold text-brand-600 mt-1">{{ number_format($stats['this_week']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                <x-lucide-calendar class="w-6 h-6" />
            </div>
        </div>

        {{-- Active Admins --}}
        <div class="panel p-5 flex items-center justify-between border-l-4 border-l-purple-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Active Staff / Admins</p>
                <h3 class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['admin_count']) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <x-lucide-shield-check class="w-6 h-6" />
            </div>
        </div>
    </div>

    {{-- ── 2. Filter & Search Panel ── --}}
    <div class="panel p-5">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" id="audit-filter-form" class="space-y-4">
            {{-- Top row: Quick Search and Reset Filter --}}
            <div class="flex flex-wrap gap-3 items-end">
                <div class="relative flex-1 min-w-[260px]">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                    <input type="text" name="search" id="audit-search-input" value="{{ request('search') }}"
                           placeholder="Search action reason, admin name, model ID, IP..."
                           class="form-input text-xs pl-9 pr-8 w-full"
                           onkeydown="if(event.key === 'Enter') { event.preventDefault(); this.form.submit(); }" />
                    @if(request('search'))
                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                           class="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-400 hover:text-ink-600"
                           title="Clear search">
                            <x-lucide-x class="w-4 h-4" />
                        </a>
                    @endif
                </div>

                {{-- Reset Filters Button --}}
                @if(request()->anyFilled(['search', 'event', 'model', 'causer_id', 'date_from', 'date_to']))
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="btn-secondary text-xs py-2 px-3.5 font-semibold inline-flex items-center gap-1.5 shrink-0 hover:bg-surface-muted transition"
                       title="Reset all filters">
                        <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-500" />
                        <span>Reset Filters</span>
                    </a>
                @else
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="btn-secondary text-xs py-2 px-3.5 font-semibold inline-flex items-center gap-1.5 shrink-0 opacity-50 cursor-default"
                       title="No filters active">
                        <x-lucide-rotate-ccw class="w-3.5 h-3.5 text-ink-400" />
                        <span>Reset Filters</span>
                    </a>
                @endif
            </div>

            {{-- Filter Select Dropdowns Grid (Live Auto-Submit on change, no filter button needed) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 pt-3 border-t border-surface-border">
                <x-filter-select
                    name="event"
                    label="Action Type"
                    placeholder="All Actions"
                    :options="[
                        'created' => 'Created (Add)',
                        'updated' => 'Updated (Edit)',
                        'deleted' => 'Deleted (Remove)',
                        'approved' => 'Approved / Verified',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]"
                    :selected="request('event', '')"
                    change="this.$refs.hiddenInput.form.submit()"
                />

                <x-filter-select
                    name="model"
                    label="Module"
                    placeholder="All Modules"
                    :options="$knownModels"
                    :selected="request('model', '')"
                    change="this.$refs.hiddenInput.form.submit()"
                />

                <x-filter-select
                    name="causer_id"
                    label="Admin / Staff"
                    placeholder="All Staff / Admins"
                    :options="$causers->mapWithKeys(fn($a) => [$a->id => $a->name])"
                    :selected="request('causer_id', '')"
                    change="this.$refs.hiddenInput.form.submit()"
                />

                {{-- From Date --}}
                <div>
                    <label class="form-label text-[11px] font-semibold uppercase tracking-wider text-ink-600 mb-1 block">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-xs w-full" onchange="this.form.submit()" />
                </div>

                {{-- To Date --}}
                <div>
                    <label class="form-label text-[11px] font-semibold uppercase tracking-wider text-ink-600 mb-1 block">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-xs w-full" onchange="this.form.submit()" />
                </div>
            </div>
        </form>
    </div>

    {{-- ── 3. Audit Logs Table (AJAX Stream) ── --}}
    <div class="panel overflow-hidden relative transition-opacity duration-150" id="audit-logs-stream-wrapper">
        @include('admin.audit-logs.partials.table-stream')
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const streamWrapper = document.getElementById('audit-logs-stream-wrapper');
    const filterForm = document.getElementById('audit-filter-form');

    // ── Live AJAX Stream Loader (Zero page reload) ──
    window.loadAuditLogsStream = async function (url, pushState = true) {
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

                // Smooth scroll back to top of table
                const rect = streamWrapper.getBoundingClientRect();
                if (rect.top < 0) {
                    streamWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        } catch (err) {
            console.error('Failed to load audit logs stream:', err);
            if (window.toast) window.toast('Failed to load audit logs.', 'error');
        } finally {
            streamWrapper.style.opacity = '1';
            streamWrapper.style.pointerEvents = 'auto';
        }
    };

    // ── Pagination Clicks (Zero Reload) ──
    document.addEventListener('click', function (e) {
        const pageLink = e.target.closest('#audit-logs-pagination a');
        if (pageLink && pageLink.href) {
            e.preventDefault();
            window.loadAuditLogsStream(pageLink.href);
        }
    });

    // ── Filter Form Intercept (Zero Reload) ──
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            params.set('page', '1');
            const url = filterForm.action + '?' + params.toString();
            window.loadAuditLogsStream(url);
        });

        // Auto-submit triggers on change without page reload
        filterForm.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('change', function () {
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                params.set('page', '1');
                const url = filterForm.action + '?' + params.toString();
                window.loadAuditLogsStream(url);
            });
        });
    }

    // ── Reset Filters ──
    window.resetAuditLogFilters = function () {
        if (filterForm) {
            filterForm.reset();
        }
        window.loadAuditLogsStream('{{ route('admin.audit-logs.index') }}');
    };

    // ── Browser Back / Forward Button Support ──
    window.addEventListener('popstate', function () {
        window.loadAuditLogsStream(window.location.href, false);
    });
});
</script>
@endpush
</div>
@endsection
