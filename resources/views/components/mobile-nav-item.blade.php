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

<a
    href="{{ $href }}"
    @class([
        'group flex min-w-0 flex-col items-center justify-center gap-1 rounded-2xl px-3 py-2 transition-colors',
        'text-primary-600' => $isActive,
        'text-gray-500 hover:text-gray-900' => !$isActive,
    ])
    @if ($isActive)
        aria-current="page"
    @endif
>
    <span
        @class([
            'flex h-10 w-10 items-center justify-center rounded-2xl transition-all duration-200',
            'bg-primary-50 text-primary-600 ring-1 ring-primary-100 shadow-sm' => $isActive,
            'text-gray-500 group-hover:bg-gray-100 group-hover:text-gray-900' => !$isActive,
        ])
    >
        {{ $slot }}
    </span>
    <span class="truncate text-[11px] font-medium leading-none {{ $isActive ? 'text-primary-600' : 'text-gray-500 group-hover:text-gray-900' }}">{{ $text }}</span>
</a>
