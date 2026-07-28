@extends('admin.layouts.app')

@section('title', 'Customers')
@section('breadcrumb', 'ADMIN / CUSTOMERS')
@section('page_title', 'Customer Master')

@section('content')
<div class="panel">
    <div class="panel-header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-section-title">Customer Master</h2>
                <p class="text-caption mt-1">Browse parties, contact details, and customer records.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.customers.index') }}" class="mt-4 flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                <input name="q" value="{{ $search }}" class="form-input pl-9" placeholder="Search name, mobile, email…">
            </div>
            <x-button type="submit" variant="primary">Search</x-button>
            @if ($search)
                <x-button href="{{ route('admin.customers.index') }}" variant="secondary">Reset</x-button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <div class="text-body-emphasis">{{ $customer->name }}</div>
                            <div class="text-caption">ID #{{ $customer->id }}</div>
                        </td>
                        <td class="text-body">{{ $customer->mobile ?? '—' }}</td>
                        <td class="text-body">{{ $customer->email ?? '—' }}</td>
                        <td class="max-w-sm text-caption truncate">{{ $customer->address ?? '—' }}</td>
                        <td class="text-caption">{{ $customer->created_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-ink-400">
                                <x-lucide-inbox class="w-10 h-10" />
                                <p class="text-sm font-medium">No customers found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-surface-border px-6 py-4">
        {{ $customers->links() }}
    </div>
</div>
@endsection
