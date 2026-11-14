<div class="flex flex-wrap gap-x-5 gap-y-2 text-xs">
    @if($product->published_at)
        <time datetime="{{ $product->published_at->toAtomString() }}" class="inline-flex items-center gap-1.5 !text-inherit">
            <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <path d="M16 3v4M8 3v4M3 10h18" />
            </svg>
            <span><span class="sr-only">Published </span>{{ $product->published_at->format('M j, Y') }}</span>
        </time>
    @endif
    <span class="inline-flex items-center gap-1.5">
        <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 17 6-6 4 4 8-8m-6 0h6v6" />
        </svg>
        {{ number_format((int) $product->outbound_clicks_count) }} website clicks
    </span>
</div>
