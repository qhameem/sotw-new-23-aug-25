<div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-500">
    <span>{{ number_format((int) $product->outbound_clicks_count) }} website clicks</span>
    @if($product->published_at)
        <span>Launch week {{ $product->published_at->isoWeek() }}, {{ $product->published_at->isoWeekYear() }}</span>
    @endif
</div>
