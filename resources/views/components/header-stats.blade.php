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

<div class="hidden w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block" aria-label="Site statistics">
    <div class="flex w-full items-center divide-x divide-gray-200 py-3 text-xs text-gray-500">
        @if($rating !== null)
            <div class="flex items-center gap-1.5 px-2.5">
                <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-50 text-xs font-bold text-gray-900">
                    <svg viewBox="0 0 36 36" class="absolute inset-0 h-full w-full" aria-hidden="true">
                        <circle cx="18" cy="18" r="14" fill="none" stroke="#e5e7eb" stroke-width="4" />
                        @if($rating > 0)
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#ff450d" stroke-width="4"
                                pathLength="100" stroke-dasharray="{{ $rating }} 100" stroke-linecap="round"
                                transform="rotate(-90 18 18)" />
                        @endif
                    </svg>
                    <span class="relative">{{ round($rating) }}<span class="sr-only"> out of 100</span></span>
                </span>
                <span class="text-left leading-tight">Domain Rating<br>by <a href="https://ahrefs.com/" rel="nofollow" class="font-semibold text-gray-700 underline">Ahrefs</a></span>
            </div>
        @endif
        <span @class(['flex-1 text-left', 'px-2.5' => $rating !== null, 'px-4' => $rating === null]) title="{{ number_format($stats['submitted_products']) }} products submitted">
            <strong class="font-semibold text-gray-900">{{ $compactNumber($stats['submitted_products']) }}</strong>
            <span> products</span>
        </span>
        <span @class(['flex-1 text-left', 'px-2.5' => $rating !== null, 'px-4' => $rating === null]) title="{{ number_format($stats['product_clicks']) }} combined product clicks">
            <strong class="font-semibold text-gray-900">{{ $compactNumber($stats['product_clicks']) }}</strong>
            <span> clicks</span>
        </span>
    </div>

    <div class="border-t border-gray-200 px-4 py-3 text-left text-xs leading-5 text-gray-500">
        <p>
            Read <strong class="font-semibold text-gray-900">{{ $compactNumber($stats['ai_bot_requests']) }}</strong> times by
            <strong class="font-semibold text-gray-900">{{ number_format($stats['ai_systems']) }}</strong> AI systems including
        </p>
        <div class="mt-1 flex flex-wrap items-center justify-start gap-x-2 gap-y-1 font-semibold text-gray-800">
            <span class="inline-flex items-center gap-1"><img src="{{ asset('images/ai/chatgpt.svg') }}" alt="ChatGPT logo" class="h-4 w-4" aria-hidden="true">ChatGPT,</span>
            <span class="inline-flex items-center gap-1"><img src="{{ asset('images/ai/claude.svg') }}" alt="Claude logo" class="h-4 w-4" aria-hidden="true">Claude,</span>
            <span class="inline-flex items-center gap-1"><img src="{{ asset('images/ai/perplexity.svg') }}" alt="Perplexity logo" class="h-4 w-4" aria-hidden="true">Perplexity,</span>
            <span class="inline-flex items-center gap-1"><span class="font-normal text-gray-500">and</span> <img src="{{ asset('images/ai/gemini.svg') }}" alt="Gemini logo" class="h-4 w-4" aria-hidden="true">Gemini</span>
        </div>
    </div>
</div>
