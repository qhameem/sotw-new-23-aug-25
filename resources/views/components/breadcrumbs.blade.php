@props(['items' => []])

@php
    $hasSlotItems = trim((string) $slot) !== '';
@endphp

<nav class="flex mb-1" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-1">
        <li class="inline-flex items-center">
            <a href="{{ route('home') }}"
                class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700"
                aria-label="Home">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 8.5 10 3.75l6.25 4.75v7a.75.75 0 0 1-.75.75h-3.75V11a.75.75 0 0 0-.75-.75h-2a.75.75 0 0 0-.75.75v5.25H4.5a.75.75 0 0 1-.75-.75v-7Z" />
                </svg>
                <span class="sr-only">Home</span>
            </a>
        </li>
        @if($hasSlotItems)
            {{ $slot }}
        @else
            @foreach($items as $item)
                @php
                    $href = $item['link'] ?? $item['url'] ?? null;
                    $isCurrent = $loop->last;
                @endphp
                <li>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        @if($href)
                            <a href="{{ $href }}" class="text-sm font-normal text-gray-500 hover:text-gray-700 md:ml-2"
                                @if($isCurrent) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @else
                            <span class="text-sm font-normal text-gray-700 md:ml-1"
                                @if($isCurrent) aria-current="page" @endif>{{ $item['label'] }}</span>
                        @endif
                    </div>
                </li>
            @endforeach
        @endif
    </ol>
</nav>
