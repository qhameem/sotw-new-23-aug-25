@php use Illuminate\Support\Str; @endphp
<div class="md:space-y-2">
@php
    $promotedProductsList = $promotedProducts ?? collect();
    $regularProductsList = $regularProducts ?? collect();
    $finalProductList = [];
    $maxPosition = 0;
    $premiumProductsList = $premiumProducts ?? collect();
    $premiumProductIndex = 0;

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
    $regularProductsCollection = $regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator ? $regularProductsList->getCollection() : $regularProductsList;
    
    $currentFinalListLength = count($finalProductList);
    $targetListSize = max($maxPosition, $currentFinalListLength + $regularProductsCollection->count());
    
    for ($i = 0; $i < $targetListSize; $i++) {
        if (!isset($finalProductList[$i])) {
            if ($regularProductIndex < $regularProductsCollection->count()) {
                $finalProductList[$i] = $regularProductsCollection[$regularProductIndex];
                $regularProductIndex++;
            } else {
                 if ($i >= $maxPosition && $regularProductIndex >= $regularProductsCollection->count()) break;
            }
        }
    }
    $finalProductList = array_filter($finalProductList, fn($value) => $value !== null);
    ksort($finalProductList);

    // Intersperse premium products
    if ($premiumProductsList->isNotEmpty()) {
        $newFinalProductList = [];
        $productCount = 0;
        foreach ($finalProductList as $product) {
            $newFinalProductList[] = $product;
            $productCount++;
            if ($productCount % 4 === 0 && $premiumProductIndex < $premiumProductsList->count()) {
                $newFinalProductList[] = $premiumProductsList[$premiumProductIndex];
                $premiumProductIndex++;
            }
        }
        $finalProductList = $newFinalProductList;
    }

    $shouldDisplayAd = isset($belowProductListingAd) && $belowProductListingAd && isset($belowProductListingAdPosition);
    $adDisplayed = false;
    $productCountForAd = count($finalProductList);
@endphp

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

@if($productCountForAd === 0 && $shouldDisplayAd)
    @include('partials.render_ad_block', ['ad' => $belowProductListingAd])
    @php $adDisplayed = true; @endphp
@endif

@php
    $isPaginator = $regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $baseNumber = $isPaginator ? $regularProductsList->firstItem() : 1;
@endphp
@forelse($finalProductList as $loopIndexActual => $product)
    @php
        $loopIndex = $loop->iteration;
        $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
        $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
        $isPromoted = $product->is_promoted ?? false;
    @endphp
    <article class="p-4 flex items-center gap-3 md:gap-3 transition relative group cursor-pointer hover:bg-gray-50 "
             x-data='{}'
             @click="window.location.href = '{{ route('products.show', $product->slug) }}'"
    >
        <span class="text-xs text-gray-500">{{ $baseNumber + $loop->index }}.</span>
        <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo" class="size-14 rounded-lg object-cover flex-shrink-0" loading="lazy" />
        <div class="flex-1">
            <h2 class="text-sm font-semibold leading-tight mb-0.5 flex items-center">
                <span class="text-left">{{ $product->name }}</span>
                @if(!$isPromoted)
                    <a href="{{ $product->link . (parse_url($product->link, PHP_URL_QUERY) ? '&' : '?') }}utm_source=softwareontheweb.com"
                       target="_blank" rel="noopener nofollow"
                       @click.stop
                       class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200  rounded-full hover:bg-gray-100 "
                       aria-label="Open product link in new tab">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600 " fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                @endif
            </h2>
            <p class="text-gray-800 text-sm md:text-sm mt-0.5 line-clamp-2">{{ $product->tagline }}</p>
            
            <div class="mt-1 flex flex-wrap gap-2 items-center">
@if($isPromoted || $product->is_premium)
                    <span class="inline-flex items-center bg-rose-50 text-rose-400 rounded text-xs mr-2">
                        <span class="px-2 py-1 font-medium">Premium</span>
                    </span>
                @endif
                @foreach($product->categories as $cat)
                <a href="{{ route('categories.show', ['category' => $cat->slug]) }}"
                       @click.stop
                       class="hidden sm:block inline-flex items-center text-gray-600  hover:text-gray-800 rounded text-xs @if($isPromoted) opacity-75 @endif">
                        <span class="px-0 py-0 hover:underline">{{ $cat->name }}</span>
                        @if(isset($cat->products_count))
                        <span class="ml-1.5 mr-2 h-5 w-5 min-w-[1.25rem] rounded-full bg-gray-200 hover:bg-gray-300 text-gray-500 hover:text-gray-600 text-xs font-semibold flex items-center justify-center leading-none antialiased">
                            {{ $cat->products_count > 99 ? '99+' : $cat->products_count }}
                        </span>
                        @endif
                    </a>
                 @if(!$loop->last)
                    <span class="text-gray-400">•</span>
                 @endif
                @endforeach
            </div>

            <div class="text-xs text-gray-600  mt-1">
                @if(isset($product->price) && is_numeric($product->price) && $product->price > 0)
                    Price: <span>${{ number_format($product->price, 2) }}</span>
                @elseif(isset($product->pricing_type) && strtolower($product->pricing_type ?? '') === 'free')
                    Price: <span>Free</span>
                @else
                @endif
            </div>
        </div>

    </article>

    @if($shouldDisplayAd && !$adDisplayed && $belowProductListingAdPosition == $loopIndex)
        @include('partials.render_ad_block', ['ad' => $belowProductListingAd])
        @php $adDisplayed = true; @endphp
    @endif
@empty
    @if(!$adDisplayed && $productCountForAd === 0)
      <div class="text-gray-400 text-center py-12">No products found.</div>
    @endif
@endforelse

@if($shouldDisplayAd && !$adDisplayed && $productCountForAd > 0)
    @include('partials.render_ad_block', ['ad' => $belowProductListingAd])
    @php $adDisplayed = true; @endphp
@endif

@if ($regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-4">
        {{ $regularProductsList->links() }}
    </div>
@endif
</div>