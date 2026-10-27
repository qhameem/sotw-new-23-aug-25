@php($detailRows = app(\App\Services\ProductDetailData::class)->rows($product, $bestForCategories, $platformCategories, $useCaseCategories))
<x-products.detail-section id="details" title="Details" tone="violet">
    <dl class="product-rows">
        @foreach($detailRows as $label => $items)
            @if($items->isNotEmpty())
                <div class="product-detail-row">
                    <dt>{{ $label }}</dt>
                    <dd class="flex flex-wrap gap-2">
                        @foreach($items as $item)
                            @if($item['url'])
                                <a href="{{ $item['url'] }}" class="product-chip">{{ $item['label'] }}</a>
                            @else
                                <span class="product-chip">{{ $item['label'] }}</span>
                            @endif
                        @endforeach
                        @if($label === 'Pricing' && $product->pricing_page_url)
                            <a href="{{ $product->pricing_page_url }}" target="_blank" rel="{{ \App\Support\OutboundLink::rel($product->pricing_page_url, 'pricing_page') }}" class="text-xs underline">View pricing</a>
                        @endif
                    </dd>
                </div>
            @endif
        @endforeach
        @if($product->published_at)
            <div class="product-detail-row"><dt>Launched</dt><dd><time datetime="{{ $product->published_at->toAtomString() }}">{{ $product->published_at->format('F j, Y') }}</time></dd></div>
        @endif
        @if($product->updated_at)
            <div class="product-detail-row"><dt>Last updated</dt><dd><time datetime="{{ $product->updated_at->toAtomString() }}">{{ $product->updated_at->format('F j, Y') }}</time></dd></div>
        @endif
    </dl>
</x-products.detail-section>
