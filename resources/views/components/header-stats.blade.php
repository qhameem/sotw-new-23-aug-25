@props(['stats'])
@inject('domainRating', 'App\Services\DomainRatingService')

@php
    $rating = $domainRating->get();
    $compactNumber = static function (int $number): string {
        return match (true) {
            $number >= 1_000_000 => rtrim(rtrim(number_format($number / 1_000_000, 1), '0'), '.').'M',
            $number >= 1_000 => rtrim(rtrim(number_format($number / 1_000, 1), '0'), '.').'K',
            default => number_format($number),
        };
    };
@endphp

<div class="hidden w-full overflow-hidden rounded-xl border border-gray-200 bg-white md:block" aria-label="Site statistics">
    <p class="px-4 py-3 text-sm leading-relaxed text-gray-600">
        Read <strong class="font-semibold text-gray-900">{{ $compactNumber($stats['ai_bot_requests']) }}</strong> times by
        <strong class="font-semibold text-gray-900">{{ number_format($stats['ai_systems']) }}</strong> AI systems including,
        <strong class="inline-flex items-center gap-1 align-middle font-semibold text-gray-900"><img src="{{ asset('images/ai/chatgpt.svg') }}" alt="" class="h-4 w-4">ChatGPT,</strong>
        <strong class="inline-flex items-center gap-1 align-middle font-semibold text-gray-900"><img src="{{ asset('images/ai/claude.svg') }}" alt="" class="h-4 w-4">Claude,</strong>
        <strong class="inline-flex items-center gap-1 align-middle font-semibold text-gray-900"><img src="{{ asset('images/ai/perplexity.svg') }}" alt="" class="h-4 w-4">Perplexity,</strong>
        and <strong class="inline-flex items-center gap-1 align-middle font-semibold text-gray-900"><img src="{{ asset('images/ai/gemini.svg') }}" alt="" class="h-4 w-4">Gemini</strong>
    </p>

    <div @class([
        'grid divide-x divide-gray-200 border-t border-gray-200 text-xs text-gray-600',
        'grid-cols-[minmax(0,3fr)_minmax(0,2fr)_minmax(0,2fr)]' => $rating !== null,
        'grid-cols-2' => $rating === null,
    ])>
        @if($rating !== null)
            <div class="flex items-center gap-2 px-3 py-3">
                <span class="relative flex h-9 w-9 shrink-0 items-center justify-center text-sm font-semibold text-gray-900">
                    @if($rating > 0)
                        <svg viewBox="0 0 36 36" class="absolute inset-0 h-full w-full" aria-hidden="true">
                            <circle cx="18" cy="18" r="15" fill="none" stroke="#e2361b" stroke-width="3.5"
                                pathLength="100" stroke-dasharray="{{ $rating }} 100" stroke-linecap="round"
                                transform="rotate(-90 18 18)" />
                        </svg>
                    @endif
                    <span>{{ round($rating) }}<span class="sr-only"> out of 100</span></span>
                </span>
                <span class="whitespace-nowrap text-[10px] leading-tight">Domain Rating<br>by <a href="https://ahrefs.com/" target="_blank" rel="nofollow noopener" class="text-gray-900 underline">Ahrefs</a></span>
            </div>
        @endif
        <div class="px-3 py-3" title="{{ number_format($stats['submitted_products']) }} products submitted">
            <strong class="block text-xl font-semibold leading-tight text-gray-900">{{ $compactNumber($stats['submitted_products']) }}</strong>
            <span>products</span>
        </div>
        <div class="px-3 py-3" title="{{ number_format($stats['product_clicks']) }} combined product clicks">
            <strong class="block text-xl font-semibold leading-tight text-gray-900">{{ $compactNumber($stats['product_clicks']) }}</strong>
            <span>clicks</span>
        </div>
    </div>
</div>
