@props(['mainContentMaxWidth' => 'max-w-3xl', 'sidebarSticky' => true, 'containerMaxWidth' => 'max-w-7xl', 'lockHeight' => false])

<x-top-bar />

<div @class(['flex flex-col md:flex-row mx-auto', $containerMaxWidth, 'md:h-screen md:overflow-hidden' => $lockHeight])>

    <!-- Main Content -->
    <main @class([
        'flex-1 w-full order-1 md:order-2 pl-6 pt-14',
        $mainContentMaxWidth,
        'md:flex md:flex-col md:h-full' => $lockHeight,
        'md:overflow-hidden' => $lockHeight && !request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'products.search')
    ])>
        <div class="flex-shrink-0">
            <x-page-header>
                <x-slot:title>
                    {!! $title !!}
                </x-slot:title>
                @if (isset($actions))
                    <x-slot:actions>
                        {!! $actions !!}
                    </x-slot:actions>
                @endif
                @if (isset($below_header))
                    <x-slot:below_header>
                        {{ $below_header }}
                    </x-slot:below_header>
                @endif
            </x-page-header>
        </div>
        <div @class([
            'md:flex-1 md:flex md:flex-col' => $lockHeight,
            'md:overflow-hidden' => $lockHeight && !request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'products.search'),
            'md:overflow-y-auto' => $lockHeight && request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'products.search')
        ])>
            {{ $slot }}
        </div>
    </main>

    <!-- Right Sidebar -->
    <div @class([
        'w-full md:w-96 flex-shrink-0 order-2 md:order-3 h-auto',
        'md:h-screen' => !$lockHeight,
        'md:sticky top-14' => $sidebarSticky && !$lockHeight,
        'pt-14 md:overflow-y-auto md:h-full' => $lockHeight,
    ])>
        <div class="flex-grow p-6">
            @if (isset($right_sidebar_content) && trim($right_sidebar_content))
                {{ $right_sidebar_content }}
            @else
                @include('partials._right-sidebar')
            @endif
        </div>
    </div>


</div>
<x-footer />