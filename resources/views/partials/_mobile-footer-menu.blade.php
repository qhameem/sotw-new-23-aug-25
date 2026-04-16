@php
$navItems = [
    [
        'href' => route('home'),
        'text' => 'Products',
        'activeRoutes' => ['home', 'products.byDate', 'products.show'],
        'icon' => <<<'HTML'
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
            </svg>
        HTML,
    ],
    [
        'href' => route('articles.index'),
        'text' => 'Articles',
        'activeRoutes' => ['articles.index', 'articles.show', 'articles.category', 'articles.tag'],
        'icon' => <<<'HTML'
            <svg class="size-6" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12.394 1.154h-.788l-4.574 12.17L7 23h1v-8.292l1.498 1.499 2.502-2.5 2.499 2.5 1.501-1.5V23h1v-9.5zM12 2.95L13.147 6h-2.294zm0 9.344l-2.502 2.5-1.417-1.418L10.477 7h3.046l2.396 6.374-1.42 1.419z"></path><path fill="none" d="M0 0h24v24H0z"></path></svg>
        HTML,
    ],
    [
        'href' => route('badges.index'),
        'text' => 'Badge',
        'activeRoutes' => ['badges.index'],
        'icon' => <<<'HTML'
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9a9 9 0 1 1 9 0Z" />
            </svg>
        HTML,
    ],
];

$isCategoriesActive = request()->routeIs('categories.index', 'categories.show');
@endphp

<div class="md:hidden fixed bottom-0 left-0 z-50 w-full h-16 border-t border-gray-200" style="background-color: var(--color-navbar-bg, #ffffff);">
    <div class="grid h-full max-w-lg grid-cols-4 mx-auto font-medium">
        @foreach ($navItems as $loopIndex => $item)
            @if ($loopIndex === 1)
                <button type="button" @click="$dispatch('open-categories-menu')"
                    class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group">
                    <div class="{{ $isCategoriesActive ? 'text-primary-500' : 'text-gray-500' }} group-hover:text-gray-900">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 10H19C21 10 22 9 22 7V5C22 3 21 2 19 2H17C15 2 14 3 14 5V7C14 9 15 10 17 10Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M5 22H7C9 22 10 21 10 19V17C10 15 9 14 7 14H5C3 14 2 15 2 17V19C2 21 3 22 5 22Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M6 10C8.20914 10 10 8.20914 10 6C10 3.79086 8.20914 2 6 2C3.79086 2 2 3.79086 2 6C2 8.20914 3.79086 10 6 10Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18 22C20.2091 22 22 20.2091 22 18C22 15.7909 20.2091 14 18 14C15.7909 14 14 15.7909 14 18C14 20.2091 15.7909 22 18 22Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                    <span class="text-xs pt-1 {{ $isCategoriesActive ? 'text-primary-500 font-semibold' : 'text-gray-500' }} group-hover:text-gray-900">Categories</span>
                </button>
            @endif

            <x-mobile-nav-item :href="$item['href']" :text="$item['text']" :active-routes="$item['activeRoutes']">
                {!! $item['icon'] !!}
            </x-mobile-nav-item>
        @endforeach
    </div>
</div>
