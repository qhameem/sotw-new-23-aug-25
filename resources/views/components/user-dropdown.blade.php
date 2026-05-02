<div x-data="{ open: false, adminSection: null, toggleAdminSection(section) { this.adminSection = this.adminSection === section ? null : section } }" class="relative flex items-center ms-auto sm:ms-2 z-50">
    @auth
    <button type="button" @click.stop="open = !open"
        class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
        aria-haspopup="true" :aria-expanded="open ? 'true' : 'false'">
            @if (Auth::user()->google_avatar)
                <img src="{{ Auth::user()->google_avatar }}" alt="{{ Auth::user()->name }}"
                    class="h-8 w-8 rounded-full object-cover" />
            @else
                <span
                    class="flex items-center justify-center h-8 w-8 rounded-full bg-primary-500 text-white text-xs font-semibold">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
            @endif
    </button>
    @else
    <button
        type="button"
        @click.prevent="$dispatch('open-modal', { name: 'login-required-modal' })"
        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900"
        aria-label="Log in"
        title="Log in"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3" />
            <path d="M10 17l5-5-5-5" />
            <path d="M15 12H4" />
        </svg>
    </button>
    @endauth

    <!-- Sliding drawer-style dropdown -->
    @auth
    <div x-show="open" x-transition.opacity.duration.150ms @click.outside="open = false"
        @keydown.escape.window="open = false"
        class="absolute top-full right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
        role="menu" aria-orientation="vertical" tabindex="-1" style="display: none;">

        <div class="py-1">
            @auth
            <div class="px-4 py-3">
                <a href="{{ route('profile.edit') }}"
                    class="block text-sm font-semibold text-gray-900 hover:text-primary-500">
                    <div class="flex items-center">
                        <span>{{ Auth::user()->name }}</span>
                        @if(Auth::user()->hasRole('admin'))
                            <x-phosphor-shield-check class="ml-1 h-4 w-4 text-primary-500" />
                        @endif
                    </div>
                </a>
            </div>
            <div class="border-t border-gray-200 my-1"></div>
            @role('admin')
            <div class="px-2 py-1">
                <button type="button" @click="toggleAdminSection('moderation')"
                    class="flex w-full items-center justify-between rounded-md px-2 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <span class="inline-flex items-center">
                        <x-phosphor-seal-check class="mr-2 h-4 w-4 text-gray-400" />
                        Moderation
                    </span>
                    <x-phosphor-caret-down x-show="adminSection === 'moderation'" class="h-4 w-4 text-gray-400" />
                    <x-phosphor-caret-right x-show="adminSection !== 'moderation'" class="h-4 w-4 text-gray-400" />
                </button>
                <div x-show="adminSection === 'moderation'" x-transition.opacity.duration.150ms class="pb-1">
                    <a href="{{ route('admin.product-approvals.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-seal-check class="mr-2 h-4 w-4 text-gray-400" />Product Approvals</a>
                    <a href="{{ route('admin.products.pending-edits.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-pencil-simple class="mr-2 h-4 w-4 text-gray-400" />Pending Edits</a>
                    <a href="{{ route('admin.product-reviews.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-clipboard-text class="mr-2 h-4 w-4 text-gray-400" />Product Reviews</a>
                </div>
                <button type="button" @click="toggleAdminSection('catalog')"
                    class="flex w-full items-center justify-between rounded-md px-2 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <span class="inline-flex items-center">
                        <x-phosphor-package class="mr-2 h-4 w-4 text-gray-400" />
                        Catalog
                    </span>
                    <x-phosphor-caret-down x-show="adminSection === 'catalog'" class="h-4 w-4 text-gray-400" />
                    <x-phosphor-caret-right x-show="adminSection !== 'catalog'" class="h-4 w-4 text-gray-400" />
                </button>
                <div x-show="adminSection === 'catalog'" x-transition.opacity.duration.150ms class="pb-1">
                    <a href="{{ route('admin.categories.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-tag class="mr-2 h-4 w-4 text-gray-400" />Categories</a>
                    <a href="{{ route('admin.products.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-package class="mr-2 h-4 w-4 text-gray-400" />Products</a>
                    <a href="{{ route('admin.tech-stacks.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-stack class="mr-2 h-4 w-4 text-gray-400" />Tech Stacks</a>
                    <a href="{{ route('admin.premium-products.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-medal class="mr-2 h-4 w-4 text-gray-400" />Premium Products</a>
                    <a href="{{ route('admin.products.assign.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-user-plus class="mr-2 h-4 w-4 text-gray-400" />Assign Product</a>
                </div>
                <button type="button" @click="toggleAdminSection('content')"
                    class="flex w-full items-center justify-between rounded-md px-2 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <span class="inline-flex items-center">
                        <x-phosphor-file-text class="mr-2 h-4 w-4 text-gray-400" />
                        Content
                    </span>
                    <x-phosphor-caret-down x-show="adminSection === 'content'" class="h-4 w-4 text-gray-400" />
                    <x-phosphor-caret-right x-show="adminSection !== 'content'" class="h-4 w-4 text-gray-400" />
                </button>
                <div x-show="adminSection === 'content'" x-transition.opacity.duration.150ms class="pb-1">
                    <a href="{{ route('admin.articles.posts.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-file-text class="mr-2 h-4 w-4 text-gray-400" />Articles</a>
                    <a href="{{ route('admin.changelogs.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-scroll class="mr-2 h-4 w-4 text-gray-400" />Changelog</a>
                    <a href="{{ route('admin.advertising.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-megaphone class="mr-2 h-4 w-4 text-gray-400" />Advertising</a>
                    <a href="{{ route('admin.seo.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-browser class="mr-2 h-4 w-4 text-gray-400" />SEO</a>
                </div>
                <button type="button" @click="toggleAdminSection('system')"
                    class="flex w-full items-center justify-between rounded-md px-2 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <span class="inline-flex items-center">
                        <x-phosphor-gear-six class="mr-2 h-4 w-4 text-gray-400" />
                        System
                    </span>
                    <x-phosphor-caret-down x-show="adminSection === 'system'" class="h-4 w-4 text-gray-400" />
                    <x-phosphor-caret-right x-show="adminSection !== 'system'" class="h-4 w-4 text-gray-400" />
                </button>
                <div x-show="adminSection === 'system'" x-transition.opacity.duration.150ms class="pb-1">
                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-users class="mr-2 h-4 w-4 text-gray-400" />Manage Users</a>
                    <a href="{{ route('admin.theme.edit') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-palette class="mr-2 h-4 w-4 text-gray-400" />Theme Settings</a>
                    <a href="{{ route('admin.settings.index') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-gear-six class="mr-2 h-4 w-4 text-gray-400" />Settings</a>
                    <a href="{{ route('admin.settings.screenshotProviders') }}"
                        class="flex items-center rounded-md px-8 py-1.5 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-browser class="mr-2 h-4 w-4 text-gray-400" />Screenshot Debug</a>
                </div>
            </div>
            <div class="border-t border-gray-200 my-1"></div>
            @endrole
            <a href="{{ route('articles.my') }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-file-text class="mr-2 h-5 w-5 text-gray-400" />My Articles</a>
            <a href="{{ url('/my-products') }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><x-phosphor-package class="mr-2 h-5 w-5 text-gray-400" />My Products</a>
            <div class="border-t border-gray-200 my-1"></div>
            <form method="POST" action="{{ route('logout') }}" x-data="{ loggingOut: false }" @submit="loggingOut = true">
                @csrf
                <button type="submit"
                    class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                    <template x-if="loggingOut">
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                                <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                            </svg>
                            Logging out...
                        </span>
                    </template>
                    <template x-if="!loggingOut">
                        <span class="inline-flex items-center">
                            <x-phosphor-sign-out class="mr-2 h-5 w-5 text-gray-400" />
                            Log Out
                        </span>
                    </template>
                </button>
            </form>
            @endauth
        </div>
    </div>
    @endauth

</div>
