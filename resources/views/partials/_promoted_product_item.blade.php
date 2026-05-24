@php
    use App\Support\ProductLogo;
    $logo = ProductLogo::url($product);
    $logoLoadPosition = $logoLoadPosition ?? 1;
    $isPromoted = $product->is_promoted ?? false;
    $impressionSurface = match (true) {
        request()->routeIs('home') => 'home_list',
        request()->routeIs('products.byWeek') => 'week_list',
        request()->routeIs('products.byDate') => 'date_list',
        request()->routeIs('products.byMonth') => 'month_list',
        request()->routeIs('products.byYear') => 'year_list',
        request()->routeIs('categories.show') => 'category_list',
        default => 'product_list',
    };
@endphp
<article class="product-card p-4 flex items-center gap-2 md:gap-1 transition relative group hover:bg-stone-50 rounded-lg"
    data-product-id="{{ $product->id }}"
    data-track-impression="true"
    data-impression-surface="{{ $impressionSurface }}">
    <div class="flex items-center gap-3 flex-1">
        <a href="{{ route('products.show', $product->slug) }}" wire:navigate.hover class="flex items-start md:items-center gap-2">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $product->name }} logo"
                    class="w-12 h-12 rounded-xl object-cover flex-shrink-0 bg-gray-100"
                    width="48" height="48"
                    loading="{{ ProductLogo::loading($logoLoadPosition) }}"
                    fetchpriority="{{ ProductLogo::fetchPriority($logoLoadPosition) }}"
                    decoding="async" />
            @else
                <div class="flex w-12 h-12 rounded-xl bg-gray-100 text-gray-500 items-center justify-center flex-shrink-0 text-sm font-semibold">
                    {{ ProductLogo::initial($product) }}
                </div>
            @endif
            <div class="flex flex-col space-y-0">
                <h2 class="site-heading-text text-sm font-semibold flex items-center leading-none">
                    <span class="site-heading-text text-left text-black">{{ $product->name }}</span>
                    <a href="{{ route('products.click', ['product' => $product->slug, 'surface' => 'product_list']) }}"
                        target="_blank" rel="{{ \App\Support\OutboundLink::rel($product->link, 'product_link') }}" @click.stop
                        class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-all duration-200 rounded-full text-gray-600 hover:text-rose-500 hover:bg-rose-50"
                        aria-label="Open product link in new tab">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </h2>

                <p class="site-body-text text-gray-900 text-sm line-clamp-2">{{ $product->tagline }}</p>

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
                        </span>
                    @endif
                    @if(!$isPromoted && !$product->is_premium)
                        <x-product-category-tags :categories="$product->softwareCategories" :withCounts="true" />
                    @endif
                </div>

                <div class="site-body-text text-xs text-gray-600">
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
        @include('partials.product-upvote-button', ['product' => $product])
    </div>
</article>
