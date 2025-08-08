@php
    use Illuminate\Support\Str;
    $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
    $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
@endphp
<article wire:key="product-{{ $product->id }}" class="p-4 flex items-center gap-2 md:gap-1 transition relative group">
    <div class="flex items-center gap-3 flex-1">
        <a href="{{ route('products.show', $product->slug) }}" class="flex items-start md:items-center gap-2">
        <span class="hidden md:block text-xs text-gray-500">{{ $itemNumber }}.</span>
        <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo" class="size-16 rounded-xl object-cover border flex-shrink-0" />
        <div class="flex flex-col space-y-1">
            <h2 class="text-sm font-semibold flex items-center leading-none">
                <span class="text-left text-black mt-1">{{ $product->name }}</span>
                <a href="{{ $product->link . (parse_url($product->link, PHP_URL_QUERY) ? '&' : '?') }}utm_source=softwareontheweb.com"
                   target="_blank" rel="noopener ugc"
                   @click.stop
                   class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200  rounded-full hover:bg-gray-100 "
                   aria-label="Open product link in new tab">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600 " fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </h2>
            
            <p class="text-gray-900 text-sm line-clamp-2">{{ $product->tagline }}</p>
            
            <div class="flex flex-wrap gap-2 items-center">
                <span class="inline-flex items-center text-gray-500 rounded text-xs mr-2">
                    <span class="py-1 font-medium text-green-700">Promoted</span>
                   
                </span>
            </div>

            <div class="text-xs text-gray-600">
                @if(isset($product->price) && is_numeric($product->price) && $product->price > 0)
                    Price: <span>${{ number_format($product->price, 2) }}</span>
                @elseif(isset($product->pricing_type) && strtolower($product->pricing_type ?? '') === 'free')
                    Price: <span>Free</span>
                @else
                @endif
            </div>
                        </div>
        </a>
    </div>
    <div class="flex-shrink-0">
        @livewire('product-upvote-button', ['product' => $product], key($product->id))
    </div>
</article>