@if ($paginator->hasPages())
    @php
        $controlClasses = 'inline-flex min-h-11 items-center justify-center gap-1 rounded-lg border px-3 text-sm font-medium';
        $enabledClasses = 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50';
        $disabledClasses = 'border-gray-200 text-gray-400';
    @endphp

    <nav aria-label="Pagination Navigation" class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 border-t border-gray-200 pt-4">
        <div class="justify-self-start">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="{{ $controlClasses }} {{ $disabledClasses }}">
                    <span aria-hidden="true">←</span> Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $controlClasses }} {{ $enabledClasses }}">
                    <span aria-hidden="true">←</span> Previous
                </a>
            @endif
        </div>

        <span class="text-center text-sm text-gray-600 lg:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        <ol class="hidden items-center gap-1 lg:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li aria-hidden="true" class="px-1 text-gray-500">{{ $element }}</li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page === $paginator->currentPage())
                                <span aria-current="page" aria-label="Page {{ $page }}" class="grid min-h-11 min-w-8 place-items-center rounded-lg bg-primary-50 px-1 text-sm font-semibold text-primary-700">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Page {{ $page }}" class="grid min-h-11 min-w-8 place-items-center rounded-lg px-1 text-sm text-gray-700 hover:bg-gray-100">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ol>

        <div class="justify-self-end">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $controlClasses }} {{ $enabledClasses }}">
                    Next <span aria-hidden="true">→</span>
                </a>
            @else
                <span aria-disabled="true" class="{{ $controlClasses }} {{ $disabledClasses }}">
                    Next <span aria-hidden="true">→</span>
                </span>
            @endif
        </div>
    </nav>
@endif
