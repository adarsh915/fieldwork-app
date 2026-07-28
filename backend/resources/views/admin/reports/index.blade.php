@extends('admin.layouts.app')

@section('title', 'Reports')
@section('breadcrumb', 'Admin / Reports')
@section('page_title', 'Reports Dashboard')

@section('content')
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Fieldworker Parties Report</h3>
                    <p class="mt-1 text-sm text-slate-500">View a list of all fieldworkers and their assigned parties (customers) along with contact details.</p>
                    <a href="{{ route('admin.reports.fieldworker-parties') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                        View Report &rarr;
                    </a>
                </div>
            </div>
        </div>

        {{-- Add more report cards here as they are created --}}
    </div>
@endsection
