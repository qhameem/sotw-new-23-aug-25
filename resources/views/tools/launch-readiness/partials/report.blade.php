@php
    $summary = $report['summary'] ?? [];
    $statusClasses = [
        'pass' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'fail' => 'border-rose-200 bg-rose-50 text-rose-700',
        'pending' => 'border-slate-200 bg-slate-50 text-slate-500',
    ];
    $iconClasses = [
        'pass' => 'text-emerald-700',
        'warning' => 'text-orange-400',
        'fail' => 'text-rose-700',
        'pending' => 'text-slate-500',
    ];
    $scoreClasses = static function (?int $score): string {
        if ($score === null) {
            return 'border-slate-200 bg-slate-50 text-slate-900';
        }

        return match (true) {
            $score >= 90 => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            $score >= 75 => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-[#f4d4a2] bg-[#fff8eb] text-[#cf7f08]',
        };
    };

    $launchScore = isset($report['launch_score']) ? (int) $report['launch_score'] : null;
    $seoScore = isset($report['seo_score']) ? (int) $report['seo_score'] : null;
    $aiScore = isset($report['ai_score']) ? (int) $report['ai_score'] : null;
    $awaitingScan = empty($summary['final_url']);
    $topScoreClasses = static function (?int $score, bool $awaitingScan): string {
        if ($awaitingScan || $score === null) {
            return 'border-[3px] border-solid border-gray-200 bg-slate-50 text-slate-500';
        }

        return match (true) {
            $score >= 90 => 'border-[3px] border-solid border-emerald-700 bg-emerald-50 text-emerald-700',
            $score >= 75 => 'border-[3px] border-solid border-amber-600 bg-amber-50 text-amber-700',
            $score >= 55 => 'border-[3px] border-solid border-orange-500 bg-orange-50 text-orange-600',
            default => 'border-[3px] border-solid border-rose-500 bg-rose-50 text-rose-600',
        };
    };
    $completedChecks = (int) (($report['passed_checks'] ?? 0) + ($report['warning_checks'] ?? 0) + ($report['failed_checks'] ?? 0));
    $scannedAt = !empty($summary['scanned_at']) ? \Illuminate\Support\Carbon::parse($summary['scanned_at']) : null;
    $totalTestTimeSeconds = isset($summary['total_test_time_seconds']) ? (float) $summary['total_test_time_seconds'] : null;
    $postScanSummaryParts = [];

    if ($scannedAt) {
        $postScanSummaryParts[] = 'Scanned '.$scannedAt->diffForHumans();
    }

    if ($totalTestTimeSeconds !== null) {
        $postScanSummaryParts[] = number_format($totalTestTimeSeconds, 2).'s total test time';
    }

    if ($completedChecks > 0) {
        $postScanSummaryParts[] = $completedChecks.' checks completed';
    }

    $postScanSummary = implode(' • ', $postScanSummaryParts);
    $categoryIconColors = [
        'meta_information' => 'text-slate-400',
        'content_structure' => 'text-slate-400',
        'technical_optimization' => 'text-slate-400',
        'accessibility_basics' => 'text-slate-400',
        'social_and_rich_results' => 'text-slate-400',
        'links_analysis' => 'text-slate-400',
        'ai_and_launch_signals' => 'text-slate-400',
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-full {{ $topScoreClasses($launchScore, $awaitingScan) }} text-xl font-semibold">
                    {{ $launchScore ?? 0 }}
                </div>
                <div>
                    <p x-cloak x-show="submitting" class="text-lg font-semibold text-slate-900">Running tests...</p>
                    <p x-show="!submitting" class="text-lg font-semibold text-slate-900">{{ $report['status_label'] ?? 'Awaiting scan' }}</p>
                    <p x-cloak x-show="submitting" class="mt-0.5 text-xs leading-5 text-slate-400">
                        Running parallel tests...
                    </p>
                    <p x-show="!submitting" class="mt-0.5 text-xs leading-5 text-slate-400">
                        {{ $summary['fatal_error'] ?: ($postScanSummary !== '' ? $postScanSummary : 'Enter a URL and run an audit.') }}
                    </p>
                    @if(!empty($summary['final_url']))
                        <p x-show="!submitting" class="mt-1 text-xs text-slate-500">{{ $summary['final_url'] }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between gap-4 sm:gap-6">
                <div class="flex-1 text-center">
                    <div class="text-2xl font-semibold text-slate-900">{{ $report['passed_checks'] ?? 0 }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">Passed</div>
                </div>
                <div class="h-12 w-px shrink-0 bg-slate-200"></div>
                <div class="flex-1 text-center">
                    <div class="text-2xl font-semibold text-amber-500">{{ $report['warning_checks'] ?? 0 }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">Warnings</div>
                </div>
                <div class="h-12 w-px shrink-0 bg-slate-200"></div>
                <div class="flex-1 text-center">
                    <div class="text-2xl font-semibold text-rose-500">{{ $report['failed_checks'] ?? 0 }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">Errors</div>
                </div>
            </div>
        </div>

        @if(!empty($summary['final_url']))
            <div class="mt-5 grid gap-2.5 sm:grid-cols-3">
                <div class="rounded-xl border px-3 py-2.5 {{ $scoreClasses($launchScore) }}">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Launch</p>
                    <p class="mt-1.5 text-xl font-semibold">{{ $launchScore }}</p>
                </div>
                <div class="rounded-xl border px-3 py-2.5 {{ $scoreClasses($seoScore) }}">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">SEO</p>
                    <p class="mt-1.5 text-xl font-semibold">{{ $seoScore }}</p>
                </div>
                <div class="rounded-xl border px-3 py-2.5 {{ $scoreClasses($aiScore) }}">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">AI Visibility</p>
                    <p class="mt-1.5 text-xl font-semibold">{{ $aiScore }}</p>
                </div>
            </div>
        @endif
    </section>

    @foreach(($report['categories'] ?? []) as $category)
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="px-5 pb-5 pt-5 sm:px-6 sm:pb-5 sm:pt-6">
                <div class="flex items-center gap-2">
                <div class="inline-flex h-5 w-5 items-center justify-center {{ $categoryIconColors[$category['key']] ?? 'text-slate-500' }}">
                    @if(($category['key'] ?? null) === 'meta_information')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15.5 9L15.6716 9.17157C17.0049 10.5049 17.6716 11.1716 17.6716 12C17.6716 12.8284 17.0049 13.4951 15.6716 14.8284L15.5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M13.2939 7.17041L11.9998 12L10.7058 16.8297" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M8.50019 9L8.32861 9.17157C6.99528 10.5049 6.32861 11.1716 6.32861 12C6.32861 12.8284 6.99528 13.4951 8.32861 14.8284L8.50019 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"></path>
                        </svg>
                    @elseif(($category['key'] ?? null) === 'content_structure')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2 7.22876 2 11 2H13C16.7712 2 18.6569 2 19.8284 3.17157C21 4.34315 21 6.22876 21 10V14C21 17.7712 21 19.6569 19.8284 20.8284C18.6569 22 16.7712 22 13 22H11C7.22876 22 5.34315 22 4.17157 20.8284C3 19.6569 3 17.7712 3 14V10Z" stroke="currentColor" stroke-width="1.5"></path>
                            <path d="M8 12H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M8 8H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M8 16H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                    @elseif(($category['key'] ?? null) === 'technical_optimization')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                            <path d="M13.7654 2.15224C13.3978 2 12.9319 2 12 2C11.0681 2 10.6022 2 10.2346 2.15224C9.74457 2.35523 9.35522 2.74458 9.15223 3.23463C9.05957 3.45834 9.0233 3.7185 9.00911 4.09799C8.98826 4.65568 8.70226 5.17189 8.21894 5.45093C7.73564 5.72996 7.14559 5.71954 6.65219 5.45876C6.31645 5.2813 6.07301 5.18262 5.83294 5.15102C5.30704 5.08178 4.77518 5.22429 4.35436 5.5472C4.03874 5.78938 3.80577 6.1929 3.33983 6.99993C2.87389 7.80697 2.64092 8.21048 2.58899 8.60491C2.51976 9.1308 2.66227 9.66266 2.98518 10.0835C3.13256 10.2756 3.3397 10.437 3.66119 10.639C4.1338 10.936 4.43789 11.4419 4.43786 12C4.43783 12.5581 4.13375 13.0639 3.66118 13.3608C3.33965 13.5629 3.13248 13.7244 2.98508 13.9165C2.66217 14.3373 2.51966 14.8691 2.5889 15.395C2.64082 15.7894 2.87379 16.193 3.33973 17C3.80568 17.807 4.03865 18.2106 4.35426 18.4527C4.77508 18.7756 5.30694 18.9181 5.83284 18.8489C6.07289 18.8173 6.31632 18.7186 6.65204 18.5412C7.14547 18.2804 7.73556 18.27 8.2189 18.549C8.70224 18.8281 8.98826 19.3443 9.00911 19.9021C9.02331 20.2815 9.05957 20.5417 9.15223 20.7654C9.35522 21.2554 9.74457 21.6448 10.2346 21.8478C10.6022 22 11.0681 22 12 22C12.9319 22 13.3978 22 13.7654 21.8478C14.2554 21.6448 14.6448 21.2554 14.8477 20.7654C14.9404 20.5417 14.9767 20.2815 14.9909 19.902C15.0117 19.3443 15.2977 18.8281 15.781 18.549C16.2643 18.2699 16.8544 18.2804 17.3479 18.5412C17.6836 18.7186 17.927 18.8172 18.167 18.8488C18.6929 18.9181 19.2248 18.7756 19.6456 18.4527C19.9612 18.2105 20.1942 17.807 20.6601 16.9999C21.1261 16.1929 21.3591 15.7894 21.411 15.395C21.4802 14.8691 21.3377 14.3372 21.0148 13.9164C20.8674 13.7243 20.6602 13.5628 20.3387 13.3608C19.8662 13.0639 19.5621 12.558 19.5621 11.9999C19.5621 11.4418 19.8662 10.9361 20.3387 10.6392C20.6603 10.4371 20.8675 10.2757 21.0149 10.0835C21.3378 9.66273 21.4803 9.13087 21.4111 8.60497C21.3592 8.21055 21.1262 7.80703 20.6602 7C20.1943 6.19297 19.9613 5.78945 19.6457 5.54727C19.2249 5.22436 18.693 5.08185 18.1671 5.15109C17.9271 5.18269 17.6837 5.28136 17.3479 5.4588C16.8545 5.71959 16.2644 5.73002 15.7811 5.45096C15.2977 5.17191 15.0117 4.65566 14.9909 4.09794C14.9767 3.71848 14.9404 3.45833 14.8477 3.23463C14.6448 2.74458 14.2554 2.35523 13.7654 2.15224Z" stroke="currentColor" stroke-width="1.5"></path>
                        </svg>
                    @elseif(($category['key'] ?? null) === 'accessibility_basics')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"></circle>
                            <path d="M14 7C14 8.10457 13.1046 9 12 9C10.8954 9 10 8.10457 10 7C10 5.89543 10.8954 5 12 5C13.1046 5 14 5.89543 14 7Z" stroke="currentColor" stroke-width="1.5"></path>
                            <path d="M18 10C18 10 14.4627 11.5 12 11.5C9.53727 11.5 6 10 6 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M12 12V13.4522M12 13.4522C12 14.0275 12.1654 14.5906 12.4765 15.0745L15 19M12 13.4522C12 14.0275 11.8346 14.5906 11.5235 15.0745L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                    @elseif(($category['key'] ?? null) === 'social_and_rich_results')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14 2C14 2.74028 13.5978 3.38663 13 3.73244V4H20C21.6569 4 23 5.34315 23 7V19C23 20.6569 21.6569 22 20 22H4C2.34315 22 1 20.6569 1 19V7C1 5.34315 2.34315 4 4 4H11V3.73244C10.4022 3.38663 10 2.74028 10 2C10 0.895431 10.8954 0 12 0C13.1046 0 14 0.895431 14 2ZM4 6H11H13H20C20.5523 6 21 6.44772 21 7V19C21 19.5523 20.5523 20 20 20H4C3.44772 20 3 19.5523 3 19V7C3 6.44772 3.44772 6 4 6ZM15 11.5C15 10.6716 15.6716 10 16.5 10C17.3284 10 18 10.6716 18 11.5C18 12.3284 17.3284 13 16.5 13C15.6716 13 15 12.3284 15 11.5ZM16.5 8C14.567 8 13 9.567 13 11.5C13 13.433 14.567 15 16.5 15C18.433 15 20 13.433 20 11.5C20 9.567 18.433 8 16.5 8ZM7.5 10C6.67157 10 6 10.6716 6 11.5C6 12.3284 6.67157 13 7.5 13C8.32843 13 9 12.3284 9 11.5C9 10.6716 8.32843 10 7.5 10ZM4 11.5C4 9.567 5.567 8 7.5 8C9.433 8 11 9.567 11 11.5C11 13.433 9.433 15 7.5 15C5.567 15 4 13.433 4 11.5ZM10.8944 16.5528C10.6474 16.0588 10.0468 15.8586 9.55279 16.1056C9.05881 16.3526 8.85858 16.9532 9.10557 17.4472C9.68052 18.5971 10.9822 19 12 19C13.0178 19 14.3195 18.5971 14.8944 17.4472C15.1414 16.9532 14.9412 16.3526 14.4472 16.1056C13.9532 15.8586 13.3526 16.0588 13.1056 16.5528C13.0139 16.7362 12.6488 17 12 17C11.3512 17 10.9861 16.7362 10.8944 16.5528Z" fill="currentColor"></path>
                        </svg>
                    @elseif(($category['key'] ?? null) === 'ai_and_launch_signals')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 4C5.79086 4 4 5.79086 4 8V16C4 18.2091 5.79086 20 8 20H16C18.2091 20 20 18.2091 20 16V8C20 5.79086 18.2091 4 16 4H13.5L12 2L10.5 4H8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"></path>
                            <circle cx="9.5" cy="11.5" r="1.5" fill="currentColor"></circle>
                            <circle cx="14.5" cy="11.5" r="1.5" fill="currentColor"></circle>
                            <path d="M10 15C10.5 15.6667 11.1667 16 12 16C12.8333 16 13.5 15.6667 14 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M12 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                    @elseif(($category['key'] ?? null) === 'links_analysis')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10.0464 14C8.54044 12.4882 8.67609 9.90087 10.3494 8.22108L15.197 3.35462C16.8703 1.67483 19.4476 1.53865 20.9536 3.05046C22.4596 4.56228 22.3239 7.14956 20.6506 8.82935L18.2268 11.2626" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            <path d="M13.9536 10C15.4596 11.5118 15.3239 14.0991 13.6506 15.7789L11.2268 18.2121L8.80299 20.6454C7.12969 22.3252 4.55237 22.4613 3.0464 20.9495C1.54043 19.4377 1.67609 16.8504 3.34939 15.1706L5.77323 12.7373" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                    @else
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06L9.25 10.69 7.78 9.22a.75.75 0 1 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                <h2 class="text-sm font-semibold text-slate-900">{{ $category['label'] }}</h2>
                </div>
            </div>

            <div class="border-t border-slate-100 px-5 pb-5 pt-5 sm:px-6 sm:pb-6">
                <div class="space-y-4">
                @foreach($category['checks'] as $check)
                    <div class="pb-4 last:pb-0">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-0.5">
                                    <span class="inline-flex h-5 w-5 items-center justify-center {{ $iconClasses[$check['status']] ?? $iconClasses['pending'] }}">
                                        @if(($check['status'] ?? 'pending') === 'pass')
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.26a1 1 0 0 1-1.42 0L4.29 10.16a1 1 0 0 1 1.414-1.414l3.096 3.096 6.49-6.546a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                            </svg>
                                        @elseif(($check['status'] ?? 'pending') === 'warning')
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 16.99V17M12 7V14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                        @elseif(($check['status'] ?? 'pending') === 'fail')
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M12 6V12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M16.24 16.24L12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        @endif
                                    </span>
                                    <p class="text-sm font-medium text-slate-900">{{ $check['label'] }}</p>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $check['summary'] }}</p>
                                @if(!empty($check['meta']['preview_url']))
                                    @if(($check['key'] ?? null) === 'open_graph_image')
                                        <div class="mt-3 w-full overflow-hidden rounded-xl">
                                            <img
                                                src="{{ $check['meta']['preview_url'] }}"
                                                alt="{{ $check['label'] }} preview"
                                                class="w-full rounded-xl object-cover"
                                                loading="lazy"
                                            >
                                        </div>
                                    @else
                                        <div class="mt-3 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-3">
                                            <img
                                                src="{{ $check['meta']['preview_url'] }}"
                                                alt="{{ $check['label'] }} preview"
                                                class="h-10 w-10 rounded-xl object-contain"
                                                loading="lazy"
                                            >
                                        </div>
                                    @endif
                                @endif
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-3 py-1 text-xs font-medium {{ $statusClasses[$check['status']] ?? $statusClasses['pending'] }}">
                                {{ ucfirst($check['status']) }}
                            </span>
                        </div>

                        @if(!empty($check['fix']))
                            <div class="mt-4 rounded-xl border border-amber-200 bg-yellow-50/25 px-2 py-3 sm:px-6">
                                <div class="flex items-center gap-0.5">
                                    <span class="inline-flex h-5 w-5 items-center justify-center text-slate-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M9 18H15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                            <path d="M10 21H14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                            <path d="M12 3C8.68629 3 6 5.68629 6 9C6 11.025 7.00225 12.4028 8.08048 13.5619C8.7154 14.2444 9 15.0949 9 16V16.5C9 17.3284 9.67157 18 10.5 18H13.5C14.3284 18 15 17.3284 15 16.5V16C15 15.0949 15.2846 14.2444 15.9195 13.5619C16.9978 12.4028 18 11.025 18 9C18 5.68629 15.3137 3 12 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                            <path d="M9.5 9C9.5 7.61929 10.6193 6.5 12 6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        </svg>
                                    </span>
                                    <p class="text-sm font-regular text-black">How to fix</p>
                                </div>
                                <div class="mt-1">
                                    <div class="inline-flex rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-normal text-amber-800">
                                        {{ $check['fix'] }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
                </div>
            </div>
        </section>
    @endforeach
</div>
