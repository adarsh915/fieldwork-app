@extends('admin.layouts.app')

@section('title', $config['label'])
@section('breadcrumb', 'ADMIN / MASTER DATA')
@section('page_title', 'Master Data')

@section('content')

{{-- Tab navigation --}}
<div class="flex flex-wrap gap-2 mb-6">
    @foreach ($types as $key => $typeConfig)
        <a href="{{ route('admin.master-data.index', $key) }}"
           class="{{ $type === $key ? 'btn-primary' : 'btn-secondary' }}">
            {{ $typeConfig['label'] }}
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Add form --}}
    <div class="panel p-6">
        <h3 class="text-section-title mb-4">Add {{ \Illuminate\Support\Str::singular($config['label']) }}</h3>
        <form method="POST" action="{{ route('admin.master-data.store', $type) }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Name <span class="text-danger-text">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="{{ $config['boolean_field'] }}" value="1" class="h-4 w-4 rounded border-surface-border text-brand-600">
                <span class="text-sm font-medium text-ink-900">{{ $config['boolean_label'] }}</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-surface-border text-brand-600">
                <span class="text-sm font-medium text-ink-900">Active</span>
            </label>
            <x-button type="submit" variant="primary" class="w-full justify-center">
                <x-lucide-plus class="w-4 h-4" />
                Add Record
            </x-button>
        </form>
    </div>

    {{-- Records table --}}
    <div class="xl:col-span-2 panel overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>{{ $config['boolean_label'] }}</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td class="text-body-emphasis">{{ $record->name }}</td>
                            <td>
                                @if ($record->{$config['boolean_field']})
                                    <span class="badge badge-success"><x-lucide-check-circle class="w-3 h-3" /> Yes</span>
                                @else
                                    <span class="badge badge-neutral">No</span>
                                @endif
                            </td>
                            <td>
                                @if ($record->is_active)
                                    <span class="badge badge-success"><x-lucide-check-circle class="w-3 h-3" /> Active</span>
                                @else
                                    <span class="badge badge-muted">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div x-data="{ editing: false }" class="relative flex justify-end">
                                    <x-button variant="secondary" class="text-xs px-2.5 py-1.5" @click="editing = !editing">
                                        <x-lucide-pencil class="w-3.5 h-3.5" />
                                        Edit
                                    </x-button>

                                    <div
                                        x-show="editing"
                                        @click.outside="editing = false"
                                        x-transition
                                        class="absolute right-0 top-full mt-1 w-72 bg-white border border-surface-border rounded-xl shadow-xl z-20 p-4"
                                        style="display: none;"
                                    >
                                        <p class="text-sm font-semibold text-ink-900 mb-3">Edit Record</p>
                                        <form method="POST" action="{{ route('admin.master-data.update', [$type, $record->id]) }}" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="form-label">Name</label>
                                                <input type="text" name="name" value="{{ $record->name }}" class="form-input" required>
                                            </div>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="{{ $config['boolean_field'] }}" value="1"
                                                    @checked($record->{$config['boolean_field']}) class="h-4 w-4 rounded border-surface-border text-brand-600">
                                                <span class="text-sm">{{ $config['boolean_label'] }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="is_active" value="1"
                                                    @checked($record->is_active) class="h-4 w-4 rounded border-surface-border text-brand-600">
                                                <span class="text-sm">Active</span>
                                            </label>
                                            <x-button type="submit" variant="primary" class="w-full justify-center">Save</x-button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.master-data.destroy', [$type, $record->id]) }}"
                                              onsubmit="return confirm('Delete this record?')" class="mt-2">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="danger" class="w-full justify-center">
                                                <x-lucide-trash-2 class="w-4 h-4" />
                                                Delete
                                            </x-button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2 text-ink-400">
                                    <x-lucide-inbox class="w-10 h-10" />
                                    <p class="text-sm">No records found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
