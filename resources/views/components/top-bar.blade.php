<div data-modal-scroll-lock-fixed class="fixed top-0 w-full z-50 h-[3.7rem] border-b border-gray-200 flex-shrink-0 hidden md:block" style="background-color: var(--color-navbar-bg, #ffffff);">
    @php
        $isCategoriesRoute = request()->routeIs('categories.*');
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <div class="flex items-center">
                <a href="{{ route('home') }}">
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800 " />
                </a>
            </div>
        <div class="flex items-center space-x-4">
            <div
                x-data="{
                    open: false,
                    closeTimer: null,
                    activeGroup: '{{ $defaultCategoryNavigationGroupKey ?? 'ai-automation' }}',
                    groups: {{ \Illuminate\Support\Js::from($categoryNavigationGroups ?? []) }},
                    clearCloseTimer() {
                        if (this.closeTimer) {
                            clearTimeout(this.closeTimer);
                            this.closeTimer = null;
                        }
                    },
                    openMenu() {
                        this.clearCloseTimer();
                        this.open = true;
                    },
                    scheduleClose() {
                        this.clearCloseTimer();
                        this.closeTimer = setTimeout(() => {
                            this.open = false;
                        }, 180);
                    },
                    setGroup(key) {
                        if (this.activeGroup !== key) {
                            this.activeGroup = key;
                        }

                        this.openMenu();
                    },
                    get activeGroupData() {
                        return this.groups.find((group) => group.key === this.activeGroup) ?? this.groups[0] ?? { items: [] };
                    },
                }"
                class="relative"
                @click.outside="open = false; clearCloseTimer()"
                @keydown.escape.window="open = false; clearCloseTimer()"
            >
                <button
                    x-ref="trigger"
                    type="button"
                    class="text-sm flex items-center gap-1.5 transition-colors"
                    @mouseenter="openMenu()"
                    @mouseleave="scheduleClose()"
                    @focus="openMenu()"
                    @click="open ? open = false : openMenu()"
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
                    @mouseenter="openMenu()"
                    @mouseleave="scheduleClose()"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="fixed left-1/2 top-[3.5rem] z-50 w-[58rem] max-w-[calc(100vw-3rem)] -translate-x-1/2 pt-3"
                    style="display: none;"
                >
                    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-2xl">
                        <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5">
                            <div class="flex items-start justify-between gap-6">
                                <div>
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Browse By Goal</p>
                                    <h2 class="mt-1 text-base font-semibold text-gray-900">Find the right category faster</h2>
                                    <p class="mt-1 max-w-none whitespace-nowrap text-[11px] text-gray-600">Explore grouped software categories on the left, then jump straight into the sub-categories on the right.</p>
                                </div>
                                <a href="{{ route('categories.index') }}" class="inline-flex shrink-0 items-center rounded-full border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900">
                                    View all categories
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-[15rem_minmax(0,1fr)]">
                            <div class="border-r border-gray-100 bg-slate-50/70 p-3">
                                @foreach (($categoryNavigationGroups ?? []) as $group)
                                    <button
                                        type="button"
                                        class="flex w-full items-start justify-between gap-3 rounded-2xl px-4 py-3 text-left transition"
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
                                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400" x-text="activeGroupData.eyebrow"></p>
                                        <h3 class="mt-1 text-lg font-semibold text-gray-900" x-text="activeGroupData.label"></h3>
                                        <p class="mt-2 max-w-2xl text-xs leading-5 text-gray-600" x-text="activeGroupData.description"></p>
                                    </div>
                                </div>

                                <template x-if="activeGroupData.items && activeGroupData.items.length">
                                    <div class="max-h-[24rem] overflow-y-auto pr-2">
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                            <template x-for="item in activeGroupData.items" :key="item.slug">
                                                <a
                                                    :href="item.url"
                                                    class="flex items-start justify-between rounded-2xl border border-gray-200 px-4 py-3 text-xs transition hover:border-gray-300 hover:bg-slate-50"
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

                                <template x-if="!activeGroupData.items || !activeGroupData.items.length">
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-slate-50 px-5 py-8 text-center">
                                        <p class="text-sm font-medium text-gray-900">No categories are in this section yet.</p>
                                        <p class="mt-2 text-sm text-gray-600">Use the alphabetical directory to browse everything that is available right now.</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
