<div data-modal-scroll-lock-fixed class="fixed top-0 w-full z-50 h-[3.7rem] border-b border-gray-200 flex-shrink-0 hidden md:block" style="background-color: var(--color-navbar-bg, #ffffff);">
    @php
        $isCategoriesRoute = request()->routeIs('categories.*');
        $launchReadinessBranding = app(\App\Support\LaunchReadinessBranding::class);
        $launchReadinessToolSettings = app(\App\Support\ToolSettings::class);
        $launchReadinessToolName = $launchReadinessBranding->siteName();
        $launchReadinessToolLogoUrl = $launchReadinessBranding->publicLogoUrl();
        $launchReadinessToolUrl = $launchReadinessToolSettings->path(\App\Support\ToolSettings::LAUNCH_READINESS_KEY);
        $launchReadinessTopBarLabel = 'Free Launch Audit';
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 xl:px-12">
        <div class="flex items-center gap-4 lg:gap-6 h-14">
            <div class="flex min-w-0 shrink-0 items-center gap-4 lg:gap-5">
                <a href="{{ route('home') }}" wire:navigate.hover class="shrink-0" aria-label="{{ config('app.name', 'Software on the Web') }} home">
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800 " />
                    <span class="sr-only">{{ config('app.name', 'Software on the Web') }} home</span>
                </a>
                @if(!request()->routeIs('todolists.*'))
                    <div class="w-[120px] lg:w-[160px] xl:w-[200px] shrink-0">
                        <button type="button" @click="$dispatch('open-search-modal')"
                            x-data="{ isMac: /Mac|iPhone|iPad|iPod/.test(navigator.platform) || /Mac|iPhone|iPad|iPod/.test(navigator.userAgent) }"
                            aria-keyshortcuts="Meta+K Control+K"
                            x-bind:title="isMac ? 'Open search (Cmd + K)' : 'Open search (Ctrl + K)'"
                            class="flex w-full items-center gap-3 rounded-md bg-gray-100 px-3 py-1 text-left transition hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z" />
                            </svg>
                            <span class="truncate text-sm text-gray-500" x-text="isMac ? 'Search (⌘ + K)' : 'Search (Ctrl + K)'"></span>
                        </button>
                    </div>
                    <a
                        href="{{ $launchReadinessToolUrl }}"
                        class="group flex min-w-0 shrink items-center gap-1 px-1 py-1 transition hover:opacity-80"
                        title="{{ $launchReadinessToolName }}"
                    >
                        <img
                            src="{{ $launchReadinessToolLogoUrl }}"
                            alt="{{ $launchReadinessToolName }} logo"
                            class="h-5 w-5 shrink-0 object-contain"
                            loading="lazy"
                        >
                        <span class="max-w-[140px] truncate text-sm font-semibold text-gray-800 group-hover:underline">{{ $launchReadinessTopBarLabel }}</span>
                    </a>
                @endif
            </div>
            <div class="flex min-w-0 flex-1 justify-center px-2 lg:px-6">
                <div class="flex items-center gap-6 lg:gap-9">
                    <div
                        x-data="{
                            open: false,
                            activeGroup: '{{ $defaultCategoryNavigationGroupKey ?? 'ai-automation' }}',
                            searchQuery: '',
                            summaries: {{ \Illuminate\Support\Js::from($categoryNavigationSummaries ?? []) }},
                            groups: [],
                            groupsLoaded: false,
                            groupsLoading: false,
                            openMenu() {
                                this.open = true;
                                this.loadGroups();
                                this.$nextTick(() => this.$refs.searchInput?.focus());
                            },
                            closeMenu() {
                                this.open = false;
                                this.searchQuery = '';
                            },
                            toggleMenu() {
                                if (this.open) {
                                    this.closeMenu();
                                    return;
                                }

                                this.openMenu();
                            },
                            setGroup(key) {
                                if (this.activeGroup !== key) {
                                    this.activeGroup = key;
                                }

                                this.loadGroups();
                            },
                            async loadGroups() {
                                if (this.groupsLoaded || this.groupsLoading) {
                                    return;
                                }

                                this.groupsLoading = true;

                                try {
                                    const response = await fetch('/api/navigation/categories');
                                    if (!response.ok) {
                                        throw new Error('Failed to load category navigation.');
                                    }

                                    const data = await response.json();
                                    this.groups = Array.isArray(data.groups) ? data.groups : [];
                                    this.groupsLoaded = true;

                                    if (!this.activeGroup && data.default_group_key) {
                                        this.activeGroup = data.default_group_key;
                                    }
                                } catch (error) {
                                    console.error('Category navigation error:', error);
                                    this.groups = [];
                                } finally {
                                    this.groupsLoading = false;
                                }
                            },
                            get activeGroupData() {
                                return this.groups.find((group) => group.key === this.activeGroup)
                                    ?? this.summaries.find((group) => group.key === this.activeGroup)
                                    ?? this.summaries[0]
                                    ?? { items: [] };
                            },
                            get isSearching() {
                                return this.searchQuery.trim().length > 0;
                            },
                            get searchableItems() {
                                return this.groups.flatMap((group) => {
                                    const items = Array.isArray(group.items) ? group.items : [];

                                    return items.map((item) => ({
                                        ...item,
                                        groupKey: group.key,
                                        groupLabel: group.label,
                                    }));
                                });
                            },
                            get filteredSearchItems() {
                                const query = this.searchQuery.trim().toLowerCase();

                                if (!query) {
                                    return [];
                                }

                                return this.searchableItems.filter((item) => item.name.toLowerCase().includes(query));
                            },
                        }"
                        class="relative"
                        @click.outside="closeMenu()"
                        @keydown.escape.window="closeMenu()"
                    >
                        <button
                            x-ref="trigger"
                            type="button"
                            class="flex items-center gap-1.5 text-sm font-semibold transition-colors"
                            @click="toggleMenu()"
                            :aria-expanded="open.toString()"
                            aria-haspopup="true"
                        >
                            <span class="{{ $isCategoriesRoute ? 'text-primary-600 font-semibold' : 'text-gray-900 hover:text-primary-500' }}">Categories</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-ref="panel"
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            class="fixed left-1/2 top-[3.5rem] z-50 w-[58rem] max-w-[calc(100vw-3rem)] -translate-x-1/2 pt-3"
                            style="display: none;"
                        >
                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
                                <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5">
                                    <div class="flex items-start justify-between gap-6">
                                        <div>
                                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Browse By Goal</p>
                                            <div class="mt-1 text-base font-semibold text-gray-900">Find the right category faster</div>
                                            <p class="mt-1 max-w-none whitespace-nowrap text-[11px] text-gray-600">Explore grouped software categories on the left, then jump straight into the sub-categories on the right.</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <a href="{{ route('categories.index') }}" wire:navigate.hover class="inline-flex items-center rounded-full border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900">
                                                View all categories
                                            </a>
                                            <button
                                                type="button"
                                                @click="closeMenu()"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                                aria-label="Close categories menu"
                                                title="Close"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label for="desktop-category-search" class="sr-only">Search categories</label>
                                        <div class="relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z" />
                                                </svg>
                                            </div>
                                            <input
                                                x-ref="searchInput"
                                                id="desktop-category-search"
                                                type="search"
                                                x-model="searchQuery"
                                                autocomplete="off"
                                                placeholder="Search category name"
                                                class="block w-full rounded-xl border border-gray-200 bg-white py-3 pl-10 pr-10 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                                            >
                                            <button
                                                x-show="searchQuery"
                                                x-cloak
                                                type="button"
                                                @click="searchQuery = ''; $nextTick(() => $refs.searchInput?.focus())"
                                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-700"
                                                aria-label="Clear category search"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-[15rem_minmax(0,1fr)]">
                                    <div class="border-r border-gray-100 bg-slate-50/70 p-3">
                                        @foreach (($categoryNavigationSummaries ?? []) as $group)
                                            <button
                                                type="button"
                                                class="flex w-full items-start justify-between gap-3 rounded-xl px-4 py-3 text-left transition"
                                                @mouseenter="setGroup('{{ $group['key'] }}')"
                                                @focus="setGroup('{{ $group['key'] }}')"
                                                @click="setGroup('{{ $group['key'] }}')"
                                                :class="activeGroup === '{{ $group['key'] }}' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-600 hover:bg-white hover:text-gray-900'"
                                            >
                                                <span class="flex min-w-0 items-start gap-3">
                                                    <span
                                                        class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/80 ring-1 ring-gray-200"
                                                        :class="activeGroup === '{{ $group['key'] }}' ? 'text-primary-600' : 'text-gray-500'"
                                                    >
                                                        @switch($group['icon'])
                                                            @case('brain')
                                                                <x-phosphor-brain class="h-4 w-4" />
                                                                @break
                                                            @case('megaphone')
                                                                <x-phosphor-megaphone class="h-4 w-4" />
                                                                @break
                                                            @case('briefcase')
                                                                <x-phosphor-briefcase class="h-4 w-4" />
                                                                @break
                                                            @case('palette')
                                                                <x-phosphor-palette class="h-4 w-4" />
                                                                @break
                                                            @case('terminal-window')
                                                                <x-phosphor-terminal-window class="h-4 w-4" />
                                                                @break
                                                            @case('bank')
                                                                <x-phosphor-bank class="h-4 w-4" />
                                                                @break
                                                            @case('lifebuoy')
                                                                <x-phosphor-lifebuoy class="h-4 w-4" />
                                                                @break
                                                            @default
                                                                <x-phosphor-grid-nine class="h-4 w-4" />
                                                        @endswitch
                                                    </span>

                                                    <span class="min-w-0">
                                                        <span class="block text-xs font-semibold">{{ $group['label'] }}</span>
                                                        <span class="mt-1 block text-[11px] text-gray-500">
                                                            {{ $group['item_count'] ? $group['item_count'] . ' categories' : 'Browse all categories' }}
                                                        </span>
                                                    </span>
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="p-6">
                                        <div class="mb-5 flex items-start justify-between gap-4">
                                            <div>
                                                <template x-if="!isSearching">
                                                    <div>
                                                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400" x-text="activeGroupData.eyebrow"></p>
                                                        <h3 class="mt-1 text-lg font-semibold text-gray-900" x-text="activeGroupData.label"></h3>
                                                        <p class="mt-2 max-w-2xl text-xs leading-5 text-gray-600" x-text="activeGroupData.description"></p>
                                                    </div>
                                                </template>
                                                <template x-if="isSearching">
                                                    <div>
                                                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Search Results</p>
                                                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Matching categories</h3>
                                                        <p class="mt-2 max-w-2xl text-xs leading-5 text-gray-600">
                                                            Search across all category groups by category name.
                                                        </p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <template x-if="groupsLoading && !groupsLoaded">
                                            <div class="rounded-xl border border-dashed border-gray-300 bg-slate-50 px-5 py-8 text-center">
                                                <p class="text-sm font-medium text-gray-900">Loading categories...</p>
                                            </div>
                                        </template>

                                        <template x-if="groupsLoaded && !isSearching && activeGroupData.items && activeGroupData.items.length">
                                            <div class="max-h-[24rem] overflow-y-auto pr-2">
                                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                    <template x-for="item in activeGroupData.items" :key="item.slug">
                                                        <a
                                                            :href="item.url"
                                                            wire:navigate.hover
                                                            class="flex items-start justify-between rounded-xl border border-gray-200 px-4 py-3 text-xs transition hover:border-gray-300 hover:bg-slate-50"
                                                        >
                                                            <span class="pr-3">
                                                                <span class="block font-semibold text-sm text-gray-900" x-text="item.name"></span>
                                                                <span class="mt-1 flex items-center gap-2 text-[11px] text-gray-500">
                                                                    <span x-text="`${item.count} products`"></span>
                                                                    <template x-if="item.type_label">
                                                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600" x-text="item.type_label"></span>
                                                                    </template>
                                                                </span>
                                                            </span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="groupsLoaded && isSearching && filteredSearchItems.length">
                                            <div class="max-h-[24rem] overflow-y-auto pr-2">
                                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                    <template x-for="item in filteredSearchItems" :key="`${item.groupKey}-${item.slug}`">
                                                        <a
                                                            :href="item.url"
                                                            wire:navigate.hover
                                                            class="flex items-start justify-between rounded-xl border border-gray-200 px-4 py-3 text-xs transition hover:border-gray-300 hover:bg-slate-50"
                                                        >
                                                            <span class="pr-3">
                                                                <span class="block font-semibold text-sm text-gray-900" x-text="item.name"></span>
                                                                <span class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600" x-text="item.groupLabel"></span>
                                                                    <span x-text="`${item.count} products`"></span>
                                                                    <template x-if="item.type_label">
                                                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600" x-text="item.type_label"></span>
                                                                    </template>
                                                                </span>
                                                            </span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="groupsLoaded && !isSearching && (!activeGroupData.items || !activeGroupData.items.length)">
                                            <div class="rounded-xl border border-dashed border-gray-300 bg-slate-50 px-5 py-8 text-center">
                                                <p class="text-sm font-medium text-gray-900">No categories are in this section yet.</p>
                                                <p class="mt-2 text-sm text-gray-600">Use the alphabetical directory to browse everything that is available right now.</p>
                                            </div>
                                        </template>

                                        <template x-if="groupsLoaded && isSearching && !filteredSearchItems.length">
                                            <div class="rounded-xl border border-dashed border-gray-300 bg-slate-50 px-5 py-8 text-center">
                                                <p class="text-sm font-medium text-gray-900">No matching categories found.</p>
                                                <p class="mt-2 text-sm text-gray-600">Try a different category name.</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('articles.index') }}" wire:navigate.hover class="text-sm font-semibold text-gray-900 transition-colors hover:text-primary-500">Articles</a>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-4">
                @guest
                    <div class="flex items-center gap-2">
                        <x-add-product-button />
                        <a href="{{ route('login') }}" class="inline-flex min-h-8 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-gray-300 bg-white px-4 py-1 text-sm font-semibold text-gray-700 transition duration-300 hover:border-gray-400 hover:bg-gray-50 hover:text-gray-900">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
                                <path fill-rule="evenodd" d="M17 4.25A2.25 2.25 0 0 0 14.75 2h-5.5A2.25 2.25 0 0 0 7 4.25v2a.75.75 0 0 0 1.5 0v-2a.75.75 0 0 1 .75-.75h5.5a.75.75 0 0 1 .75.75v11.5a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1-.75-.75v-2a.75.75 0 0 0-1.5 0v2A2.25 2.25 0 0 0 9.25 18h5.5A2.25 2.25 0 0 0 17 15.75V4.25Z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M1 10a.75.75 0 0 1 .75-.75h9.546l-1.048-.943a.75.75 0 1 1 1.004-1.114l2.5 2.25a.75.75 0 0 1 0 1.114l-2.5 2.25a.75.75 0 1 1-1.004-1.114l1.048-.943H1.75A.75.75 0 0 1 1 10Z" clip-rule="evenodd" />
                            </svg>
                            <span>Sign in</span>
                        </a>
                    </div>
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
                    <div class="mr-2">
                        @auth
                        <div id="notification-bell-app">
                            <notification-bell :user-id="{{ Auth::id() }}"></notification-bell>
                        </div>
                        @endauth
                    </div>
                    @auth
                        @if (Auth::user()->hasRole('admin'))
                            <a href="{{ route('admin.product-approvals.index') }}"
                                class="mr-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-medium transition
                                    {{ ($pendingApprovalCount ?? 0) > 0
                                        ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100'
                                        : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                                <x-phosphor-seal-check class="h-4 w-4" />
                                <span>{{ $pendingApprovalCount ?? 0 }}</span>
                                <span class="hidden lg:inline">Pending</span>
                            </a>
                        @endif
                    @endauth
                    <x-user-dropdown />
                </div>
                @endguest
            </div>
        </div>
    </div>
</div>
