@php use App\Support\ProductLogo; @endphp
<div class="pb-24">
@php
    $regularProductsList = $regularProducts ?? collect();
    $finalProductList = ProductLogo::paginatedListItems($regularProductsList, $premiumProducts ?? collect());
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
    @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
    @php $adDisplayed = true; @endphp
@endif

@php
    $isPaginator = $regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $baseNumber = $isPaginator ? $regularProductsList->firstItem() : 1;
@endphp
@forelse($finalProductList as $loopIndexActual => $product)
    @php
        $loopIndex = $loop->iteration;
        $isPromoted = $product->is_promoted ?? ($product->is_premium ?? false);
    @endphp

    @if ($isPromoted)
        @include('partials._promoted_product_item', ['product' => $product, 'itemNumber' => $baseNumber + $loop->index, 'logoLoadPosition' => $loop->iteration])
    @else
        @include('partials._product_item', ['product' => $product, 'itemNumber' => $baseNumber + $loop->index, 'logoLoadPosition' => $loop->iteration])
    @endif
    @if($shouldDisplayAd && !$adDisplayed && $belowProductListingAdPosition == $loopIndex)
        @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
        @php $adDisplayed = true; @endphp
    @endif
@empty
    @if(!$adDisplayed && $productCountForAd === 0)
      <div class="text-gray-400 text-center py-12">No products found.</div>
    @endif
@endforelse

@if($shouldDisplayAd && !$adDisplayed && $productCountForAd > 0)
    @include('partials.render_ad_block', ['ad' => $belowProductListingAd, 'zoneSlug' => 'below-product-listing'])
    @php $adDisplayed = true; @endphp
@endif

@if ($regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-4">
        {{ $regularProductsList->links() }}
    </div>
@endif
</div>
