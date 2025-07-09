@php use Illuminate\Support\Str; @endphp
<div class="md:space-y-2">
@php
    $promotedProductsList = $promotedProducts ?? collect();
    $regularProductsList = $regularProducts ?? collect();
    $finalProductList = [];
    $maxPosition = 0;

    // Place promoted products
    foreach ($promotedProductsList as $p) {
        if (isset($p->promoted_position) && $p->promoted_position > 0) {
            $finalProductList[$p->promoted_position - 1] = $p;
            if ($p->promoted_position > $maxPosition) {
                $maxPosition = $p->promoted_position;
            }
        }
    }

    // Fill in with regular products
    $regularProductIndex = 0;
    $currentFinalListLength = count($finalProductList);
    $targetListSize = max($maxPosition, $currentFinalListLength + $regularProductsList->count());
    
    for ($i = 0; $i < $targetListSize; $i++) {
        if (!isset($finalProductList[$i])) {
            if ($regularProductIndex < $regularProductsList->count()) {
                $finalProductList[$i] = $regularProductsList[$regularProductIndex];
                $regularProductIndex++;
            } else {
                // No more regular products to fill this slot, break if we are beyond maxPosition
                // or if we only intended to fill up to the count of regular products after promoted ones.
                // For now, let ensure list is at least $maxPosition long if promoted products define it.
                 if ($i >= $maxPosition && $regularProductIndex >= $regularProductsList->count()) break;
            }
        }
    }
    // Remove empty slots if any were created by high promoted_position values beyond available products
    $finalProductList = array_filter($finalProductList, function($value) { return $value !== null; });
    // Re-index if sparse, though foreach handles non-sequential keys. For consistent $loopIndex, ksort might be an option.
    // For simplicity, we'll iterate directly. If $loopIndex is critical for ad placement, this needs more thought.
    ksort($finalProductList); // Sort by keys (original positions) to maintain order

    $shouldDisplayAd = isset($belowProductListingAd) && $belowProductListingAd && isset($belowProductListingAdPosition);
    $adDisplayed = false;
    $productCountForAd = count($finalProductList); // Count of products to display for ad logic
@endphp

@if($productCountForAd === 0 && $shouldDisplayAd)
    @include('partials.render_ad_block', ['ad' => $belowProductListingAd])
    @php $adDisplayed = true; @endphp
@endif

@forelse($finalProductList as $loopIndexActual => $product) {{-- $loopIndexActual is the 0-based array index after ksort --}}
    @php
        // $loopIndex is now 1-based for ad logic, based on visible product sequence
        $loopIndex = $loop->iteration;
        $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
        $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
        $isPromoted = $product->is_promoted ?? false; // Ensure $isPromoted is defined
    @endphp
    <article class="p-4 md:p-4 flex items-start gap-3 md:gap-3 transition relative group cursor-pointer hover:bg-gray-50 "
             itemscope itemtype="https://schema.org/Product"
             x-data="{}"
             @if($isPromoted)
                @click="window.open('{{ $product->link . (parse_url($product->link, PHP_URL_QUERY) ? '&' : '?') }}utm_source=softwareontheweb.com&utm_medium=promoted_listing_card', '_blank')"
             @else
                @click.prevent="$dispatch('open-product-modal', {{ json_encode($product) }})"
             @endif
    >
        <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo" class="size-8 rounded object-cover flex-shrink-0" loading="lazy" itemprop="image" />
        <div class="flex-1">
            <h2 class="text-md font-semibold leading-tight mb-0.5 flex items-center">
                @if(!$isPromoted)
                    <span itemprop="name" class="text-left">{{ $product->name }}</span>
                    <a href="{{ $product->link . (parse_url($product->link, PHP_URL_QUERY) ? '&' : '?') }}utm_source=softwareontheweb.com"
                       target="_blank" rel="noopener nofollow"
                       @click.stop
                       class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200  rounded-full hover:bg-gray-100 "
                       aria-label="Open product link in new tab">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600 " fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                @else
                    <span itemprop="name" class="text-left">{{ $product->name }}</span>
                @endif
            </h2>
            <p class="text-gray-700 text-xs md:text-sm mt-0.5 mb-1 line-clamp-2" itemprop="description">{{ $product->tagline }}</p>
            
            <div class="mt-1.5 flex flex-wrap gap-2 items-center">
                @if($isPromoted)
                    <span class="inline-flex items-center bg-gray-100 text-gray-800 rounded text-xs">
                        <span class="px-2 py-1 font-semibold">Promoted</span>
                    </span>
                @endif

                @foreach($product->categories as $cat)
                    <a href="{{ route('categories.show', ['category' => $cat->slug]) }}"
                       @click.stop
                       class="inline-flex items-center bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 rounded text-xs @if($isPromoted) opacity-75 @endif">
                        <span class="px-2 py-1 hover:underline">{{ $cat->name }}</span>
                        @if(isset($cat->products_count))
                        <span class="ml-1.5 mr-2 h-5 w-5 min-w-[1.25rem] rounded-full bg-gray-200 hover:bg-gray-300 text-gray-500 hover:text-gray-600 text-xs font-semibold flex items-center justify-center leading-none antialiased">
                            {{ $cat->products_count > 99 ? '99+' : $cat->products_count }}
                        </span>
                        @endif
                    </a>
                @endforeach
            </div>

            <!-- <div class="text-xs text-gray-600  mt-1" itemprop="brand" itemscope itemtype="https://schema.org/Organization">
                By: <span itemprop="name">{{ $product->user->name ?? 'Unknown Contributor' }}</span>
            </div> -->

            <div class="text-xs text-gray-600  mt-1" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <meta itemprop="priceCurrency" content="USD" />
                @if(isset($product->price) && is_numeric($product->price) && $product->price > 0)
                    Price: <span itemprop="price" content="{{ number_format($product->price, 2, '.', '') }}">${{ number_format($product->price, 2) }}</span>
                    <link itemprop="availability" href="https://schema.org/InStock" />
                @elseif(isset($product->pricing_type) && strtolower($product->pricing_type ?? '') === 'free')
                    Price: <span itemprop="price" content="0.00">Free</span>
                    <link itemprop="availability" href="https://schema.org/InStock" />
                @else
                @endif
            </div>

            <!-- <div class="text-xs text-gray-600  mt-1" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                <meta itemprop="worstRating" content="0">
                <meta itemprop="bestRating" content="5">
                @if(isset($product->average_rating) && is_numeric($product->average_rating) && isset($product->votes_count) && $product->votes_count > 0)
                    <meta itemprop="ratingValue" content="{{ number_format($product->average_rating, 1) }}">
                    Rating: <span class="font-semibold">{{ number_format($product->average_rating, 1) }}</span>/5
                    (<span itemprop="ratingCount">{{ $product->votes_count }}</span> votes)
                @else
                    <meta itemprop="ratingValue" content="0">
                    Votes: <span itemprop="ratingCount">{{ $product->votes_count ?? 0 }}</span>
                @endif
            </div> -->
        </div>

        <div x-data="upvote({{ $product->isUpvotedByCurrentUser ? 'true' : 'false' }}, {{ $product->votes_count }}, {{ $product->id }}, '{{ $product->slug }}', {{ Auth::check() ? 'true' : 'false' }}, '{{ csrf_token() }}')" class="upvote-control-wrapper flex flex-col items-center justify-start pt-1 ml-1 md:ml-2 w-10 flex-shrink-0">
            <button type="button"
                    @click.stop="toggleUpvote"
                    :class="{ 'text-[var(--color-primary-500)]': isUpvoted, 'text-gray-400 hover:text-gray-600': !isUpvoted, 'opacity-50': isLoading }"
                    class="p-1 rounded-md outline-none"
                    :disabled="isLoading"
                    aria-label="Upvote {{ $product->name }}">
                <svg width="20px" height="20px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill="currentColor" d="M8 1.25a2.101 2.101 0 00-1.785.996l.64.392-.642-.388-5.675 9.373-.006.01a2.065 2.065 0 00.751 2.832c.314.183.67.281 1.034.285h11.366a2.101 2.101 0 001.791-1.045 2.064 2.064 0 00-.006-2.072L9.788 2.25l-.003-.004A2.084 2.084 0 008 1.25z"></path></g></svg>
            </button>
            <span x-text="votesCount" class="text-xs text-gray-600 mt-0.5"></span>
            <span x-show="errorMessage" x-text="errorMessage" class="text-red-500 text-xs mt-1"></span>
        </div>
    </article>

    @if($shouldDisplayAd && !$adDisplayed && $belowProductListingAdPosition == $loopIndex)
        @include('partials.render_ad_block', ['ad' => $belowProductListingAd])
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
    @include('partials.render_ad_block', ['ad' => $belowProductListingAd])
    @php $adDisplayed = true; @endphp
@endif
</div>