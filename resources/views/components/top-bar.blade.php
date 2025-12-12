<div class="fixed top-0 w-full z-50 bg-white h-[3.7rem] border-b border-gray-200 flex-shrink-0 hidden md:block">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <div class="flex items-center">
                <a href="{{ route('home') }}">
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800 " />
                </a>
            </div>
        <div class="flex items-center space-x-4">
            <a href="{{ route('home') }}" class="text-sm  text-gray-900 hover:text-primary-500">Software</a>
            <a href="{{ route('articles.index') }}" class="text-sm text-gray-900 hover:text-primary-500">Articles</a>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="text-sm text-gray-900 hover:text-primary-500 flex items-center">
                    <span>Free Tools</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" style="display: none;">
                    <div class="py-1">
                        <a href="{{ route('todolists.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">To-Do List</a>
                    </div>
                </div>
            </div>
            <a href="{{ route('promote') }}" class="text-sm text-gray-900 hover:text-primary-500">Promote</a>
        </div>
        @guest
            <a href="#" @click.prevent="$dispatch('open-modal', { name: 'login-required-modal' })" class="text-sm bg-gray-900 text-white py-1 px-4 rounded-lg font-semibold">Log in <span aria-hidden="true">&rarr;</span></a>
        @else
            <div class="flex items-center">
                <div class="flex items-center space-x-2">
                   @if(request()->routeIs('articles.index'))
                       <a href="{{ route('articles.create') }}" class="inline-flex items-center justify-center px-4 py-1 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        <div class="flex items-center space-x-2">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-800 stroke-gray-800" viewBox="0 0 24 24"><g fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" color="currentColor"><path d="M6 22v-8.306c0-1.565 0-2.348.215-3.086c.214-.739.63-1.39 1.465-2.693l2.656-4.15C11.088 2.587 11.465 2 12 2s.912.588 1.664 1.764l2.656 4.151c.834 1.303 1.25 1.954 1.465 2.693c.215.738.215 1.52.215 3.086V22"/><path d="M7 11c.632.323 1.489.973 2.28 1c1.019.032 1.707-.863 2.72-.863s1.701.895 2.72.862c.791-.026 1.649-.676 2.28-.999m-5 1v10M10 5h4"/></g></svg>
                        </div>
                        <div>
                            Write Article
                        </div>
                        </div>
                       </a>
                   @else
                       <x-add-product-button />
                   @endif
                   <span class=" w-0.5"></span>
                </div>
                @if(!request()->is('free-todo-list-tool'))
                    <div class="w-[280px] ml-auto mr-0">
                        <div class="relative">
                            <input type="text" id="sidebar-search-input" placeholder="Search..." class="w-full shadow-sm px-3 py-1 border border-gray-300 text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent placeholder-gray-600 placeholder:text-sm">
                            <button id="sidebar-search-clear" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" style="display: none;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div id="sidebar-search-results" class="absolute right-0 z-50 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5" style="display: none;"></div>
                        </div>
                    </div>
                @endif
                <div class="mr-2">
                    @auth
                    <div id="notification-bell-app">
                        <notification-bell :user-id="{{ Auth::id() }}"></notification-bell>
                    </div>
                    @endauth
                </div>
                <div id="user-dropdown-app" data-user="{{ json_encode(Auth::user()) }}" data-is-admin="{{ Auth::user()->hasRole('admin') ? 'true' : 'false' }}"></div>
            </div>
        @endguest
        </div>
    </div>
</div>