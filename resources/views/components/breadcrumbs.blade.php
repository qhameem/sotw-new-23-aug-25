@props(['items' => []])

<nav class="flex mb-1 ml-2" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-1">
        <li class="inline-flex items-center">
            <a href="{{ route('home') }}"
                class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <img src="{{ asset('favicon/logo.svg') }}" alt="Home" width="16" height="16" class="w-4 h-4">
            </a>
        </li>
        @foreach($items as $item)
            <li>
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"></path>
                    </svg>
                    @if(isset($item['link']))
                        <a href="{{ $item['link'] }}"
                            class="text-sm font-normal text-gray-500 hover:text-gray-700 md:ml-2">{{ $item['label'] }}</a>
                    @else
                        <span class="text-sm font-normal text-gray-700 md:ml-1">{{ $item['label'] }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</nav>