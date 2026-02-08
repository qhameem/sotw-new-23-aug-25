<div class="flex flex-col md:w-sm" x-data="{ searchFocused: false }"
    @search-focus-changed.window="searchFocused = $event.detail">
    @if(!request()->is('free-todo-list-tool'))
        <div x-show="!searchFocused">
            @if(in_array(Route::currentRouteName(), ['home', 'products.byDate', 'products.byWeek', 'categories.show', 'products.search']))
                <div class="p-4">
                    <h3 class="text-sm font-medium text-gray-800">{{ now()->year }} Statistics
                        <div class="relative group inline-block">
                            <span class="cursor-default text-gray-900 font-semibold">&#9432;</span>
                            <div
                                class="absolute z-10 hidden group-hover:block mt-1 px-2 py-1 text-xs text-gray-600 rounded border bg-gray-50 whitespace-nowrap">
                                Updated every day.
                            </div>
                        </div>
                    </h3>
                    <div class="mt-2 grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg text-center border">
                            <span id="total-visits" class="text-sm text-gray-700 font-bold">Loading...</span>
                            <p class="text-xs text-gray-600">Visits</p>
                        </div>
                        <div class="p-4 rounded-lg text-center border">
                            <span id="total-pageviews" class="text-sm text-gray-700 font-bold">Loading...</span>
                            <p class="text-xs text-gray-600">Page views</p>
                        </div>
                    </div>
                </div>

                @php
                    $sponsors = cache()->remember('sponsors_sidebar', config('performance.sponsors_cache_ttl', 3600), function () {
                        $sponsorZone = \App\Models\AdZone::where('slug', 'sponsors')->first();
                        return $sponsorZone ? $sponsorZone->ads()->where('is_active', true)->take(config('performance.max_sponsors_display', 6))->get() : collect();
                    });
                @endphp
                @if($sponsors->isNotEmpty())
                    <div class="p-4">
                        <h3 class="text-base font-semibold mb-4 text-gray-70">Our Partners</h3>
                        <ul class="space-y-4">
                            @foreach($sponsors as $sponsor)
                                <li>
                                    <a href="{{ $sponsor->target_url }}" target="_blank" class="flex items-center space-x-3">
                                        <img src="{{ $sponsor->content }}" alt="{{ $sponsor->internal_name }}"
                                            class="w-10 h-10 rounded-xl object-cover">
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ $sponsor->internal_name }} <span
                                                    class="text-gray-400">↗</span></div>
                                            <p class="text-sm text-gray-50">{{ $sponsor->tagline }}</p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <x-top-categories />

                @guest
                    <div class="p-4">
                        @include('partials._what-is-sotw-card')
                    </div>
                @endguest
            @elseif(request()->is('articles*') && !request()->is('promote-your-software'))
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
                                            <img src="{{ $faviconUrl }}" alt="Software on the web"
                                                class="h-6 w-6 rounded-full object-cover mr-2">
                                            <span class="text-sm text-gray-600">
                                                In {{ $pick->categories->first()->name ?? 'Uncategorized' }} by Software on the web
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
                                    class="flex items-center p-2 rounded-lg hover:bg-gray-100 cursor-pointer"
                                    wire:navigate>
                                    <template x-if="product.logo_url">
                                        <img :src="product.logo_url" :alt="product.name"
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toMultiplyWith = 15;
            fetch('/api/analytics/total-sessions')
                .then(response => response.json())
                .then(data => {
                    const totalVisitsElement = document.getElementById('total-visits');
                    const totalPageviewsElement = document.getElementById('total-pageviews');

                    if (totalVisitsElement) {
                        if (data.sessions !== null && data.sessions !== undefined) {
                            totalVisitsElement.textContent = (data.sessions * toMultiplyWith).toLocaleString();
                        } else {
                            totalVisitsElement.textContent = 'N/A';
                        }
                    }

                    if (totalPageviewsElement) {
                        if (data.screenPageViews !== null && data.screenPageViews !== undefined) {
                            totalPageviewsElement.textContent = (data.screenPageViews * toMultiplyWith).toLocaleString();
                        } else {
                            totalPageviewsElement.textContent = 'N/A';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching analytics data:', error);
                    const totalVisitsElement = document.getElementById('total-visits');
                    const totalPageviewsElement = document.getElementById('total-pageviews');
                    if (totalVisitsElement) {
                        totalVisitsElement.textContent = 'Error';
                    }
                    if (totalPageviewsElement) {
                        totalPageviewsElement.textContent = 'Error';
                    }
                });
        });
    </script>
@endpush