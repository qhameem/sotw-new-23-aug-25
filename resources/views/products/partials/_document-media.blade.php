@php
    $isCover = $mode === 'cover';
    $images = $isCover ? $product->media->take(1) : $product->media;
@endphp
<div x-data="{ image: null }" @keydown.escape.window="image = null">
    <div @class(['product-cover' => $isCover, 'product-gallery' => !$isCover]) @if(!$isCover) tabindex="0" role="region" aria-label="Product gallery" @endif>
        @foreach($images as $media)
            @php
                $imageUrl = $media->medium_url ?: $media->url;
                $imageFullUrl = $media->url;
                $imageAlt = $media->alt_text ?: $product->name.' screenshot '.($loop->index + 1);
            @endphp
            <figure class="min-w-0">
                <button type="button" class="block w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    @click="image = {{ Js::from(['src' => $imageFullUrl, 'alt' => $imageAlt]) }}"
                    aria-label="Enlarge {{ $imageAlt }}">
                    <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" loading="{{ $isCover ? 'eager' : 'lazy' }}"
                        @if($isCover) fetchpriority="high" @endif>
                </button>
                @if($isCover)<figcaption>{{ $product->name }} — {{ $product->product_page_tagline ?: $product->tagline }}</figcaption>@endif
            </figure>
        @endforeach
        @if(!$isCover && $product->video_url && $product->getEmbedUrl())
            <iframe src="{{ $product->getEmbedUrl() }}" title="{{ $product->name }} video" loading="lazy" allowfullscreen></iframe>
        @endif
    </div>
    <template x-teleport="body">
        <div x-show="image" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/90 p-4 sm:p-8"
            role="dialog" aria-modal="true" aria-label="Enlarged product image" @click.self="image = null">
            <img :src="image?.src" :alt="image?.alt || ''" class="max-h-full max-w-full rounded-lg object-contain">
            <button type="button" @click="image = null" class="absolute right-4 top-4 rounded-full bg-white px-4 py-2 text-sm text-gray-900" aria-label="Close enlarged image">Close</button>
        </div>
    </template>
</div>
