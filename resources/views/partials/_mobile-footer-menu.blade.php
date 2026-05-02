@php
$navItems = [
    [
        'href' => route('home'),
        'text' => 'Products',
        'activeRoutes' => ['home', 'products.byDate', 'products.byWeek', 'products.byMonth', 'products.byYear', 'products.search', 'products.show'],
        'icon' => <<<'HTML'
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 6.75A2.25 2.25 0 0 1 7 4.5h10A2.25 2.25 0 0 1 19.25 6.75v10.5A2.25 2.25 0 0 1 17 19.5H7a2.25 2.25 0 0 1-2.25-2.25V6.75Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 9.5h14.5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 14h3.5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.25 14h2.75" />
            </svg>
        HTML,
    ],
    [
        'href' => route('categories.index'),
        'text' => 'Categories',
        'activeRoutes' => ['categories.*'],
        'icon' => <<<'HTML'
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h5.5v5.5H5z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 5H19v5.5h-5.5z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13.5h5.5V19H5z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 13.5H19V19h-5.5z" />
            </svg>
        HTML,
    ],
    [
        'href' => route('articles.index'),
        'text' => 'Articles',
        'activeRoutes' => ['articles.index', 'articles.show', 'articles.category', 'articles.tag'],
        'icon' => <<<'HTML'
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 5.5h9.75A2.25 2.25 0 0 1 19 7.75v8.5A2.25 2.25 0 0 1 16.75 18.5H8.5A2.5 2.5 0 0 1 6 16V6.5A1 1 0 0 1 7 5.5Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9h7" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.5h7" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 16h3.5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5v8.25A2.75 2.75 0 0 0 8.75 18.5" />
            </svg>
        HTML,
    ],
];
@endphp

<div class="md:hidden" aria-hidden="true" style="height: calc(4.5rem + env(safe-area-inset-bottom, 0px));"></div>
<nav
    class="md:hidden fixed inset-x-0 bottom-0 z-50 border-t border-gray-200/80 bg-white/95 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] backdrop-blur-md"
    style="padding-bottom: env(safe-area-inset-bottom, 0px); background-color: var(--color-navbar-bg, #ffffff);"
    aria-label="Mobile navigation"
>
    <div class="mx-auto grid h-[4.5rem] max-w-md grid-cols-3 items-center px-3">
        @foreach ($navItems as $item)
            <x-mobile-nav-item :href="$item['href']" :text="$item['text']" :active-routes="$item['activeRoutes']">
                {!! $item['icon'] !!}
            </x-mobile-nav-item>
        @endforeach
    </div>
</nav>
