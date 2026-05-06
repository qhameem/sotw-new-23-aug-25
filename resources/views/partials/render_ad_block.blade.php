@if(isset($ad) && $ad)
    <div class="my-4 p-4 bg-stone-100 rounded-lg ad-listing-item border-l-4 transition-colors hover:bg-stone-200/70" style="border-left-color: var(--color-primary-500);"> {{-- Removed shadow-md --}}
        {{-- You can add a small "Advertisement" label if desired --}}
        {{-- <span class="text-xs text-gray-400  block mb-2 text-right">Advertisement</span> --}}
        @if($ad->type === 'image_banner')
            <a href="{{ route('ads.click', ['ad' => $ad, 'zone' => $zoneSlug ?? $ad->adZones->first()?->slug]) }}" @if($ad->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="group relative block" aria-label="Open {{ $ad->internal_name }} website">
                <img src="{{ $ad->image_url }}" alt="{{ $ad->internal_name }}" class="w-full h-auto object-contain rounded">
                <span class="ad-link-out-icon absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-sm ring-1 ring-gray-200 transition group-hover:bg-white" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8" />
                    </svg>
                </span>
            </a>
        @elseif($ad->type === 'product_listing_card')
            <a href="{{ route('ads.click', ['ad' => $ad, 'zone' => $zoneSlug ?? $ad->adZones->first()?->slug]) }}" @if($ad->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="group flex items-center gap-3 rounded-lg p-1" aria-label="Open {{ $ad->internal_name }} website">
                <img src="{{ $ad->image_url }}" alt="{{ $ad->internal_name }}" class="w-12 h-12 rounded-xl object-cover flex-shrink-0">
                <div class="min-w-0">
                    <div class="flex items-center gap-1 text-sm font-semibold text-gray-900">
                        <span class="truncate">{{ $ad->internal_name }}</span>
                        <span class="ad-link-out-icon inline-flex h-4 w-4 flex-shrink-0 items-center justify-center text-gray-400 transition group-hover:text-gray-700" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8" />
                            </svg>
                        </span>
                    </div>
                    @if($ad->tagline)
                        <p class="text-sm text-gray-700 line-clamp-2">{{ $ad->tagline }}</p>
                    @endif
                </div>
            </a>
        @elseif($ad->type === 'text_link')
            <a href="{{ route('ads.click', ['ad' => $ad, 'zone' => $zoneSlug ?? $ad->adZones->first()?->slug]) }}" @if($ad->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="inline-flex items-center gap-1 text-blue-600 hover:underline " aria-label="Open {{ $ad->internal_name }} website">
                <span>{{ $ad->content }}</span>
                <span class="ad-link-out-icon inline-flex h-4 w-4 items-center justify-center text-blue-500" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8" />
                    </svg>
                </span>
            </a>
        @elseif($ad->type === 'html_snippet')
            {!! $ad->content !!}
        @endif
        <img src="{{ route('ads.impression', ['ad' => $ad, 'zone' => $zoneSlug ?? $ad->adZones->first()?->slug]) }}" alt="" class="hidden" width="1" height="1">
    </div>
@endif
