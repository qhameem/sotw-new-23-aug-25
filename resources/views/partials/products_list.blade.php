@php use App\Support\ProductLogo; @endphp
<div class="md:space-y-2">
    @php
        $finalProductList = ProductLogo::productListItems($regularProducts ?? collect(), $promotedProducts ?? collect());
        $shouldDisplayAd = isset($belowProductListingAd) && $belowProductListingAd && isset($belowProductListingAdPosition);
        $adDisplayed = false;
        $productCountForAd = count($finalProductList); // Count of products to display for ad logic
        $regularProductsList = $regularProducts ?? collect();
        $showProductEngagement = $showProductEngagement ?? true;
        $isPaginator = $regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator;
        $organicRank = $isPaginator ? (($regularProductsList->currentPage() - 1) * $regularProductsList->perPage()) : 0;
        $lastPublishedGroup = null;
    @endphp

    @if($productCountForAd > 0)
        <script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => collect($finalProductList)->values()->map(function ($product, $index) {
        return [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => route('products.show', $product->slug),
        ];
    }),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endif

    @if($productCountForAd === 0 && $shouldDisplayAd)
        @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
        @php $adDisplayed = true; @endphp
    @endif

    @forelse($finalProductList as $loopIndexActual => $product)
        @php
            // $loopIndex is now 1-based for ad logic, based on visible product sequence
            $loopIndex = $loop->iteration;
            $productLogo = ProductLogo::url($product);
            $productShowUrl = route('products.show', $product->slug);
            $isPromoted = $product->is_promoted ?? false; // Ensure $isPromoted is defined
            $isHomePage = request()->routeIs('home');
            $isWeeklyPage = request()->routeIs('products.byWeek');
            $isCategoryPage = request()->routeIs('categories.show', 'categories.show.page', 'software-groups.show', 'software-groups.page', 'pseo.builtWith');
            $showMomentumMeta = $isHomePage || $isCategoryPage;
            $logoSize = $isHomePage ? 40 : 48;
            $votesCount = max(1, (int) ($product->votes_count ?? 0));
            $outboundClicksCount = max(0, (int) ($product->outbound_clicks_count ?? 0));
            $impressionsCount = max(0, (int) ($product->impressions ?? 0));
            $clicksCount = $outboundClicksCount + $impressionsCount;
            $momentumLabel = $votesCount >= 4
                ? 'Popular'
                : (($outboundClicksCount >= 3 || $impressionsCount >= 25) ? 'Rising' : 'New');
            $itemNumber = null;
            if (!$isPromoted) {
                $organicRank++;
                $itemNumber = $organicRank;
            }
            $impressionSurface = match (true) {
                request()->routeIs('home') => 'home_list',
                request()->routeIs('products.byWeek') => 'week_list',
                request()->routeIs('products.byDate') => 'date_list',
                request()->routeIs('products.byMonth') => 'month_list',
                request()->routeIs('products.byYear') => 'year_list',
                request()->routeIs('categories.show', 'categories.show.page', 'software-groups.show', 'software-groups.page') => 'category_list',
                request()->routeIs('pseo.builtWith') => 'built_with_list',
                default => 'product_list',
            };
            $publishedGroup = null;
            if (request()->routeIs('categories.show', 'categories.show.page', 'software-groups.show', 'software-groups.page')) {
                $publishedDate = ($product->published_at ?? $product->created_at)->copy();
                $publishedGroup = match (true) {
                    $publishedDate->gte(now()->startOfWeek()) => 'Published this week',
                    $publishedDate->gte(now()->startOfMonth()) => 'Published this month',
                    $publishedDate->gte(now()->startOfYear()) => 'Published this year',
                    default => 'Published earlier',
                };
            }
        @endphp
        @if($publishedGroup && $publishedGroup !== $lastPublishedGroup)
            <div class="border-y border-gray-100 bg-gray-50 px-4 py-2.5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $publishedGroup }}</h2>
            </div>
            @php $lastPublishedGroup = $publishedGroup; @endphp
        @endif
        <article
            class="product-card p-4 md:p-4 flex items-start gap-3 md:gap-3 transition relative group cursor-pointer"
            data-product-id="{{ $product->id }}"
            data-track-impression="true"
            data-impression-surface="{{ $impressionSurface }}"
            itemscope itemtype="https://schema.org/SoftwareApplication" x-data="{}" @if($isPromoted)
                @click="window.open('{{ route('products.click', ['product' => $product->slug, 'surface' => 'promoted_listing_card']) }}', '_blank')"
            @else
                @click="const link = $el.querySelector('[data-product-detail-link]'); if (link) { link.click(); }"
            @endif>
            <meta itemprop="url" content="{{ $productShowUrl }}" />
            <a href="{{ $productShowUrl }}" wire:navigate.hover data-product-detail-link @click.stop class="flex-shrink-0">
                @if($productLogo)
                    <img src="{{ $productLogo }}" alt="{{ $product->name }} logo"
                        @class([
                            'rounded-xl object-contain flex-shrink-0 bg-gray-100 md:w-12 md:h-12',
                            'w-10 h-10' => $isHomePage,
                            'w-12 h-12' => !$isHomePage,
                        ])
                        width="{{ $logoSize }}" height="{{ $logoSize }}"
                        loading="{{ ProductLogo::loading($loopIndex) }}"
                        fetchpriority="{{ ProductLogo::fetchPriority($loopIndex) }}"
                        decoding="async"
                        itemprop="image" />
                @else
                    <div @class([
                        'flex rounded-xl bg-gray-100 text-gray-500 items-center justify-center flex-shrink-0 text-sm font-semibold md:w-12 md:h-12',
                        'w-10 h-10' => $isHomePage,
                        'w-12 h-12' => !$isHomePage,
                    ])
                        aria-label="{{ $product->name }} logo placeholder">
                        {{ ProductLogo::initial($product) }}
                    </div>
                @endif
            </a>
            <div class="flex-1">
                <h2 class="site-heading-text text-base font-semibold leading-tight flex items-center">
                    <a href="{{ $productShowUrl }}" wire:navigate.hover @click.stop
                        class="site-heading-text text-left hover:underline flex items-center gap-1">
                        @if(!$isPromoted && $itemNumber)
                            <span class="site-heading-text text-left text-base font-semibold text-black">{{ $itemNumber }}.</span>
                        @endif
                        <span itemprop="name" class="site-heading-text text-left">{{ $product->name }}</span>
                    </a>
                    @if(!$isPromoted)
                        <a href="{{ route('products.click', ['product' => $product->slug, 'surface' => 'product_list']) }}"
                            target="_blank" rel="{{ \App\Support\OutboundLink::rel($product->link, 'product_link') }}" @click.stop
                            class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-all duration-200 rounded-full text-gray-600 hover:text-rose-500 hover:bg-rose-50"
                            aria-label="Open product link in new tab">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                </h2>
                <a href="{{ $productShowUrl }}" wire:navigate.hover @click.stop class="block">
                    <p @class([
                        'site-body-text mb-0 line-clamp-2',
                        'text-xs md:text-base' => $isHomePage,
                        'text-base' => !$isHomePage,
                    ]) itemprop="description">
                        {{ $product->tagline }}
                    </p>
                </a>

                <div class="mt-0.5 flex flex-wrap gap-2 items-center">
                    @if($isPromoted)
                        <span class="inline-flex items-center bg-gray-100 text-gray-800 rounded text-xs">
                            <span class="px-2 py-1 font-semibold">Promoted</span>
                        </span>
                    @endif
                    @if($showProductEngagement && $showMomentumMeta && !$isHomePage && !$isPromoted)
                        <span @class([
                            'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.08em]',
                            'bg-slate-100 text-slate-500' => $momentumLabel === 'Rising',
                            'bg-slate-100 text-slate-600' => $momentumLabel !== 'Rising',
                        ])>
                            {{ $momentumLabel }}
                        </span>
                    @endif
                    <meta itemprop="applicationCategory"
                        content="{{ $product->application_category ?? 'BusinessApplication' }}" />
                    <meta itemprop="operatingSystem" content="{{ $product->operating_system ?? 'Web' }}" />

                    @if($showMomentumMeta && !$isPromoted && $clicksCount > 0)
                        <div class="flex flex-shrink-0 items-center text-gray-400 text-[10px] mr-2">
                            <span class="font-medium">{{ number_format($clicksCount) }} clicks</span>
                        </div>
                    @endif

                    <x-product-category-tags :categories="$product->categories" :withCounts="true" :hideOnMobile="true" />
                </div>

                <!-- <div class="text-xs text-gray-600  mt-1" itemprop="brand" itemscope itemtype="https://schema.org/Organization">
                                                    By: <span itemprop="name">{{ $product->user->name ?? 'Unknown Contributor' }}</span>
                                                </div> -->

                <div class="site-body-text text-xs text-gray-600 mt-1" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                    <meta itemprop="priceCurrency" content="USD" />
                    <link itemprop="availability" href="https://schema.org/InStock" />
                    @if(isset($product->price) && is_numeric($product->price) && $product->price > 0)
                        Price: <span itemprop="price"
                            content="{{ number_format($product->price, 2, '.', '') }}">${{ number_format($product->price, 2) }}</span>
                    @elseif(isset($product->pricing_type) && strtolower($product->pricing_type ?? '') === 'free')
                        Price: <span itemprop="price" content="0.00">Free</span>
                    @else

                        <meta itemprop="price" content="0.00" />
                    @endif
                </div>

                <div class="site-body-text text-xs text-gray-600 mt-1" itemprop="aggregateRating" itemscope
                    itemtype="https://schema.org/AggregateRating">
                    <meta itemprop="worstRating" content="1">
                    <meta itemprop="bestRating" content="5">
                    @if(isset($product->average_rating) && is_numeric($product->average_rating) && $product->average_rating > 0)
                        <meta itemprop="ratingValue" content="{{ number_format($product->average_rating, 1) }}">
                        <meta itemprop="ratingCount" content="{{ $product->votes_count > 0 ? $product->votes_count : 1 }}">
                    @else
                        <meta itemprop="ratingValue" content="5">
                        <meta itemprop="ratingCount" content="1">
                    @endif
                </div>
            </div>

            @if($showProductEngagement && !$isHomePage && !$isWeeklyPage)
                <div class="flex items-center gap-2">
                    @include('partials.product-upvote-button', [
                        'product' => $product,
                        'preferMomentumLabels' => $showMomentumMeta,
                    ])
                </div>
            @endif
        </article>

        @if($shouldDisplayAd && !$adDisplayed && $belowProductListingAdPosition == $loopIndex)
            @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
            @php $adDisplayed = true; @endphp
        @endif
    @empty
        {{-- This case is handled by the check before the loop if products are empty --}}
        @if(!$adDisplayed && $productCountForAd === 0) {{-- Check if ad was displayed when list was empty --}}
            <div class="text-gray-400 text-center py-12">No products found.</div>
        @endif
    @endforelse

    @if($shouldDisplayAd && !$adDisplayed && $productCountForAd > 0)
        {{-- Display ad after the last product if N was too large or not met by loop --}}
        @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
        @php $adDisplayed = true; @endphp
    @endif

    @if (!empty($categoryPagination))
        <nav class="mt-6 flex items-center justify-between gap-4 border-t border-gray-100 pt-4" aria-label="Category pagination">
            <div class="text-sm text-gray-500">
                Page {{ $categoryPagination['current_page'] }} of {{ $categoryPagination['last_page'] }}
            </div>
            <div class="flex items-center gap-3">
                @if(!empty($categoryPagination['previous_url']))
                    <a href="{{ $categoryPagination['previous_url'] }}" rel="prev" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Previous
                    </a>
                @endif
                @if(!empty($categoryPagination['next_url']))
                    <a href="{{ $categoryPagination['next_url'] }}" rel="next" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Next
                    </a>
                @endif
            </div>
        </nav>
    @elseif ($regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-4">
            {{ $regularProductsList->links() }}
        </div>
    @endif
</div>
