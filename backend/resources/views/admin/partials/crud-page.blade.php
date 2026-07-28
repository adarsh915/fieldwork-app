@props([
    'module' => '',
    'title' => '',
    'subtitle' => '',
    'columns' => '', // e.g. "name:Name|mobile:Mobile"
    'seed' => '[]',  // JSON string
])

<section class="panel" data-ui-module="{{ $module }}" data-ui-columns="{{ $columns }}" data-ui-seed='{{ $seed }}'>
    <div class="panel-header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h2 class="font-semibold text-slate-950">{{ $title }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            </div>

            <div class="flex w-full flex-col gap-2 sm:flex-row xl:max-w-xl">
                <input data-ui-search class="form-input" placeholder="Search..." />
                <div class="flex gap-2">
                    <button type="button" class="btn-primary" data-ui-add>Add</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table" data-ui-table>
            <thead>
                <tr></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</section>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4" data-ui-modal>
    <div class="w-full max-w-xl rounded-xl bg-white shadow-xl ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div class="text-base font-black text-slate-950" data-ui-form-title>Add Record</div>
            <button type="button" class="btn-secondary px-2.5 py-1.5 text-xs" data-ui-modal-close>Close</button>
        </div>

        <form class="px-5 py-5" data-ui-form>
            <input type="hidden" name="id" />

            @php
                $cols = collect(explode('|', $columns))
                    ->map(fn ($p) => explode(':', $p, 2))
                    ->map(fn ($p) => ['key' => trim($p[0] ?? ''), 'label' => trim($p[1] ?? $p[0] ?? '')])
                    ->filter(fn ($c) => $c['key'] !== '')
                    ->values();
            @endphp

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($cols as $c)
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $c['label'] }}</label>
                        <input class="form-input mt-2" name="{{ $c['key'] }}" />
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" class="btn-secondary" data-ui-modal-close>Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
