@props(['mainContentMaxWidth' => 'max-w-3xl', 'sidebarSticky' => true, 'containerMaxWidth' => 'max-w-7xl', 'lockHeight' => false, 'headerPadding' => 'px-4 sm:px-6 lg:px-8', 'mainPadding' => 'px-4 sm:px-6 lg:px-8'])

<x-top-bar />

<div @class(['flex flex-col min-h-0', 'md:h-screen' => $lockHeight])>

    <!-- Body Container (Middle + Right Columns) -->
    <div class="flex-1 flex flex-col md:flex-row min-h-0 w-full relative z-0">
        <div @class(['flex flex-col md:flex-row flex-1 md:overflow-hidden w-full mx-auto', $containerMaxWidth])>
            <!-- Main Content -->
            <main @class([
                'flex-1 w-full order-1 md:order-2 md:pt-[3.7rem] min-h-0',
                $mainPadding,
                $mainContentMaxWidth,
                'md:flex md:flex-col md:h-full' => $lockHeight,
                'md:overflow-hidden' => $lockHeight && !request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'products.search')
            ])>
                <div class="flex-shrink-0">
                    <div class="h-[75px] md:hidden w-full"></div>
                    <x-page-header :padding="$headerPadding">
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
                    'md:flex-1 md:flex md:flex-col min-h-0' => $lockHeight,
                    'md:overflow-hidden' => $lockHeight && !request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'products.search'),
                    'md:overflow-y-auto pb-32' => $lockHeight && request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'products.search')
                ])>
                    {{ $slot }}
                </div>
            </main>

            <!-- Right Sidebar -->
            <div @class([
                'w-full md:w-96 flex-shrink-0 order-2 md:order-3 h-auto min-h-0 md:pt-[3.7rem]',
                'md:h-screen' => !$lockHeight,
                'md:sticky top-[3.7rem]' => $sidebarSticky && !$lockHeight,
                'md:flex md:flex-col md:h-full' => $lockHeight,
            ])>
                <div @class([
                    'p-6 min-h-0',
                    'md:flex-1 md:overflow-y-auto' => $lockHeight
                ])>
                    <div class="pb-40">
                        @if (isset($right_sidebar_content) && trim($right_sidebar_content))
                            {{ $right_sidebar_content }}
                        @else
                            @include('partials._right-sidebar')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Container (Outside Body) -->
    <div class="flex-shrink-0 relative bg-white w-full z-20">
        <x-footer />
    </div>
</div>