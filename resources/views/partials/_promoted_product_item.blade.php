@php
    use Illuminate\Support\Str;
    $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
    $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
    $isPromoted = $product->is_promoted ?? false;
@endphp
<article wire:key="product-{{ $product->id }}"
    class="product-card p-4 flex items-center gap-2 md:gap-1 transition relative group hover:bg-stone-50 rounded-lg"
    data-product-id="{{ $product->id }}">
    <div class="flex items-center gap-3 flex-1">
        <a href="{{ route('products.show', $product->slug) }}" class="flex items-start md:items-center gap-2">
            <span class="hidden md:block text-xs text-gray-500">{{ $itemNumber }}.</span>
            <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo"
                class="w-[65px] h-[65px] rounded-xl object-cover flex-shrink-0" />
            <div class="flex flex-col space-y-0">
                <h2 class="text-base font-semibold flex items-center leading-none">
                    <span class="text-left text-black mt-1">{{ $product->name }}</span>
                    <a href="{{ $product->link . (parse_url($product->link, PHP_URL_QUERY) ? '&' : '?') }}utm_source=softwareontheweb.com"
                        target="_blank" rel="noopener ugc" @click.stop
                        class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-20  rounded-full hover:bg-gray-100 "
                        aria-label="Open product link in new tab">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600 " fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </h2>

                <p class="text-gray-900 text-base line-clamp-2">{{ $product->tagline }}</p>

                <div class="flex flex-wrap gap-2 items-center">
                    <div class="flex flex-shrink-0 items-center gap-1 text-gray-400 text-[10px] mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-300" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M6 13H2c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h4c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zm16-4h-4c-.6 0-1 .4-1 1v12c0 .6.4 1 1 1h4c.6 0 1-.4 1-1V10c0-.6-.4-1-1-1zm-8-8h-4c-.6 0-1 .4-1 1v20c0 .6.4 1 1 1h4c.6 0 1-.4 1-1V2c0-.6-.4-1-1-1z" />
                        </svg>
                        <span class="font-medium">{{ number_format($product->impressions) }}</span>
                    </div>

                    @if($isPromoted || $product->is_premium)
                        <span class="inline-flex items-center text-gray-400 rounded text-xs mr-2">
                            <span class="py-1 font-medium">Promoted</span>
                            <a href="/promote-your-software" class="ml-2 text-slate-800 text-xs hover:underline">
                                Advertise with Software on the web
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline-block transform rotate-45"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                            </a>
                        </span>
                    @endif
                    @if(!$isPromoted && !$product->is_premium)
                        <x-product-category-tags :categories="$product->softwareCategories" :withCounts="true" />
                    @endif
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
    <div class="flex-shrink-0 flex items-center gap-2">
        @livewire('product-upvote-button', ['product' => $product], key($product->id))
    </div>
</article>