@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-4 text-sm">
        <p class="text-muted">
            Showing <span class="text-white">{{ $paginator->firstItem() }}</span>
            to <span class="text-white">{{ $paginator->lastItem() }}</span>
            of <span class="text-white">{{ $paginator->total() }}</span>
        </p>

        <ul class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <li><span class="inline-block cursor-not-allowed rounded-sm border border-line px-3 py-2 text-muted/50" aria-disabled="true">&laquo;</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page" class="inline-block rounded-sm border border-line px-3 py-2 text-white hover:border-gold hover:text-gold">&laquo;</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="inline-block px-3 py-2 text-muted">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span aria-current="page" class="inline-block rounded-sm border border-gold bg-gold px-3 py-2 font-medium text-ink">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Go to page {{ $page }}" class="inline-block rounded-sm border border-line px-3 py-2 text-white hover:border-gold hover:text-gold">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page" class="inline-block rounded-sm border border-line px-3 py-2 text-white hover:border-gold hover:text-gold">&raquo;</a></li>
            @else
                <li><span class="inline-block cursor-not-allowed rounded-sm border border-line px-3 py-2 text-muted/50" aria-disabled="true">&raquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
