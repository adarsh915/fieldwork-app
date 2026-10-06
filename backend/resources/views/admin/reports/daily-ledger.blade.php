@extends('admin.layouts.app')

@section('title', 'Daily Field Operations Ledger')
@section('breadcrumb', 'Admin / Reports / Daily Ledger')
@section('page_title', 'Daily Field Operations Ledger')

@section('content')
<div class="space-y-6">

    {{-- ── Top Navigation & Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-ink-900">Daily Field Operations Ledger</h1>
            <p class="text-sm text-ink-500 mt-0.5">
                Day-by-day audit trail of field employee shifts, odometer travel, visits, and expenses.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.reports.index', request()->query()) }}" 
               class="btn-secondary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-sm">
                <x-lucide-arrow-left class="w-4 h-4" /> Back to Matrix
            </a>
        </div>
    </div>

    {{-- ── Date Presets & Filter Panel ── --}}
    <div class="panel p-5 bg-white border border-slate-200 shadow-sm rounded-xl">
        <form method="GET" action="{{ route('admin.reports.daily-ledger') }}">
            
            <div class="flex flex-wrap items-center gap-1.5 pb-4 mb-4 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-2">Range:</span>
                @php
                    $presets = [
                        'today'      => 'Today',
                        'yesterday'  => 'Yesterday',
                        'this_week'  => 'This Week',
                        'last_week'  => 'Last Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                    ];
                @endphp
                @foreach($presets as $key => $name)
                    <button type="submit" name="preset" value="{{ $key }}"
                            class="px-3 py-1 text-xs font-semibold rounded-full border transition-all 
                            {{ $dateRange['preset'] === $key ? 'bg-brand-50 border-brand-300 text-brand-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        {{ $name }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-3">
                    <label class="form-label text-xs">Filter by Employee</label>
                    <select name="user_id" class="form-input text-xs py-1.5" onchange="this.form.submit()">
                        <option value="">All Employees ({{ $employeesList->count() }})</option>
                        @foreach($employeesList as $emp)
                            <option value="{{ $emp->id }}" {{ $selectedUser == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->role_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <input type="hidden" name="preset" value="{{ $dateRange['preset'] }}" />
                    <button type="submit" class="btn-primary text-xs py-2 px-4 flex-1">Filter</button>
                    <a href="{{ route('admin.reports.daily-ledger') }}" class="btn-secondary text-xs py-2 px-3">Reset</a>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>Active Range: <strong class="text-slate-900 font-semibold">{{ $dateRange['label'] }}</strong></span>
                <span>Total records: <strong>{{ $records->total() }}</strong></span>
            </div>
        </form>
    </div>

    {{-- ── Daily Ledger Table ── --}}
    <div class="panel overflow-hidden border border-slate-200 shadow-sm rounded-xl bg-white">
        <div class="table-wrap overflow-x-auto">
            <table class="data-table w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Employee</th>
                        <th class="py-3 px-3 text-center">Opening KM</th>
                        <th class="py-3 px-3 text-center">Closing KM</th>
                        <th class="py-3 px-3 text-center">Distance</th>
                        <th class="py-3 px-3 text-center">Visits Done</th>
                        <th class="py-3 px-4 text-right">Expenses Claimed</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($records as $rec)
                        @php
                            $distance = ($rec->closing_km && $rec->opening_km) ? max(0, $rec->closing_km - $rec->opening_km) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ \Carbon\Carbon::parse($rec->date)->format('D, d M Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $rec->user ? $rec->user->name . ' (' . $rec->user->role_name . ')' : 'N/A' }}</div>
                            </td>
                            <td class="py-3.5 px-3 text-center text-slate-600 font-medium">
                                {{ $rec->opening_km ?? '—' }}
                            </td>
                            <td class="py-3.5 px-3 text-center text-slate-600 font-medium">
                                {{ $rec->closing_km ?? '—' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-bold text-emerald-700">
                                {{ $distance !== null ? number_format($distance, 1) . ' KM' : '—' }}
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <span class="badge badge-secondary font-bold text-xs">{{ $rec->visits->count() }} visits</span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-slate-900">
                                ₹{{ number_format($rec->expenses->sum('amount'), 2) }}
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                @if($rec->status === 'auto_closed' || (!$rec->closing_km && $rec->date < \Carbon\Carbon::today()))
                                    <span class="badge badge-warning text-[10px]">Auto Closed</span>
                                @elseif($rec->closing_km)
                                    <span class="badge badge-success text-[10px]">Completed</span>
                                @else
                                    <span class="badge badge-primary text-[10px]">In Progress</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.day-starts.show', $rec->id) }}" class="btn-secondary px-2.5 py-1 text-xs inline-flex items-center gap-1 font-semibold text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200">
                                    View Day
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-slate-400">
                                <p class="text-sm font-semibold text-slate-600">No shift records found for this period.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $records->links() }}
        </div>
    </div>

</div>
@endsection
