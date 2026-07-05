<div class="flex flex-col md:w-sm" x-data="{ searchFocused: false }"
    @search-focus-changed.window="searchFocused = $event.detail">
    @if(!request()->routeIs('todolists.*'))
        <div x-show="!searchFocused">
            @if(in_array(Route::currentRouteName(), ['home', 'products.byDate', 'products.byWeek', 'categories.show', 'products.search']))
                @include('partials._sidebar-ads')

                <x-top-categories />

                @guest
                    <div class="p-4">
                        @include('partials._what-is-sotw-card', ['compact' => request()->routeIs('categories.show')])
                    </div>
                @endguest
            @elseif(request()->is('articles*'))
                <div class="p-4">
                    @if(isset($staffPicks) && $staffPicks->isNotEmpty())
                        <h3 class="text-sm font-semibold mb-4 text-gray-800">Staff Picks</h3>
                        <ul class="space-y-4">
                            @foreach($staffPicks as $pick)
                                <li>
                                    <div class="flex items-center mb-2">
                                        @if($pick->author->hasRole('admin'))
                                            @php
                                                $faviconUrl = config('theme.favicon_url') ? Illuminate\Support\Facades\Storage::url(config('theme.favicon_url')) : asset('favicon/favicon.ico');
                                            @endphp
                                            <img src="{{ $faviconUrl }}" alt="{{ config('app.name', 'Software on the Web') }}"
                                                class="h-6 w-6 rounded-full object-cover mr-2">
                                            <span class="text-sm text-gray-600">
                                                In {{ $pick->categories->first()->name ?? 'Uncategorized' }} by {{ config('app.name', 'Software on the Web') }}
                                            </span>
                                        @else
                                            <img src="{{ $pick->author->google_avatar ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($pick->author->email ?? ''))) . '?d=mp' }}"
                                                alt="{{ $pick->author->name ?? 'Author' }}" class="h-6 w-6 rounded-full object-cover mr-2">
                                            <span class="text-sm text-gray-600">
                                                In {{ $pick->categories->first()->name ?? 'Uncategorized' }} by
                                                {{ $pick->author->name ?? 'Unknown' }}
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('articles.show', $pick->slug) }}"
                                        class="text-sm font-semibold text-gray-900 hover:text-primary-500">
                                        {{ $pick->title }}
                                    </a>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span>{{ $pick->published_at->format('M d') }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @elseif (View::hasSection('right_sidebar_content'))
                <div class="sticky top-6">
                    @yield('right_sidebar_content')
                </div>
            @endif

            @if (isset($scheduledProductsStats) && Route::currentRouteName() == 'admin.product-approvals.index')
                <div class="p-4">
                    <h3 class="text-base font-medium mb-2">Scheduled Products</h3>
                    @if(!$scheduledProductsStats->isEmpty())
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Date</th>
                                    <th class="text-right py-2">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduledProductsStats as $stat)
                                    <tr class="border-b">
                                        <td class="py-2">{{ \Carbon\Carbon::parse($stat->date)->format('d M, Y') }}</td>
                                        <td class="text-right py-2">{{ $stat->count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-500">No products are currently scheduled.</p>
                    @endif
                </div>
            @endif
        </div>
    @endif
    <div x-show="searchFocused" style="display: none;" class="p-4 text-gray-500" x-data="{ results: null }"
        @search-results.window="results = $event.detail">
        <div x-show="!results" class="text-center">
            Try searching for software products or categories
        </div>
        <template x-if="results">
            <div>
                <div x-show="results.products.length > 0">
                    <h3 class="text-sm font-semibold mb-2 text-gray-800">Products</h3>
                    <ul class="space-y-2">
                        <template x-for="product in results.products" :key="product.id">
                            <li>
                                <a :href="`/product/${product.slug}`"
                                    class="flex items-center p-2 rounded-lg hover:bg-gray-100 cursor-pointer">
                                    <template x-if="product.logo_url">
                                        <img :src="product.logo_url" :alt="product.name"
                                            x-on:error="if (product.fallback_logo_url && $el.src !== product.fallback_logo_url) { $el.src = product.fallback_logo_url } else { product.logo_url = null }"
                                            class="w-8 h-8 mr-3 rounded-xl object-cover">
                                    </template>
                                    <template x-if="!product.logo_url">
                                        <div
                                            class="w-8 h-8 mr-3 rounded-xl bg-gray-200 flex items-center justify-center">
                                            <span class="text-xs font-semibold text-gray-500"
                                                x-text="product.name.substring(0, 1)"></span>
                                        </div>
                                    </template>
                                    <div>
                                        <div class="font-semibold text-gray-900" x-text="product.name"></div>
                                        <div class="text-xs text-gray-500" x-text="product.tagline"></div>
                                    </div>
                </div>
                </li>
        </template>
        </ul>
    </div>
    <div x-show="results.categories.length > 0" class="mt-4">
        <h3 class="text-sm font-semibold mb-2 text-gray-800">Categories</h3>
        <ul class="space-y-2">
            <template x-for="category in results.categories" :key="category.id">
                <li>
                    <a :href="`/category/${category.slug}`" class="flex items-center p-2 rounded-lg hover:bg-gray-100">
                        <span class="font-semibold text-gray-900" x-text="category.name"></span>
                    </a>
                </li>
            </template>
        </ul>
    </div>
    <div x-show="results.products.length === 0 && results.categories.length === 0" class="text-center">
        No results found.
    </div>
</div>
</template>
</div>
</div>
