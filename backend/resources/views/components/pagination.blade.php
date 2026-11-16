@if ($paginator->total() > 0)
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-surface-border">
        {{-- Results Info (Left) --}}
        <div class="text-xs text-ink-600">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="font-semibold text-ink-900">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-semibold text-ink-900">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            {!! __('of') !!}
            <span class="font-semibold text-ink-900">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </div>

        {{-- Page Buttons (Right) --}}
        @if ($paginator->hasPages())
            <div class="dt-paging flex items-center gap-1">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="dt-paging-button previous disabled" aria-disabled="true">
                        ‹ Prev
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="dt-paging-button previous" aria-label="{{ __('pagination.previous') }}">
                        ‹ Prev
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="dt-paging-button ellipsis" aria-disabled="true">…</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="dt-paging-button current" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="dt-paging-button" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="dt-paging-button next" aria-label="{{ __('pagination.next') }}">
                        Next ›
                    </a>
                @else
                    <span class="dt-paging-button next disabled" aria-disabled="true">
                        Next ›
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif

