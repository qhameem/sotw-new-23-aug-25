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

<div class="hidden w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block" aria-label="Site statistics">
    <div class="flex w-full items-center divide-x divide-gray-200 py-3 text-xs text-gray-500">
        <span class="flex-1 px-4 text-left" title="{{ number_format($stats['submitted_products']) }} products submitted">
            <strong class="font-semibold text-gray-900">{{ $compactNumber($stats['submitted_products']) }}</strong>
            <span> products</span>
        </span>
        <span class="flex-1 px-4 text-left" title="{{ number_format($stats['product_clicks']) }} combined product clicks">
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
            <span class="inline-flex items-center gap-1"><img src="{{ asset('images/ai/chatgpt.svg') }}" alt="" class="h-4 w-4" aria-hidden="true">ChatGPT,</span>
            <span class="inline-flex items-center gap-1"><img src="{{ asset('images/ai/claude.svg') }}" alt="" class="h-4 w-4" aria-hidden="true">Claude,</span>
            <span class="inline-flex items-center gap-1"><img src="{{ asset('images/ai/perplexity.svg') }}" alt="" class="h-4 w-4" aria-hidden="true">Perplexity,</span>
            <span class="inline-flex items-center gap-1"><img src="{{ asset('images/ai/gemini.svg') }}" alt="" class="h-4 w-4" aria-hidden="true">Gemini</span>
        </div>
    </div>
</div>
