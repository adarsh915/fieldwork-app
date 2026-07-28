@extends('admin.layouts.app')

@section('title', $employee->name . ' — Assigned Parties')
@section('breadcrumb', 'Admin / Employees / ' . $employee->name . ' / Parties')
@section('page_title', 'Assigned Parties')

@section('content')

    {{-- Flash Messages --}}
    @if(session('status'))
        <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-start gap-3">
            <span class="text-emerald-600 mt-0.5">✅</span>
            <p class="text-sm font-medium text-emerald-800">{{ session('status') }}</p>
        </div>
    @endif

    {{-- Employee Info Card --}}
    <div class="mb-5 flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-lg font-bold text-white">
            {{ strtoupper(substr($employee->name, 0, 2)) }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-bold text-slate-950 text-lg leading-tight">{{ $employee->name }}</h3>
                @if($employee->is_active ?? true)
                    <span class="badge badge-success text-xs">Active</span>
                @else
                    <span class="badge badge-danger text-xs">Inactive</span>
                @endif
            </div>
            <div class="mt-0.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                <span>{{ $employee->email }}</span>
                @if($employee->phone)
                    <span>{{ $employee->phone }}</span>
                @endif
                @if($employee->employee_id)
                    <span class="font-mono">ID: {{ $employee->employee_id }}</span>
                @endif
                @if($employee->position)
                    <span>{{ $employee->position }}</span>
                @endif
            </div>
            <div class="mt-1 flex flex-wrap gap-1">
                @forelse($employee->roles as $role)
                    <span class="badge badge-info text-xs">{{ $role->name }}</span>
                @empty
                    <span class="badge badge-neutral text-xs">No role</span>
                @endforelse
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <a href="{{ route('admin.employees.index') }}"
               class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition border border-slate-200">
                ← Back to Employees
            </a>
            @can('employees.edit')
                <a href="{{ route('admin.employees.edit', $employee->id) }}"
                   class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition border border-amber-200">
                    ✏️ Edit Employee
                </a>
            @endcan
        </div>
    </div>

    {{-- Parties Panel --}}
    <section class="panel">
        {{-- Panel Header --}}
        <div class="panel-header">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">
                        Assigned Parties
                        <span class="ml-1.5 inline-flex items-center justify-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700">
                            {{ $parties->total() }}
                        </span>
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        All parties (customers) assigned to <strong>{{ $employee->name }}</strong>.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap items-center gap-2">
                    @can('customers.view')
                        <a href="{{ route('admin.parties.index', ['employee_id' => $employee->id]) }}"
                           class="inline-flex items-center gap-1 rounded-md bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 transition border border-violet-200">
                            🗂 View in Party Master
                        </a>
                    @endcan
                    @can('employees.edit')
                        <a href="{{ route('admin.employees.edit', $employee->id) }}"
                           class="btn-primary whitespace-nowrap">
                            + Manage Assigned Parties
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Search --}}
            <form method="GET" action="{{ route('admin.employees.parties', $employee->id) }}"
                  class="mt-4 flex flex-wrap gap-3">
                <input
                    name="q"
                    value="{{ $search }}"
                    class="form-input flex-1 min-w-[240px]"
                    placeholder="Search by party name, mobile or email…"
                >
                <button class="btn-primary">Search</button>
                @if($search)
                    <a href="{{ route('admin.employees.parties', $employee->id) }}" class="btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Party Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Assigned On</th>
                        @can('customers.view')
                            <th class="text-center">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parties as $index => $party)
                        <tr>
                            <td class="text-slate-500 text-sm">{{ $parties->firstItem() + $index }}</td>

                            {{-- Party Name --}}
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white">
                                        {{ strtoupper(substr($party->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-950">{{ $party->name }}</div>
                                        <div class="text-xs text-slate-400">ID #{{ $party->id }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Mobile --}}
                            <td class="text-slate-600">
                                @if($party->mobile)
                                    <a href="tel:{{ $party->mobile }}"
                                       class="hover:text-indigo-600 transition">{{ $party->mobile }}</a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Email --}}
                            <td class="text-slate-600">
                                @if($party->email)
                                    <a href="mailto:{{ $party->email }}"
                                       class="hover:text-indigo-600 transition">{{ $party->email }}</a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Address --}}
                            <td class="max-w-xs text-slate-500 text-sm truncate" title="{{ $party->address }}">
                                {{ $party->address ?? '—' }}
                            </td>

                            {{-- Assigned On (pivot timestamp) --}}
                            <td class="text-slate-500 text-sm whitespace-nowrap">
                                @if($party->pivot && $party->pivot->created_at)
                                    {{ $party->pivot->created_at->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Actions --}}
                            @can('customers.view')
                                <td>
                                    <div class="flex items-center justify-center gap-2 flex-wrap">
                                        @can('customers.edit')
                                            <a href="{{ route('admin.parties.edit', $party->id) }}"
                                               class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition border border-amber-200">
                                                ✏️ Edit
                                            </a>
                                        @endcan
                                        <a href="{{ route('admin.parties.assign-employees', $party->id) }}"
                                           class="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 transition border border-violet-200">
                                            👥 Assign
                                        </a>
                                    </div>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td class="py-16 text-center text-slate-500" colspan="7">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="text-5xl">🏢</span>
                                    <div>
                                        <p class="font-semibold text-slate-700">No parties assigned</p>
                                        @if($search)
                                            <p class="mt-1 text-sm">No results for <strong>"{{ $search }}"</strong>.
                                                <a href="{{ route('admin.employees.parties', $employee->id) }}"
                                                   class="text-indigo-600 underline hover:text-indigo-800">Clear search</a>
                                            </p>
                                        @else
                                            <p class="mt-1 text-sm text-slate-400">
                                                No parties have been assigned to <strong>{{ $employee->name }}</strong> yet.
                                            </p>
                                            @can('employees.edit')
                                                <a href="{{ route('admin.employees.edit', $employee->id) }}"
                                                   class="btn-primary mt-3 inline-flex">
                                                    + Assign Parties
                                                </a>
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($parties->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $parties->links() }}
            </div>
        @endif
    </section>

@endsection
