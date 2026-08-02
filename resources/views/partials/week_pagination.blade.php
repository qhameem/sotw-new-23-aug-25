@php
    $previousWeek = $weekPagination['previous'] ?? null;
    $paginationWeeks = $weekPagination['weeks'] ?? [];
@endphp

@if($previousWeek || count($paginationWeeks) > 1)
    <nav class="border-t border-gray-100 px-4 py-6" aria-label="Product weeks">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
            <div class="flex flex-wrap items-center justify-center gap-1.5">
                @foreach($paginationWeeks as $weekItem)
                    <a href="{{ $weekItem['url'] }}" wire:navigate.hover
                       aria-label="View Week {{ $weekItem['week'] }} of {{ $weekItem['year'] }}"
                       @if($weekItem['isSelected']) aria-current="page" @endif
                       @class([
                           'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-2 text-sm font-semibold transition',
                           'bg-primary-600 text-white' => $weekItem['isSelected'],
                           'border border-gray-200 text-gray-600 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700' => !$weekItem['isSelected'],
                       ])>
                        {{ $weekItem['week'] }}
                    </a>
                @endforeach
            </div>

            @if($previousWeek)
                <a href="{{ $previousWeek['url'] }}" wire:navigate.hover
                   class="text-sm font-semibold text-primary-600 hover:text-primary-700 hover:underline">
                    &larr; Check previous week's product
                </a>
            @else
                <span></span>
            @endif
        </div>
    </nav>
@endif
