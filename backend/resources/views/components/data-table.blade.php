@props([
    'id'         => 'data-table-' . uniqid(),
    'columns'    => [],   // ['label' => '...', 'key' => '...'] for client-side only
    'ajaxRoute'  => null, // for server-side DataTables
    'serverSide' => false,
    'order'      => [[0, 'asc']],
])
{{--
  Wraps a DataTable with:
  - Consistent header styling
  - Sticky header
  - Empty state (slot:empty or default)
  - Loading skeleton (shown while DT initialises)

  For server-side tables: pass ajaxRoute + serverSide=true and define
  the <thead> with full columns in the default slot.
  For client-side tables: pass columns array and put <tbody> rows in default slot.
--}}

<div
    class="panel overflow-hidden"
    x-data="{ loading: true }"
    x-init="
        $nextTick(() => {
            const el = document.getElementById('{{ $id }}');
            if (!el) { loading = false; return; }
            const dt = new DataTable('#{{ $id }}', {
                serverSide: {{ $serverSide ? 'true' : 'false' }},
                @if ($ajaxRoute)
                ajax: '{{ $ajaxRoute }}',
                @endif
                order: {!! json_encode($order) !!},
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    emptyTable: '',
                    zeroRecords: '',
                },
                drawCallback: function() {
                    loading = false;
                },
                dom: 'rt<\"flex items-center justify-between px-6 py-3 border-t border-surface-border\"<\"text-caption text-ink-400\"i><\"\"p>>',
            });
        });
    "
>
    {{-- Filter / search bar slot --}}
    @isset($header)
        <div class="px-6 py-4 border-b border-surface-border">
            {{ $header }}
        </div>
    @endisset

    {{-- Skeleton while loading --}}
    <div x-show="loading" class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach ($columns as $col)
                        <th>{{ $col['label'] ?? $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 5; $i++)
                    <tr class="skeleton-row">
                        @foreach ($columns as $col)
                            <td><div class="skeleton-bar" style="width: {{ rand(50, 90) }}%"></div></td>
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    {{-- Actual table (hidden until DataTables initialises) --}}
    <div x-show="!loading" class="table-wrap">
        {{ $slot }}
    </div>
</div>
