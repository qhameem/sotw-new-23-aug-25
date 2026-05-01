@php use App\Support\ProductLogo; @endphp
<div class="md:space-y-2">
    @php
        $finalProductList = ProductLogo::productListItems($regularProducts ?? collect(), $promotedProducts ?? collect());
        $shouldDisplayAd = isset($belowProductListingAd) && $belowProductListingAd && isset($belowProductListingAdPosition);
        $adDisplayed = false;
        $productCountForAd = count($finalProductList); // Count of products to display for ad logic
    @endphp

    @if($productCountForAd === 0 && $shouldDisplayAd)
        @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
        @php $adDisplayed = true; @endphp
    @endif

    @forelse($finalProductList as $loopIndexActual => $product)
        @php
            // $loopIndex is now 1-based for ad logic, based on visible product sequence
            $loopIndex = $loop->iteration;
            $productLogo = ProductLogo::url($product);
            $isPromoted = $product->is_promoted ?? false; // Ensure $isPromoted is defined
        @endphp
        <article
            class="p-4 md:p-4 flex items-start gap-3 md:gap-3 transition relative group cursor-pointer hover:bg-gray-50 "
            itemscope itemtype="https://schema.org/SoftwareApplication" x-data="{}" @if($isPromoted)
                @click="window.open('{{ route('products.click', ['product' => $product->slug, 'surface' => 'promoted_listing_card']) }}', '_blank')"
            @else @click="window.location.href = '{{ route('products.show', $product->slug) }}'" @endif>
            <img src="{{ $productLogo ?? asset('favicon/favicon-32x32.png') }}" alt="{{ $product->name }} logo"
                class="w-12 h-12 rounded-xl object-cover flex-shrink-0 bg-gray-100"
                width="48" height="48"
                loading="{{ ProductLogo::loading($loopIndex) }}"
                fetchpriority="{{ ProductLogo::fetchPriority($loopIndex) }}"
                decoding="async"
                itemprop="image" />
            <div class="flex-1">
                <h2 class="site-heading-text text-sm font-semibold leading-tight flex items-center">
                    @if(!$isPromoted)
                        <span itemprop="name" class="site-heading-text text-left">{{ $product->name }}</span>
                        <a href="{{ route('products.click', ['product' => $product->slug, 'surface' => 'product_list']) }}"
                            target="_blank" rel="noopener nofollow" @click.stop
                            class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-all duration-200 rounded-full text-gray-600 hover:text-rose-500 hover:bg-rose-50"
                            aria-label="Open product link in new tab">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @else
                        <span itemprop="name" class="site-heading-text text-left">{{ $product->name }}</span>
                    @endif
                </h2>
                <p class="site-body-text text-gray-700 text-sm mb-0 line-clamp-2" itemprop="description">
                    {{ $product->tagline }}
                </p>

                <div class="mt-0.5 flex flex-wrap gap-2 items-center">
                    @if($isPromoted)
                        <span class="inline-flex items-center bg-gray-100 text-gray-800 rounded text-xs">
                            <span class="px-2 py-1 font-semibold">Promoted</span>
                        </span>
                    @endif
                    <meta itemprop="applicationCategory"
                        content="{{ $product->application_category ?? 'BusinessApplication' }}" />
                    <meta itemprop="operatingSystem" content="{{ $product->operating_system ?? 'Web' }}" />

                    <div class="flex flex-shrink-0 items-center gap-1 text-gray-400 text-[10px] mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-300" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M6 13H2c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h4c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zm16-4h-4c-.6 0-1 .4-1 1v12c0 .6.4 1 1 1h4c.6 0 1-.4 1-1V10c0-.6-.4-1-1-1zm-8-8h-4c-.6 0-1 .4-1 1v20c0 .6.4 1 1 1h4c.6 0 1-.4 1-1V2c0-.6-.4-1-1-1z" />
                        </svg>
                        <span class="font-medium">{{ number_format($product->impressions) }}</span>
                    </div>

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

            <div class="flex items-center gap-2">
                @livewire('product-upvote-button', ['product' => $product], key($product->id))
            </div>
        </article>

        @if($shouldDisplayAd && !$adDisplayed && $belowProductListingAdPosition == $loopIndex)
            @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
            @php $adDisplayed = true; @endphp
        @endif
    @empty
        {{-- This case is handled by the check before the loop if products are empty --}}
        @if(!$adDisplayed && $productCountForAd === 0) {{-- Check if ad was displayed when list was empty --}}
            <div class="text-gray-40 text-center py-12">No products found.</div>
        @endif
    @endforelse

    @if($shouldDisplayAd && !$adDisplayed && $productCountForAd > 0)
        {{-- Display ad after the last product if N was too large or not met by loop --}}
        @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
        @php $adDisplayed = true; @endphp
    @endif
</div>
