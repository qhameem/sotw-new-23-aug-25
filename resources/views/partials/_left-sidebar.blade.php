<div class="bg-white flex flex-col md:border-r md:border-gray-200 h-full">
    <div class="hidden md:flex items-center justify-center h-16 flex-shrink-0">
        <a href="{{ route('home') }}">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800 " />
        </a>
    </div>
    @if(!request()->is('free-todo-list-tool'))
    <div class="hidden md:flex flex-grow p-4 pl-8 ml-8">
        <nav class="flex flex-col space-y-2 text-base">
            <a href="{{ route('home') }}" class="flex items-center p-2 text-gray-900 rounded-lg  group">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 group-hover:text-gray-900 @if(request()->routeIs('home') || request()->routeIs('products.byDate') || request()->routeIs('categories.show') || request()->routeIs('products.show')) text-primary-500 @else text-gray-400 @endif">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                </svg>
               <span class="ml-3 transition delay-50 duration-50 group-hover:translate-x-1 @if(request()->routeIs('home') || request()->routeIs('products.byDate') || request()->routeIs('categories.show') || request()->routeIs('products.show')) font-semibold @else font-normal @endif">Software</span>
            </a>
            <a href="{{ route('articles.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg group">
                <svg class="size-6 group-hover:text-gray-900 @if(request()->routeIs('articles.index') || request()->routeIs('articles.show') || request()->routeIs('articles.category') || request()->routeIs('articles.tag')) text-primary-500 @else text-gray-400 @endif" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12.394 1.154h-.788l-4.574 12.17L7 23h1v-8.292l1.498 1.499 2.502-2.5 2.499 2.5 1.501-1.5V23h1v-9.5zM12 2.95L13.147 6h-2.294zm0 9.344l-2.502 2.5-1.417-1.418L10.477 7h3.046l2.396 6.374-1.42 1.419z"/><path fill="none" d="M0 0h24v24H0z"/></svg>
                 <span class="ml-3 transition delay-50 duration-50 group-hover:translate-x-1 @if(request()->routeIs('articles.index') || request()->routeIs('articles.show') || request()->routeIs('articles.category') || request()->routeIs('articles.tag')) font-semibold @else font-normal @endif">Articles</span>
            </a>

            <div x-data="{ open: {{ request()->routeIs('todolists.index') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="flex items-center justify-between w-full p-2 text-gray-900 rounded-lg group">
                    <div class="flex items-center">
                        <svg class="size-6 group-hover:text-gray-900 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="ml-3">Free Tools</span>
                    </div>
                </button>
                <div x-show="open" class="pl-8">
                    <a href="{{ route('todolists.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg group">
                        <svg class="size-6 group-hover:text-gray-900 @if(request()->routeIs('todolists.index')) text-primary-500 @else text-gray-400 @endif" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="ml-3 transition delay-50 duration-50 group-hover:translate-x-1 @if(request()->routeIs('todolists.index')) font-semibold @else font-normal @endif">To-Do List</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('promote') }}" class="flex items-center p-2 text-gray-900 rounded-lg  group">
                <svg class="size-6 group-hover:text-gray-900 -rotate-45 @if(request()->routeIs('promote')) text-primary-500 @else text-gray-600 @endif" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor">
                    <path fill-rule="evenodd" d="M9.315 7.584C12.195 3.883 16.695 1.5 21.75 1.5a.75.75 0 0 1 .75.75c0 5.056-2.383 9.555-6.084 12.436A6.75 6.75 0 0 1 9.75 22.5a.75.75 0 0 1-.75-.75v-4.131A15.838 15.838 0 0 1 6.382 15H2.25a.75.75 0 0 1-.75-.75 6.75 6.75 0 0 1 7.815-6.666ZM15 6.75a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" clip-rule="evenodd" />
                      <path d="M5.26 17.242a.75.75 0 1 0-.897-1.203 5.243 5.243 0 0 0-2.05 5.022.75.75 0 0 0 .625.627 5.243 5.243 0 0 0 5.022-2.051.75.75 0 1 0-1.202-.897 3.744 3.744 0 0 1-3.008 1.51c0-1.23.592-2.323 1.51-3.008Z" />
                </svg>
                 <span class="ml-3 transition delay-50 duration-50 group-hover:translate-x-1 @if(request()->routeIs('promote')) font-semibold @else font-normal @endif">Promote</span>
            </a>
@if(request()->is('admin/*'))
            <a href="{{ route('products.create') }}"
               class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-gray-400 group-hover:text-gray-900">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="ml-3 transition delay-50 duration-50 group-hover:translate-x-1">Add your product</span>
            </a>
            @endif
        </nav>
    </div>
    @endif
    <div class="hidden md:block md:w-3/4 p-4 mt-auto flex-shrink-0 ml-auto">
        <div class="text-xs text-gray-500 ">
            <a href="{{ route('promote') }}" class="hover:underline">Pricing</a> •
            <a href="{{ route('about') }}" class="hover:underline">About</a> •
            <a href="{{ route('faq') }}" class="hover:underline">FAQ</a> •
            <!-- <a href="{{ route('legal') }}" class="hover:underline">Terms of Use</a> • -->
            <a href="{{ route('legal') }}" class="hover:underline">Privacy Policy</a> •
            <a href="{{ route('changelog.index') }}" class="hover:underline">Changelog</a> •
            <a href="https://x.com/software_on_web" target="_blank" class="hover:underline">X.com</a>
            <!-- <a href="{{ route('promote') }}" class="hover:underline">Advertise</a> -->
            <div class="h-2"></div>
            <span class="text-gray-400 " x-data="{ time: new Date() }" x-init="setInterval(() => time = new Date(), 1000)">
                <span x-text="time.toLocaleString('en-GB', { timeZone: 'UTC', day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false })"></span> UTC
            </span> © 2025 Software on the web
        </div>
    </div>

    <!-- Mobile Footer -->
    @include('partials._mobile-footer-menu')
</div>