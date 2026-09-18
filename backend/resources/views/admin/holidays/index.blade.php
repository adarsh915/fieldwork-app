@extends('admin.layouts.app')

@section('title', 'Holidays Management')
@section('breadcrumb', 'ADMIN / HOLIDAYS')
@section('page_title', 'Declared Holidays')

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('success') }}</p>
    </div>
@endif

@if($errors->any())
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-danger-bg px-4 py-3">
        <x-lucide-alert-circle class="w-5 h-5 text-danger-text shrink-0" />
        <div>
            @foreach($errors->all() as $error)
                <p class="text-sm font-medium text-danger-text">{{ $error }}</p>
            @endforeach
        </div>
    </div>
@endif

<div x-data="{ declareModalOpen: false }">

    {{-- Header Action Bar --}}
    <div class="panel p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <form method="GET" action="{{ route('admin.holidays.index') }}" class="flex items-center gap-3">
                <label class="form-label mb-0">Select Year:</label>
                <select name="year" onchange="this.form.submit()" class="form-input w-auto min-w-[120px]">
                    @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>

            <x-button type="button" variant="primary" @click="declareModalOpen = true" class="flex items-center gap-2">
                <x-lucide-plus class="w-4 h-4" /> Declare Holiday
            </x-button>
        </div>
    </div>

    {{-- Data Table Panel --}}
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Holiday Name</th>
                        <th>Type</th>
                        <th>Declared By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $holiday)
                    <tr>
                        <td class="font-bold text-ink-900 whitespace-nowrap">
                            {{ $holiday->date->format('d M Y') }}
                        </td>
                        <td class="text-ink-600 whitespace-nowrap text-sm">
                            {{ $holiday->date->format('l') }}
                        </td>
                        <td class="font-semibold text-ink-900">
                            {{ $holiday->name }}
                        </td>
                        <td>
                            @if($holiday->type === 'national')
                                <span class="badge badge-primary">National Holiday</span>
                            @else
                                <span class="badge badge-success">Company Holiday</span>
                            @endif
                        </td>
                        <td class="text-sm text-ink-600">
                            {{ $holiday->createdBy->name ?? 'Admin' }}
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <form action="{{ route('admin.holidays.destroy', $holiday->id) }}" method="POST" onsubmit="return confirm('Delete holiday {{ addslashes($holiday->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="px-2 py-1 text-xs">
                                    <x-lucide-trash-2 class="w-3.5 h-3.5 inline" /> Delete
                                </x-button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-6 text-ink-500">No holidays declared for {{ $year }}.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Declare Holiday Modal --}}
    <div x-show="declareModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div @click.outside="declareModalOpen = false" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-ink-900">Declare New Holiday</h3>
                <button type="button" @click="declareModalOpen = false" class="text-ink-400 hover:text-ink-700">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>
            <form action="{{ route('admin.holidays.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Holiday Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="date" required class="form-input" />
                </div>

                <div class="mb-4">
                    <label class="form-label">Holiday Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Diwali, Independence Day" class="form-input" />
                </div>

                <div class="mb-6">
                    <label class="form-label">Holiday Type <span class="text-rose-500">*</span></label>
                    <select name="type" required class="form-input">
                        <option value="company">Company Holiday</option>
                        <option value="national">National Holiday</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="declareModalOpen = false">Cancel</x-button>
                    <x-button type="submit" variant="primary">Save Holiday</x-button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
