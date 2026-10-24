@php
    $knownPrice = $product->facts_json['price_amount'] ?? $product->price;
    $knownCurrency = $product->facts_json['currency'] ?? $product->currency;
    $priceLabel = is_numeric($knownPrice) && (float) $knownPrice > 0 && filled($knownCurrency)
        ? strtoupper($knownCurrency).' '.number_format((float) $knownPrice, 2) : null;
    $pricingCategories = $product->categories->filter(fn ($category) => $category->types->contains('name', 'Pricing'));
    $detailRows = [
        'Pricing' => $pricingCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()])->values()
            ->when($priceLabel, fn ($items) => $items->push(['label' => $priceLabel, 'url' => null])),
        'Built for' => $bestForCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()]),
        'Platforms' => $platformCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()]),
        'Categories' => $product->categories->diff($bestForCategories)->diff($platformCategories)->diff($useCaseCategories)
            ->diff($pricingCategories)
            ->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()]),
        'Built with' => $product->techStacks->map(fn ($tech) => ['label' => $tech->name, 'url' => route('pseo.builtWith', $tech->slug)]),
    ];
    if ($detailRows['Platforms']->isEmpty()) {
        $detailRows['Platforms'] = collect($product->facts_json['platforms'] ?? [])
            ->filter(fn ($platform) => is_string($platform) && filled($platform))
            ->map(fn ($platform) => ['label' => $platform, 'url' => null]);
        if ($detailRows['Platforms']->isEmpty() && filled($product->operating_system)) {
            $detailRows['Platforms']->push(['label' => $product->operating_system, 'url' => null]);
        }
    }
    $applicationType = $product->facts_json['product_type'] ?? $product->application_category;
    if ($detailRows['Categories']->isEmpty() && filled($applicationType)) {
        $detailRows['Categories']->push(['label' => $applicationType, 'url' => null]);
    }
@endphp
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
