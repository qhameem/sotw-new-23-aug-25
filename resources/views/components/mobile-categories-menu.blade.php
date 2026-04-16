<div
    x-data="{
        open: false,
        screen: 'groups',
        activeGroup: '{{ $defaultCategoryNavigationGroupKey ?? 'ai-automation' }}',
        groups: {{ \Illuminate\Support\Js::from($categoryNavigationGroups ?? []) }},
        openMenu() {
            this.open = true;
            this.screen = 'groups';
            this.activeGroup = '{{ $defaultCategoryNavigationGroupKey ?? 'ai-automation' }}';
        },
        closeMenu() {
            this.open = false;
            this.screen = 'groups';
        },
        showGroup(key) {
            this.activeGroup = key;
            this.screen = 'items';
        },
        get activeGroupData() {
            return this.groups.find((group) => group.key === this.activeGroup) ?? this.groups[0] ?? { items: [] };
        },
    }"
    x-init="$watch('open', value => {
        document.documentElement.classList.toggle('overflow-hidden', value);
        document.body.classList.toggle('overflow-hidden', value);
    })"
    @open-categories-menu.window="openMenu()"
    @close-categories-menu.window="closeMenu()"
    @keydown.escape.window="if (open) closeMenu()"
>
    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[110] bg-gray-900/40 md:hidden"
        style="display: none;"
        @click="if ($event.target === $el) closeMenu()"
    >
        <div class="absolute inset-0 overflow-hidden bg-white">
            <div class="flex h-full w-[200%] transition-transform duration-300 ease-out" :style="screen === 'groups' ? 'transform: translateX(0);' : 'transform: translateX(-50%);'">
                <section class="w-1/2 shrink-0 overflow-y-auto">
                    <div class="flex min-h-full flex-col">
                        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4">
                            <div>
                                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Categories</p>
                                <h2 class="mt-1 text-lg font-semibold text-gray-900">Browse by goal</h2>
                            </div>
                            <button type="button" @click="closeMenu()" class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-4 py-4">
                            <p class="rounded-2xl bg-slate-50 px-4 py-3 text-sm leading-6 text-gray-600">Start with a parent category, then drill into the specific sub-category you want.</p>
                        </div>

                        <div class="flex-1 space-y-2 px-4 pb-24">
                            <template x-for="group in groups" :key="group.key">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border border-gray-200 px-4 py-4 text-left transition hover:border-gray-300 hover:bg-slate-50"
                                    @click="showGroup(group.key)"
                                >
                                    <span class="pr-4">
                                        <span class="block text-sm font-semibold text-gray-900" x-text="group.label"></span>
                                        <span class="mt-1 block text-xs text-gray-500" x-text="group.description"></span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </section>

                <section class="w-1/2 shrink-0 overflow-y-auto border-l border-gray-200">
                    <div class="flex min-h-full flex-col">
                        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4">
                            <div class="flex items-center gap-3">
                                <button type="button" @click="screen = 'groups'" class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <div>
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400" x-text="activeGroupData.eyebrow"></p>
                                    <h2 class="mt-1 text-lg font-semibold text-gray-900" x-text="activeGroupData.label"></h2>
                                </div>
                            </div>
                            <button type="button" @click="closeMenu()" class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-4 py-4">
                            <p class="text-sm leading-6 text-gray-600" x-text="activeGroupData.description"></p>
                        </div>

                        <div class="flex-1 space-y-2 px-4 pb-24">
                            <template x-if="activeGroupData.items && activeGroupData.items.length">
                                <div class="space-y-2">
                                    <template x-for="item in activeGroupData.items" :key="item.slug">
                                        <a
                                            :href="item.url"
                                            class="flex items-start justify-between rounded-2xl border border-gray-200 px-4 py-4 transition hover:border-gray-300 hover:bg-slate-50"
                                            @click="closeMenu()"
                                        >
                                            <span class="pr-4">
                                                <span class="block text-sm font-semibold text-gray-900" x-text="item.name"></span>
                                                <span class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                                    <span x-text="`${item.count} products`"></span>
                                                    <template x-if="item.type_label">
                                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600" x-text="item.type_label"></span>
                                                    </template>
                                                </span>
                                            </span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!activeGroupData.items || !activeGroupData.items.length">
                                <div class="rounded-2xl border border-dashed border-gray-300 bg-slate-50 px-4 py-8 text-center">
                                    <p class="text-sm font-medium text-gray-900">No categories are here yet.</p>
                                    <p class="mt-2 text-sm text-gray-600">Go back and use the alphabetical view to browse everything.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
