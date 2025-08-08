@php use Illuminate\Support\Str; @endphp
<div class="pb-24">
@php
    $regularProductsList = $regularProducts ?? collect();
    $finalProductList = $regularProductsList instanceof \Illuminate\Pagination\LengthAwarePaginator ? $regularProductsList->items() : $regularProductsList->all();
    $premiumProductsList = $premiumProducts ?? collect();
    $premiumProductIndex = 0;

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
    @endphp
    @include('partials._product_item', ['product' => $product, 'itemNumber' => $baseNumber + $loop->index])
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
