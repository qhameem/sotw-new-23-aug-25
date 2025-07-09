@if(!empty($items) && count($items) > 1) {{-- Only render if not homepage (more than just 'Home' link) --}}
<nav aria-label="breadcrumb" class="px-4 md:px-[100px] md:ml-16 mt-4 pt-20 text-xs text-gray-700 ">
    <ol class="inline-flex items-center space-x-1 md:space-x-1 rtl:space-x-reverse">
        @foreach ($items as $index => $item)
            <li class="inline-flex items-center">
                @if ($item['url'])
                    <a href="{{ $item['url'] }}" class="inline-flex items-center hover:text-primary-600 ">
                        @if ($index === 0)
                            <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                            </svg>
                        @endif
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-gray-500 " aria-current="page">{{ $item['label'] }}</span>
                @endif

                @if (!$loop->last)
                    <span class="text-gray-400 ml-2 rtl:rotate-180">></span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif

