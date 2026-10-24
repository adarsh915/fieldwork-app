@extends('admin.layouts.app')

@section('title', 'Fieldworker Parties Report')
@section('breadcrumb', 'Admin / Reports / Fieldworker Parties')
@section('page_title', 'Fieldworker Parties Report')

@section('content')
    <section class="panel">
        {{-- Header --}}
        <div class="panel-header">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Fieldworker Parties</h2>
                    <p class="mt-1 text-sm text-slate-500">List of all fieldworkers and their assigned parties (customers).</p>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.reports.fieldworker-parties') }}" class="mt-4 flex flex-wrap gap-3">
                <select name="employee_id" class="form-input w-auto">
                    <option value="">All Fieldworkers</option>
                    @foreach ($employeesList as $emp)
                        <option value="{{ $emp->id }}" @selected($employeeId == $emp->id)>
                            {{ $emp->name }} ({{ $emp->role_name }})
                        </option>
                    @endforeach
                </select>
                <button class="btn-primary">Filter</button>
                @if($employeeId)
                    <a href="{{ route('admin.reports.fieldworker-parties') }}" class="btn-secondary">Clear</a>
                @endif
            </form>
        </div>

        {{-- Quick Search & Filter Toolbar --}}
        <div class="p-4 bg-slate-50/70 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <input type="text" id="fw-parties-search" placeholder="Search fieldworker, party, contact, address..." 
                       class="form-input text-xs py-2 pl-9 pr-3 w-full rounded-lg border-slate-200 bg-white shadow-2xs focus:border-brand-500" />
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-600">
                <span class="font-medium">Show</span>
                <select id="fw-parties-page-len" class="form-input text-xs py-1.5 px-2.5 w-auto rounded-lg border-slate-200 bg-white">
                    <option value="15">15</option>
                    <option value="30" selected>30</option>
                    <option value="50">50</option>
                    <option value="-1">All</option>
                </select>
                <span>parties</span>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-wrap">
            <table id="fieldworker-parties-table" class="data-table">
                <thead>
                    <tr>
                        <th>Fieldworker</th>
                        <th>Party Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $hasRecords = false;
                    @endphp
                    
                    @foreach ($fieldworkers as $fieldworker)
                        @foreach ($fieldworker->parties as $party)
                            @php $hasRecords = true; @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="align-top">
                                    <div class="font-bold text-slate-900">{{ $fieldworker->name }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $fieldworker->role_name }}</div>
                                </td>
                                <td class="align-top">
                                    <div class="font-medium text-slate-900">{{ $party->name }}</div>
                                </td>
                                <td class="align-top text-slate-600">{{ $party->mobile ?? '—' }}</td>
                                <td class="align-top text-slate-600">{{ $party->email ?? '—' }}</td>
                                <td class="align-top text-slate-500 text-sm max-w-[250px] truncate" title="{{ $party->address }}">
                                    {{ $party->address ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    @if (!$hasRecords)
                        <tr>
                            <td class="py-12 text-center text-slate-500" colspan="5">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-3xl">📭</span>
                                    <p class="font-medium">No parties found for the selected fieldworker(s).</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let fwPartiesTable = null;

    function getDT() {
        return window.DataTable || (typeof DataTable !== 'undefined' ? DataTable : null);
    }

    function initFwPartiesDT() {
        const tableEl = document.getElementById('fieldworker-parties-table');
        if (!tableEl) return;
        const DT = getDT();
        if (!DT) return;

        const hasEmpty = tableEl.querySelector('tbody tr td[colspan]') !== null;
        if (hasEmpty) return;

        const lenSelect = document.getElementById('fw-parties-page-len');
        const initialLen = lenSelect ? parseInt(lenSelect.value) : 30;

        fwPartiesTable = new DT('#fieldworker-parties-table', {
            pageLength: initialLen > 0 ? initialLen : 30,
            lengthMenu: [[15, 30, 50, -1], [15, 30, 50, 'All']],
            order: [[0, 'asc'], [1, 'asc']],
            dom: 'rt<"flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-3.5 border-t border-slate-100"<"text-caption text-ink-500 font-medium"i><""p>>',
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ parties",
                infoEmpty: "Showing 0 parties",
                emptyTable: "No parties found for the selected fieldworker(s)."
            }
        });

        document.getElementById('fw-parties-search')?.addEventListener('input', function () {
            if (fwPartiesTable) {
                fwPartiesTable.search(this.value).draw();
            }
        });

        document.getElementById('fw-parties-page-len')?.addEventListener('change', function () {
            if (fwPartiesTable) {
                fwPartiesTable.page.len(parseInt(this.value)).draw();
            }
        });
    }

    if (getDT()) {
        initFwPartiesDT();
    } else {
        let tries = 0;
        const timer = setInterval(() => {
            tries++;
            if (getDT()) {
                clearInterval(timer);
                initFwPartiesDT();
            } else if (tries > 50) {
                clearInterval(timer);
            }
        }, 50);
    }
});
</script>
@endpush

