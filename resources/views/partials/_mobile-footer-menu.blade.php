@php
$navItems = [
    [
        'href' => route('home'),
        'text' => 'Products',
        'activeRoutes' => ['home', 'products.byDate', 'products.show', 'categories.show'],
        'icon' => <<<'HTML'
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
            </svg>
        HTML,
    ],
    [
        'href' => route('categories.index'),
        'text' => 'Categories',
        'activeRoutes' => ['categories.index', 'categories.show'],
        'icon' => <<<'HTML'
            <svg class="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M17 10H19C21 10 22 9 22 7V5C22 3 21 2 19 2H17C15 2 14 3 14 5V7C14 9 15 10 17 10Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M5 22H7C9 22 10 21 10 19V17C10 15 9 14 7 14H5C3 14 2 15 2 17V19C2 21 3 22 5 22Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M6 10C8.20914 10 10 8.20914 10 6C10 3.79086 8.20914 2 6 2C3.79086 2 2 3.79086 2 6C2 8.20914 3.79086 10 6 10Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M18 22C20.2091 22 22 20.2091 22 18C22 15.7909 20.2091 14 18 14C15.7909 14 14 15.7909 14 18C14 20.2091 15.7909 22 18 22Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
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
        'href' => route('promote'),
        'text' => 'Promote',
        'activeRoutes' => ['promote'],
        'icon' => <<<'HTML'
            <svg class="size-6 -rotate-45" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor">
                <path fill-rule="evenodd" d="M9.315 7.584C12.195 3.883 16.695 1.5 21.75 1.5a.75.75 0 0 1 .75.75c0 5.056-2.383 9.555-6.084 12.436A6.75 6.75 0 0 1 9.75 22.5a.75.75 0 0 1-.75-.75v-4.131A15.838 15.838 0 0 1 6.382 15H2.25a.75.75 0 0 1-.75-.75 6.75 6.75 0 0 1 7.815-6.666ZM15 6.75a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" clip-rule="evenodd" />
                  <path d="M5.26 17.242a.75.75 0 1 0-.897-1.203 5.243 5.243 0 0 0-2.05 5.022.75.75 0 0 0 .625.627 5.243 5.243 0 0 0 5.022-2.051.75.75 0 1 0-1.202-.897 3.744 3.744 0 0 1-3.008 1.51c0-1.23.592-2.323 1.51-3.008Z" />
            </svg>
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
@endphp

<div class="md:hidden fixed bottom-0 left-0 z-50 w-full h-16 bg-white border-t border-gray-200">
    <div class="grid h-full max-w-lg grid-cols-5 mx-auto font-medium">
        @foreach ($navItems as $item)
            <x-mobile-nav-item :href="$item['href']" :text="$item['text']" :active-routes="$item['activeRoutes']">
                {!! $item['icon'] !!}
            </x-mobile-nav-item>
        @endforeach

    </div>
</div>