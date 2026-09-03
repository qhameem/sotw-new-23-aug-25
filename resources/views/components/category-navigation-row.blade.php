@props(['categoryNavigationSummaries' => []])

@php
    $isHomeRoute = request()->routeIs('home', 'products.byWeek', 'products.byDate');
@endphp

<div class="h-14 w-full">
    <nav
        class="flex h-full w-full items-center justify-between gap-1 overflow-x-auto bg-stone-100 px-5 scrollbar-hide"
        aria-label="Software categories"
    >
        <a
            href="{{ route('home') }}"
            wire:navigate.hover
            @class([
                'inline-flex shrink-0 items-center gap-2 rounded-2xl px-4 py-2 text-xs font-semibold transition-colors',
                'bg-primary-500 text-white shadow-sm' => $isHomeRoute,
                'text-gray-700 hover:bg-white hover:text-primary-600' => !$isHomeRoute,
            ])
        >
            <x-phosphor-grid-nine class="h-4 w-4" />
            <span>All</span>
        </a>

        @foreach (($categoryNavigationSummaries ?? []) as $group)
            @if ($group['key'] !== 'view-all')
                <a
                    href="{{ route('software-groups.show', ['group' => $group['key']]) }}"
                    wire:navigate.hover
                    @class([
                        'inline-flex shrink-0 items-center gap-2 rounded-2xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                        'bg-primary-500 text-white shadow-sm' => request()->route('group') === $group['key'],
                        'text-gray-800 hover:bg-white hover:text-primary-600' => request()->route('group') !== $group['key'],
                    ])
                    aria-label="Browse {{ $group['label'] }} software"
                >
                    <span @class(['text-primary-400' => request()->route('group') !== $group['key']]) aria-hidden="true">
                        @switch($group['icon'])
                            @case('brain')
                                <x-phosphor-brain class="h-4 w-4" />
                                @break
                            @case('megaphone')
                                <x-phosphor-megaphone class="h-4 w-4" />
                                @break
                            @case('briefcase')
                                <x-phosphor-briefcase class="h-4 w-4" />
                                @break
                            @case('palette')
                                <x-phosphor-palette class="h-4 w-4" />
                                @break
                            @case('terminal-window')
                                <x-phosphor-terminal-window class="h-4 w-4" />
                                @break
                            @case('bank')
                                <x-phosphor-bank class="h-4 w-4" />
                                @break
                            @case('lifebuoy')
                                <x-phosphor-lifebuoy class="h-4 w-4" />
                                @break
                            @case('puzzle-piece')
                                <x-phosphor-puzzle-piece class="h-4 w-4" />
                                @break
                            @default
                                <x-phosphor-grid-nine class="h-4 w-4" />
                        @endswitch
                    </span>
                    <span>{{ $group['label'] }}</span>
                </a>
            @endif
        @endforeach

        <a
            href="{{ route('categories.index') }}"
            wire:navigate.hover
            @class([
                'inline-flex shrink-0 items-center gap-2 rounded-2xl px-4 py-2 text-xs font-semibold transition-colors',
                'bg-primary-500 text-white shadow-sm' => request()->routeIs('categories.index'),
                'text-primary-500 hover:bg-white hover:text-primary-600' => !request()->routeIs('categories.index'),
            ])
        >
            <x-phosphor-grid-nine class="h-4 w-4" />
            <span>Explore</span>
        </a>
    </nav>
</div>
