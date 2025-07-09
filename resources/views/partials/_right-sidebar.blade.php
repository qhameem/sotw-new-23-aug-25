<div class="h-full flex flex-col md:border-r border-l md:border-gray-200 md:w-sm" x-data="{ searchFocused: false }" @search-focus-changed.window="searchFocused = $event.detail">
    <div x-show="!searchFocused">
        @if(isset($isCategoryPage) && $isCategoryPage)
            <div class="p-4">
                <h3 class="text-base font-semibold mb-4 text-gray-800">Categories</h3>
                <ul class="space-y-1">
                    @foreach($categories as $cat)
                        <li>
                            <a href="{{ route('categories.show', ['category' => $cat->slug]) }}"
                               class="text-sm text-gray-700 hover:text-gray-800 hover:underline @if(isset($category) && $cat->slug === $category->slug) font-bold text-primary-500 @endif">
                                {{ $cat->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            @if(Route::currentRouteName() == 'home')
            <div class="p-4">
                <h3 class="text-sm font-medium text-gray-800">{{ now()->year }} Statistics
                    <div class="relative group inline-block">
                        <span class="cursor-default text-gray-900 font-semibold">&#9432;</span>
                        <div class="absolute z-10 hidden group-hover:block mt-1 px-2 py-1 text-xs text-gray-600 rounded border bg-gray-50 whitespace-nowrap">
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
            @endif
            @if(Route::currentRouteName() == 'home')
                @guest
                    <div class="p-4">
                        @include('partials._what-is-sotw-card')
                    </div>
                @endguest
            @endif
            @if (View::hasSection('right_sidebar_content'))
                @yield('right_sidebar_content')
            @else
            @if(!request()->is('promote-your-software'))
                @if(request()->is('articles*'))
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
                                            <img src="{{ $faviconUrl }}" alt="Software on the web" class="h-6 w-6 rounded-full object-cover mr-2">
                                            <span class="text-sm text-gray-600">
                                                In {{ $pick->categories->first()->name ?? 'Uncategorized' }} by Software on the web
                                            </span>
                                        @else
                                            <img src="{{ $pick->author->google_avatar ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($pick->author->email ?? ''))) . '?d=mp' }}" alt="{{ $pick->author->name ?? 'Author' }}" class="h-6 w-6 rounded-full object-cover mr-2">
                                            <span class="text-sm text-gray-600">
                                                In {{ $pick->categories->first()->name ?? 'Uncategorized' }} by {{ $pick->author->name ?? 'Unknown' }}
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('articles.show', $pick->slug) }}" class="text-sm font-semibold text-gray-900 hover:text-primary-500">
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
                @endif
            @endif
            @endif
        @endif
    </div>
    <div x-show="searchFocused" style="display: none;" class="p-4 text-gray-500" x-data="{ results: null }" @search-results.window="results = $event.detail">
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
                                <div @click="$dispatch('open-product-modal', product)" class="flex items-center p-2 rounded-lg hover:bg-gray-100 cursor-pointer">
                                    <template x-if="product.logo_url">
                                        <img :src="product.logo_url" :alt="product.name" class="w-8 h-8 mr-3 rounded-md object-cover">
                                    </template>
                                    <template x-if="!product.logo_url">
                                        <div class="w-8 h-8 mr-3 rounded-md bg-gray-200 flex items-center justify-center">
                                            <span class="text-xs font-semibold text-gray-500" x-text="product.name.substring(0, 1)"></span>
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
document.addEventListener('DOMContentLoaded', function() {
    fetch('/api/analytics/total-sessions')
        .then(response => response.json())
        .then(data => {
            const totalVisitsElement = document.getElementById('total-visits');
            const totalPageviewsElement = document.getElementById('total-pageviews');

            if (totalVisitsElement) {
                if (data.sessions !== null && data.sessions !== undefined) {
                    totalVisitsElement.textContent = data.sessions.toLocaleString();
                } else {
                    totalVisitsElement.textContent = 'N/A';
                }
            }

            if (totalPageviewsElement) {
                if (data.screenPageViews !== null && data.screenPageViews !== undefined) {
                    totalPageviewsElement.textContent = data.screenPageViews.toLocaleString();
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