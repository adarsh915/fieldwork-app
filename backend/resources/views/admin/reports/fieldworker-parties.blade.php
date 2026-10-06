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

        {{-- Table --}}
        <div class="table-wrap">
            <table class="data-table">
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
                        @if ($fieldworker->parties->count() > 0)
                            @php $hasRecords = true; @endphp
                            @foreach ($fieldworker->parties as $index => $party)
                                <tr class="{{ $index === $fieldworker->parties->count() - 1 ? 'border-b-2 border-slate-200' : '' }}">
                                    @if ($index === 0)
                                        <td rowspan="{{ $fieldworker->parties->count() }}" class="align-top border-r border-slate-100 bg-slate-50">
                                            <div class="font-bold text-slate-900">{{ $fieldworker->name }} ({{ $fieldworker->role_name }})</div>
                                            <div class="text-xs text-slate-500 mt-1">
                                                Total: {{ $fieldworker->parties->count() }} parties
                                            </div>
                                        </td>
                                    @endif
                                    <td>
                                        <div class="font-medium text-slate-900">{{ $party->name }}</div>
                                    </td>
                                    <td class="text-slate-600">{{ $party->mobile ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $party->email ?? '—' }}</td>
                                    <td class="text-slate-500 text-sm max-w-[200px] truncate" title="{{ $party->address }}">
                                        {{ $party->address ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
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
