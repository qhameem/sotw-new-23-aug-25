@props(['items'])

<nav class="mb-4 text-xs text-gray-500" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <li><a href="{{ route('home') }}" wire:navigate.hover class="hover:text-primary-600">Home</a></li>
        @foreach ($items as $item)
            <li class="flex min-w-0 items-baseline gap-2">
                <span aria-hidden="true">/</span>
                @if ($loop->last)
                    <span aria-current="page" class="min-w-0 break-words text-gray-800">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['link'] }}" wire:navigate.hover class="min-w-0 break-words hover:text-primary-600">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
