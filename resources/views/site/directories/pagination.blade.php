@if($paginator->hasPages())
    <nav aria-label="Directory pages" class="flex flex-wrap items-center gap-2 text-sm">
        @if(!$paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}" class="directory-control">Previous</a>
        @endif
        @foreach($elements as $element)
            @if(is_string($element))
                <span>{{ $element }}</span>
            @else
                @foreach($element as $pageNumber => $url)
                    @if($pageNumber === $paginator->currentPage())
                        <span aria-current="page" class="directory-control directory-current-page">{{ $pageNumber }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Page {{ $pageNumber }}" class="directory-control">{{ $pageNumber }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="directory-control">Next</a>
        @endif
    </nav>
@endif
