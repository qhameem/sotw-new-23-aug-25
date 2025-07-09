@php use Illuminate\Support\Str; @endphp
@php
    $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
    $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
@endphp
<article class="p-4 flex items-center gap-3 md:gap-3 transition relative group border rounded hover:bg-gray-50">
    <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo" class="size-14 rounded-lg object-cover flex-shrink-0" loading="lazy" />
    <div class="flex-1">
        <h2 class="text-sm font-semibold leading-tight mb-0.5 flex items-center">
            <span class="text-left">{{ $product->name }}</span>
        </h2>
        <p class="text-gray-800 text-sm md:text-sm mt-0.5 line-clamp-2">{{ $product->tagline }}</p>
        
        <div class="mt-1 flex flex-wrap gap-2 items-center">
            @foreach($product->categories as $cat)
            <a href="{{ route('categories.show', ['category' => $cat->slug]) }}"
                   @click.stop
                   class="hidden sm:block inline-flex items-center text-gray-600  hover:text-gray-800 rounded text-xs">
                    <span class="px-0 py-0 hover:underline">{{ $cat->name }}</span>
                </a>
             @if(!$loop->last)
                <span class="text-gray-400">•</span>
            @endif
            @endforeach
        </div>
    </div>
    <div class="flex-shrink-0">
        <p class="text-sm font-bold">{{ $product->published_at->isFuture() ? 'Scheduled' : 'Published' }}</p>
        <p class="text-sm">{{ $product->published_at->format('F d, Y') }}</p>
    </div>
</article>