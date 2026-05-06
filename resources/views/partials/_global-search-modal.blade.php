<div x-show="searchModalOpen" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[100] overflow-y-auto bg-slate-950/45 px-4 py-6 sm:px-6 md:py-16"
    @click.self="closeSearchModal()" @keydown.escape.window="closeSearchModal()" x-cloak
    x-init="$watch('searchModalOpen', open => { const searchInput = document.getElementById('globalSearchInput'); if (open) { $nextTick(() => { if (searchInput) { searchInput.focus(); } }); } else if (searchInput) { searchInput.blur(); } })">
    <div class="mx-auto w-full max-w-5xl">
        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-2xl">
            <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5 sm:px-8">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Discover Faster</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900">Search products and categories</h2>
                        <p class="mt-1 max-w-2xl text-xs leading-5 text-gray-600">Type to search instantly, or jump into the most popular spaces and products right now.</p>
                    </div>
                    <button type="button" @click="closeSearchModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="relative mt-5">
                    <label for="globalSearchInput" class="sr-only">Search products and categories</label>
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input type="text" x-model="searchTerm" @input.debounce.150ms="performSearch(searchTerm)" id="globalSearchInput"
                        placeholder="Search by product name, tagline, description, or category..."
                        class="w-full rounded-2xl border border-gray-200 bg-white px-12 py-3 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <button x-show="searchTerm.length > 0" type="button" @click="searchTerm = ''; performSearch('')"
                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 transition hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[16rem_minmax(0,1fr)]">
                <div class="border-b border-gray-100 bg-slate-50/70 p-4 lg:border-b-0 lg:border-r lg:p-5">
                    <div class="mb-4">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400" x-text="showDefaultSearchContent() ? 'Popular Categories' : 'Matching Categories'"></p>
                        <h3 class="mt-1 text-sm font-semibold text-gray-900" x-text="showDefaultSearchContent() ? 'Browse leading spaces' : 'Category results'"></h3>
                    </div>

                    <div class="space-y-2" x-show="showDefaultSearchContent()">
                        <template x-for="category in popularSearchContent.categories" :key="`popular-category-${category.id}`">
                            <a :href="category.url" class="flex items-start justify-between gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-left transition hover:border-gray-300 hover:bg-white/90">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900" x-text="category.name"></span>
                                    <span class="mt-1 block text-[11px] text-gray-500" x-text="`${category.products_count} products`"></span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </template>
                    </div>

                    <div class="space-y-2" x-show="!showDefaultSearchContent() && searchResults.categories.length > 0">
                        <template x-for="category in searchResults.categories" :key="`search-category-${category.id}`">
                            <a :href="category.url" class="flex items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-left transition hover:border-gray-300 hover:bg-white/90">
                                <span class="truncate text-sm font-semibold text-gray-900" x-text="category.name"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </template>
                    </div>

                    <div x-show="!showDefaultSearchContent() && !searchLoading && searchTerm.trim().length >= 2 && searchResults.categories.length === 0"
                        class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-5 text-center text-sm text-gray-500">
                        No categories matched.
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-gray-400" x-text="showDefaultSearchContent() ? 'Popular Products' : 'Matching Products'"></p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900" x-text="showDefaultSearchContent() ? 'Explore what is trending' : 'Relevant product results'"></h3>
                            <p class="mt-2 text-xs leading-5 text-gray-600" x-show="showDefaultSearchContent()">These picks are surfaced from the most active approved products on the site.</p>
                        </div>
                        <div x-show="searchLoading" class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-500">
                            Searching...
                        </div>
                    </div>

                    <div x-show="showDefaultSearchContent()" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <template x-for="product in popularSearchContent.products" :key="`popular-product-${product.id}`">
                            <a :href="product.url" class="flex items-start gap-3 rounded-2xl border border-gray-200 px-4 py-4 text-left transition hover:border-gray-300 hover:bg-slate-50">
                                <template x-if="product.logo_url">
                                    <img :src="product.logo_url" :alt="product.name" class="h-11 w-11 rounded-xl object-cover">
                                </template>
                                <template x-if="!product.logo_url">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-sm font-semibold text-gray-500" x-text="product.name.charAt(0)"></div>
                                </template>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900" x-text="product.name"></span>
                                    <span class="mt-1 block line-clamp-2 text-xs leading-5 text-gray-500" x-text="product.tagline || 'Explore this product'"></span>
                                    <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-gray-600" x-text="`${product.votes_count} votes`"></span>
                                </span>
                            </a>
                        </template>
                    </div>

                    <div x-show="!showDefaultSearchContent() && !searchLoading && searchResults.products.length > 0"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <template x-for="product in searchResults.products" :key="`search-product-${product.id}`">
                            <a :href="product.url" class="flex items-start gap-3 rounded-2xl border border-gray-200 px-4 py-4 text-left transition hover:border-gray-300 hover:bg-slate-50">
                                <template x-if="product.logo_url">
                                    <img :src="product.logo_url" :alt="product.name" class="h-11 w-11 rounded-xl object-cover">
                                </template>
                                <template x-if="!product.logo_url">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-sm font-semibold text-gray-500" x-text="product.name.charAt(0)"></div>
                                </template>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900" x-text="product.name"></span>
                                    <span class="mt-1 block line-clamp-2 text-xs leading-5 text-gray-500" x-text="product.tagline || 'View product details'"></span>
                                </span>
                            </a>
                        </template>
                    </div>

                    <div x-show="!showDefaultSearchContent() && !searchLoading && searchTerm.trim().length >= 2 && !hasSearchResults()"
                        class="rounded-2xl border border-dashed border-gray-300 bg-slate-50 px-5 py-10 text-center">
                        <p class="text-sm font-medium text-gray-900">No matching products or categories found.</p>
                        <p class="mt-2 text-sm text-gray-600">Try a broader keyword or browse the popular picks on the left.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
