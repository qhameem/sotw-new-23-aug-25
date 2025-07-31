<div class="relative flex items-center ms-auto  sm:ms-2 z-50" id="userDropdownContainer">
    <button id="userDropdownTrigger" type="button"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
        aria-haspopup="true" aria-expanded="false" aria-controls="userDropdownMenu">
        @auth
            @if (Auth::user()->google_avatar)
                <img src="{{ Auth::user()->google_avatar }}" alt="{{ Auth::user()->name }}" class="h-8 w-8 rounded-full object-cover me-2" />
            @else
                <span class="flex items-center justify-center h-8 w-8 rounded-full bg-primary-500 text-white text-xs font-semibold me-2">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
            @endif
            <span class="hidden sm:block">{{ strtok(Auth::user()->name, ' ') }}</span>
        @else
            <a href="#" @click.prevent="$dispatch('open-modal', { name: 'login-required-modal' })" class="text-sm bg-gray-900 text-white py-1 px-4 rounded-lg font-semibold">Log in <span aria-hidden="true">&rarr;</span></a>
        @endauth
        <svg xmlns="http://www.w3.org/2000/svg"
             width="24" height="24" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round"
             class="h-4 w-4 ms-1 hidden sm:block">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <!-- Sliding drawer-style dropdown -->
    <div id="userDropdownMenu"
         class="hidden md:absolute md:top-full md:right-0 md:mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
         role="menu" aria-orientation="vertical" aria-labelledby="userDropdownTrigger" tabindex="-1">

        <div class="py-1">
            @auth
            <div class="px-4 py-3">
                <a href="{{ route('profile.edit') }}" class="block text-sm font-semibold text-gray-900 hover:text-primary-500">
                    <div class="flex items-center">
                        <span>{{ Auth::user()->name }}</span>
                        @if(Auth::user()->hasRole('admin'))
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 ml-1 text-primary-500"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        @endif
                    </div>
                </a>
                <p class="text-sm text-gray-500">
                    Manage integrations, resume, collections, etc.
                </p>
            </div>
            <div class="border-t border-gray-200 my-1"></div>
            @role('admin')
                <a href="{{ route('admin.product-approvals.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>Product Approvals</a>
                <a href="{{ route('admin.products.pending-edits.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12 22h6a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v10"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10.4 12.6a2 2 0 1 1 3 3L8 21l-4 1 1-4Z"/></svg>Pending Product Edits</a>
                <a href="{{ route('admin.categories.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.432 0l6.568-6.568a2.426 2.426 0 0 0 0-3.432L12.586 2.586z"/><path d="M7 7h.01"/></svg>Manage Categories</a>
                <a href="{{ route('admin.products.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>Manage Products</a>
                <a href="{{ route('admin.premium-products.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>Premium Products</a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Manage Users</a>
                <a href="{{ route('admin.product-reviews.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12 22h6a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v10"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m9 15 2 2 4-4"/></svg>Product Reviews</a>
                <a href="{{ route('admin.changelogs.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Changelog</a>
                <div class="border-t border-gray-200 my-1"></div>
                <a href="{{ route('admin.theme.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.47-1.125-.29-.289-.68-.47-1.128-.47H10c-.615 0-1.111-.496-1.111-1.111 0-.615.496-1.111 1.111-1.111h2.222c.615 0 1.111.496 1.111 1.111 0 .615-.496 1.111-1.111 1.111H12c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3v.133c.383.05.76.144 1.125.28.43.16.83.386 1.188.66.36.27.67.59.92.95.25.36.45.76.58 1.18.13.42.2.87.2 1.34 0 1.82-1.48 3.3-3.3 3.3-.926 0-1.648.746 1.648-1.688 0 .94.722 1.688 1.648 1.688.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.47-1.125-.29-.289-.68-.47-1.128-.47H14c-.615 0-1.111-.496-1.111-1.111 0-.615.496-1.111 1.111-1.111h.333c.615 0 1.111.496 1.111 1.111 0 .615-.496 1.111-1.111 1.111h-.333c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3v.133c.383.05.76.144 1.125.28.43.16.83.386 1.188.66.36.27.67.59.92.95.25.36.45.76.58 1.18.13.42.2.87.2 1.34 0 1.82-1.48 3.3-3.3 3.3-.926 0-1.648.746 1.648-1.688C13.648 21.254 12.926 22 12 22z"/><path d="M12 12v.01"/></svg>Theme Settings</a>
                <a href="{{ route('admin.settings.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 0 2l-.15.08a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l-.22-.38a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1 0-2l.15-.08a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>Settings</a>
                <a href="{{ route('admin.seo.meta-tags.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/></svg>Meta Tags</a>
                <div class="border-t border-gray-200 my-1"></div>
                <a href="{{ route('admin.ad-zones.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>Manage Ad Zones</a>
                <a href="{{ route('admin.ads.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01"/><path d="M15 9h.01"/></svg>Manage Ads</a>
                <a href="{{ route('admin.articles.posts.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v16a2 2 0 0 0-2 2Z"/><path d="M15 2h-5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z"/><path d="M8 8h2"/><path d="M8 12h2"/><path d="M8 16h2"/></svg>Articles Management</a>
            @endrole
            <a href="{{ route('articles.my') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v16a2 2 0 0 0-2 2Z"/><path d="M15 2h-5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z"/><path d="M8 8h2"/><path d="M8 12h2"/><path d="M8 16h2"/></svg>My Articles</a>
            <a href="{{ url('/my-products') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>My Products</a>
            <div class="border-t border-gray-200 my-1"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Log Out
                </a>
            </form>
            @endauth
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trigger = document.getElementById('userDropdownTrigger');
            const menu = document.getElementById('userDropdownMenu');
            const container = document.getElementById('userDropdownContainer');

            function openMenu() {
                menu.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
            }

            function closeMenu() {
                menu.classList.add('hidden');
                trigger.setAttribute('aria-expanded', 'false');
            }

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = !menu.classList.contains('hidden');
                isOpen ? closeMenu() : openMenu();
            });

            document.addEventListener('click', function (e) {
                if (!container.contains(e.target) && !menu.contains(e.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeMenu();
                }
            });
        });
    </script>
</div>
