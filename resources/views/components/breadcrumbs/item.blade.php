@props(['url' => '', 'last' => false])

<li class="inline-flex items-center">
    @if ($url)
        <a href="{{ $url }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
            {{ $slot }}
        </a>
    @else
        <span class="text-sm font-medium text-gray-500">
            {{ $slot }}
        </span>
    @endif

    @if (!$last)
        <svg class="w-3 h-3 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
    @endif
</li>