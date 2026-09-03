@props(['stats'])

@php
    $compactNumber = static function (int $number): string {
        return match (true) {
            $number >= 1_000_000 => rtrim(rtrim(number_format($number / 1_000_000, 1), '0'), '.').'M',
            $number >= 1_000 => rtrim(rtrim(number_format($number / 1_000, 1), '0'), '.').'K',
            default => number_format($number),
        };
    };
@endphp

<div class="hidden shrink-0 items-center divide-x divide-gray-200 rounded-full border border-gray-200 bg-white px-1.5 py-1 text-[11px] text-gray-500 shadow-sm lg:flex" aria-label="Site statistics">
    <span class="px-2" title="{{ number_format($stats['submitted_products']) }} products submitted">
        <strong class="font-semibold text-gray-900">{{ $compactNumber($stats['submitted_products']) }}</strong>
        <span class="text-[10px]"> products</span>
    </span>
    <span class="px-2" title="{{ number_format($stats['product_clicks']) }} combined product clicks">
        <strong class="font-semibold text-gray-900">{{ $compactNumber($stats['product_clicks']) }}</strong>
        <span class="text-[10px]"> clicks</span>
    </span>
    <span class="px-2" title="{{ number_format($stats['ai_bot_requests']) }} identified AI crawler requests from retained server logs">
        <strong class="font-semibold text-primary-600">{{ $compactNumber($stats['ai_bot_requests']) }}</strong>
        <span class="text-[10px]"> AI requests</span>
    </span>
</div>
