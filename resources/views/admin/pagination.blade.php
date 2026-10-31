@if ($paginator->hasPages())
    <nav class="approval-pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="approval-page-link" aria-disabled="true" aria-label="Previous page">&lsaquo;</span>
        @else
            <a class="approval-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="approval-page-gap" aria-hidden="true">{{ $element }}</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="approval-page-link is-current" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                    @else
                        <a class="approval-page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="approval-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
        @else
            <span class="approval-page-link" aria-disabled="true" aria-label="Next page">&rsaquo;</span>
        @endif
    </nav>
@endif
