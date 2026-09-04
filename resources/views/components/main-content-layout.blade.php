@unless($distractionFree)
    @persist('site-top-bar')
        <x-top-bar />
    @endpersist
@endunless

<div @class(['flex flex-col min-h-screen', 'md:h-screen' => $lockHeight])>

    <!-- Body Container (Middle + Right Columns) -->
    <div class="flex-1 flex flex-col md:flex-row min-h-0 w-full relative z-0">
        <div
            @class([
                'flex flex-col md:flex-row flex-1 w-full mx-auto',
                'md:overflow-visible' => !$lockHeight || request()->routeIs('products.show'),
                'md:overflow-hidden' => $lockHeight && !request()->routeIs('products.show'),
                $containerMaxWidth,
                'mx-0' => $hideSidebar,
            ])
            @if($hideSidebar)
                style="max-width: none; width: 100%;"
            @endif
        >
            @if (isset($left_sidebar_content) && trim($left_sidebar_content))
                <aside class="site-header-padding hidden xl:block xl:w-64 flex-shrink-0 order-1 min-h-0 border-r border-gray-100"
                    aria-label="Product filters">
                    <div @class([
                        'site-header-sticky p-5 min-h-0 sticky',
                    ])>
                        {!! $left_sidebar_content !!}
                    </div>
                </aside>
            @endif

            <!-- Main Content -->
            <main
                @class([
                    'flex-1 w-full min-w-0 order-1 md:order-2 min-h-0',
                    'site-header-padding' => !$distractionFree,
                    $mainPadding,
                    $mainContentMaxWidth,
                    'md:flex md:flex-col md:h-full' => $lockHeight,
                    'md:overflow-hidden' => $lockHeight && !request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'categories.show.page', 'products.search')
                ])
                @if($hideSidebar)
                    style="max-width: none; width: 100%; flex-basis: 100%;"
                @endif
            >
                <div class="flex-shrink-0">
                    @unless($hideDesktopPageHeader ?? false)
                        <div class="h-[75px] md:hidden w-full"></div>
                        <x-page-header :padding="$headerPadding">
                            @if (isset($before_title))
                                <x-slot:before_title>
                                    {!! $before_title !!}
                                </x-slot:before_title>
                            @endif
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
                    @endunless
                </div>
                <div @class([
                    'min-w-0',
                    'md:flex-1 md:flex md:flex-col min-h-0' => $lockHeight,
                    'md:overflow-hidden' => $lockHeight && !request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'categories.show.page', 'products.search'),
                    'md:overflow-y-auto overscroll-contain scrollbar-hide' => $lockHeight && request()->routeIs('home', 'products.byWeek', 'products.byDate', 'categories.show', 'categories.show.page', 'products.search')
                ])>
                    {{ $slot }}

                    @if($lockHeight && !$distractionFree)
                        @persist('site-footer')
                            <div class="mt-auto flex-shrink-0 border-t border-gray-100" style="background-color: var(--color-body-bg, #ffffff);">
                                <x-footer />
                            </div>
                        @endpersist
                    @endif
                </div>
            </main>

            <!-- Right Sidebar -->
            @unless($hideSidebar)
                <div @class([
                    'site-header-padding w-full md:w-96 flex-shrink-0 order-2 md:order-3 h-auto min-h-0',
                    'md:flex md:flex-col md:h-full' => $lockHeight,
                ])>
                    <div @class([
                        'p-6 min-h-0',
                        'md:flex-1 md:overflow-hidden' => $lockHeight,
                        'site-header-sticky md:sticky' => $sidebarSticky && !$lockHeight,
                    ])>
                        <div @class([
                        ])>
                            <div class="mb-6">
                                <x-header-stats :stats="$headerStats" />
                            </div>
                            @if (isset($right_sidebar_content) && trim($right_sidebar_content))
                                {!! $right_sidebar_content !!}
                            @else
                                @include('partials._right-sidebar')
                            @endif
                        </div>
                    </div>
                </div>
            @endunless
        </div>
    </div>

    <!-- Footer Container (Outside Body) -->
    @if(!$distractionFree && !$lockHeight)
        @persist('site-footer')
            <div class="flex-shrink-0 relative w-full z-20" style="background-color: var(--color-body-bg, #ffffff);">
                <x-footer />
            </div>
        @endpersist
    @endif
</div>
