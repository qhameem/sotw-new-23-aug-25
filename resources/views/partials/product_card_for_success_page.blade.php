@php use Illuminate\Support\Str; @endphp
@php
    $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
    $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
@endphp
<article class="p-4 flex items-center gap-3 md:gap-3 transition relative group border rounded hover:bg-gray-50">
    <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo"
        class="w-[65px] h-[65px] rounded-xl object-cover flex-shrink-0" loading="lazy" />
    <div class="flex-1">
        <h2 class="text-sm font-semibold leading-tight mb-0.5 flex items-center">
            <span class="text-left">{{ $product->name }}</span>
        </h2>
        <p class="text-gray-800 text-sm mt-0.5 mb-0 line-clamp-2">{{ $product->tagline }}</p>

        <div class="mt-0.25 flex flex-wrap gap-2 items-center">
            <x-product-category-tags :categories="$product->categories" />
        </div>
    </div>
    <div class="flex-shrink-0">
        <p class="text-sm font-bold">{{ $product->published_at->isFuture() ? 'Scheduled' : 'Published' }}</p>
        <p class="text-sm">{{ $product->published_at->format('F d, Y') }}</p>
    </div>
</article>