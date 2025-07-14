<div class="space-y-6 p-6 mt-4">
    @if($pricingCategory)
        <div>
            <h3 class="text-sm font-semibold text-gray-800 mb-2">Pricing Model</h3>
            <p class="text-sm text-gray-600">{{ $pricingCategory->name }}</p>
        </div>
    @endif

    @if($similarProducts->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-gray-800 mb-4">Similar Products</h3>
            <ul class="space-y-6">
                @foreach($similarProducts as $similarProduct)
                    <li class="flex items-start">
                        <a href="{{ route('products.show', $similarProduct->slug) }}">
                            @if($similarProduct->logo)
                                <img src="{{ Str::startsWith($similarProduct->logo, 'http') ? $similarProduct->logo : asset('storage/' . $similarProduct->logo) }}" alt="{{ $similarProduct->name }} logo" class="w-10 h-10 object-contain rounded-lg mr-4">
                            @elseif($similarProduct->link)
                                <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($similarProduct->link) }}" alt="{{ $similarProduct->name }} favicon" class="w-10 h-10 object-contain rounded-lg mr-4">
                            @endif
                        </a>
                        <div class="flex-1">
                            <a href="{{ route('products.show', $similarProduct->slug) }}" class="font-medium text-sm text-gray-900 hover:text-primary-500">{{ $similarProduct->name }}</a>
                            <p class="text-xs text-gray-600">{{ Str::limit($similarProduct->tagline, 35) }}</p>
                            <!-- <div class="text-xs text-gray-500 mt-2">
                                <span class="font-bold">{{ $similarProduct->votes_count }}</span> Upvotes
                            </div> -->
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>