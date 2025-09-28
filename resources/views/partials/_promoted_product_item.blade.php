@php
    use Illuminate\Support\Str;
    $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
    $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
    $productUrl = $product->link . (parse_url($product->link, PHP_URL_QUERY) ? '&' : '?') . 'utm_source=softwareontheweb.com';
@endphp
<a href="{{ $productUrl }}"
   target="_blank"
   rel="noopener ugc"
   wire:key="product-{{ $product->id }}"
   class="p-4 flex items-center gap-2 md:gap-1 transition relative group cursor-pointer hover:bg-gray-50 rounded-lg">
    <div class="flex items-center gap-3 flex-1">
        <div class="flex items-start md:items-center gap-2">
            <span class="hidden md:block text-xs text-gray-500">{{ $itemNumber }}.</span>
            <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo" class="size-16 rounded-xl object-cover border flex-shrink-0" />
            <div class="flex flex-col space-y-1">
                <h2 class="text-base font-semibold flex items-center leading-none">
                    <span class="text-left text-black mt-1">{{ $product->name }}</span>
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
        </div>
    </div>
    <div class="flex-shrink-0">
        <div class="py-3 px-2 rounded-md border">
           <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 stroke-gray-500 group-hover:text-gray-800" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.343 15.657L15.657 4.343m0 0v9.9m0-9.9h-9.9"></path> </g></svg>
        </div>
    </div>
</a>