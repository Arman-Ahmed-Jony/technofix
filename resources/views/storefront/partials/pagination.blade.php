@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center gap-1.5">
        @php($base = 'grid h-10 min-w-10 place-items-center rounded-xl px-3 font-mono text-[13px] font-medium transition')

        @if ($paginator->onFirstPage())
            <span class="{{ $base }} cursor-default text-faint" aria-disabled="true"><x-icon name="chevron-left" class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} bg-card text-ink ring-1 ring-line hover:ring-line-strong" aria-label="Previous page"><x-icon name="chevron-left" class="size-4" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="{{ $base }} text-faint">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="{{ $base }} bg-ink text-canvas" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $base }} bg-card text-ink-2 ring-1 ring-line hover:text-ink hover:ring-line-strong">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} bg-card text-ink ring-1 ring-line hover:ring-line-strong" aria-label="Next page"><x-icon name="chevron-right" class="size-4" /></a>
        @else
            <span class="{{ $base }} cursor-default text-faint" aria-disabled="true"><x-icon name="chevron-right" class="size-4" /></span>
        @endif
    </nav>
@endif
