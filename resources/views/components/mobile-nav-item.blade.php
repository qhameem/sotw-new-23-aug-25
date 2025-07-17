@props([
    'href',
    'text',
    'activeRoutes' => [],
])

@php
    $isActive = false;
    foreach ($activeRoutes as $route) {
        if (request()->routeIs($route)) {
            $isActive = true;
            break;
        }
    }
@endphp

<a href="{{ $href }}" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group">
    <div class="{{ $isActive ? 'text-primary-500' : 'text-gray-500' }} group-hover:text-gray-900">
        {{ $slot }}
    </div>
    <span class="text-xs pt-1 {{ $isActive ? 'text-primary-500 font-semibold' : 'text-gray-500' }} group-hover:text-gray-900">{{ $text }}</span>
</a>