@php
    $summary = $report['summary'] ?? [];
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
    $resolvedToolSlug = request()->route('toolSlug') ?? app(\App\Support\ToolSettings::class)->slug(\App\Support\ToolSettings::LAUNCH_READINESS_KEY);
    $guideLibrary = app(\App\Support\LaunchReadinessGuideLibrary::class);
    $promptIssues = collect($report['categories'] ?? [])->flatMap(function (array $category) {
        return collect($category['checks'] ?? [])
            ->filter(fn (array $check) => in_array($check['status'] ?? null, ['warning', 'fail'], true))
            ->map(function (array $check) use ($category) {
                $lines = [
                    '- ['.strtoupper($check['status']).'] '.($category['label'] ?? 'General').' — '.($check['label'] ?? 'Issue'),
                    '  Finding: '.($check['summary'] ?? 'No details provided.'),
                ];

                if (!empty($check['fix'])) {
                    $lines[] = '  Recommended fix: '.$check['fix'];
                }

                return implode("\n", $lines);
            });
    })->values();
    $remediationPrompt = $promptIssues->isEmpty() ? '' : implode("\n", [
        'Act as a senior web engineer specializing in technical SEO, accessibility, and AI-search visibility.',
        '',
        'Website: '.($summary['final_url'] ?? $summary['submitted_url'] ?? 'Unknown'),
        'Launch-readiness score: '.($launchScore ?? 0).'/100',
        '',
        'Fix every issue listed below:',
        $promptIssues->implode("\n\n"),
        '',
        'Requirements:',
        '1. Inspect the existing codebase before changing anything. Do not assume the framework or stack.',
        '2. Implement durable, production-ready fixes while preserving current behavior and design.',
        '3. Avoid duplicate metadata, conflicting structured data, fabricated claims, and keyword stuffing.',
        '4. Add or update relevant automated tests where practical.',
        '5. Validate each fix and report: files changed, what changed, tests run, and any item requiring manual input.',
        '6. If an issue cannot be fixed safely without business context, explain exactly what information is needed.',
    ]);
@endphp

@if(($report['status_label'] ?? null) === 'Waiting to render')
    <div hidden
        x-data
        x-init="
            if (typeof waitForScan !== 'function') {
                submitting = true;
                $nextTick(() => { if ($el.isConnected) submitting = true; });
                const poll = async () => {
                    try {
                        const response = await fetch(@js($pendingResultUrl ?? request()->url()), {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                            signal: AbortSignal.timeout(10000),
                        });
                        if (response.ok && (await response.json()).waiting_to_render === false) {
                            window.location.replace(@js($pendingResultUrl ?? request()->url()));
                            return;
                        }
                    } catch {
                        // Retry temporary network errors.
                    }
                    if ($el.isConnected) setTimeout(poll, 2000);
                };
                setTimeout(poll, 2000);
            }
        ">
    </div>
@elseif($summary['incomplete_reason'] ?? $summary['fatal_error'] ?? (parse_url($summary['final_url'] ?? '', PHP_URL_HOST) === 'consent.google.com' ? 'Google returned a consent page.' : null))
    <section class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-5">
        <h2 class="text-lg font-semibold text-slate-900">Scan incomplete</h2>
        <p>{{ $summary['incomplete_reason'] ?? $summary['fatal_error'] ?? 'Google returned a consent page.' }} Unable to verify page-level checks.</p>
        <p class="break-all text-xs">Submitted: {{ $summary['submitted_url'] ?? $summary['normalized_url'] ?? '' }}</p>
        <p class="break-all text-xs">Final: {{ $summary['final_url'] ?? '' }}</p>
    </section>
@else
<div class="space-y-6">
    @if(!empty($summary['partial']))
        <section class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Partial report</h2>
            <p>Initial HTML checked. Browser rendering didn’t finish. Scores are available after a full scan.</p>
            <button type="button" @click="$refs.readinessForm.requestSubmit()" class="font-semibold underline">
                Retry full scan
            </button>
        </section>
    @else
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 flex-1 items-center gap-4">
                <div class="flex h-24 w-24 shrink-0 flex-col items-center justify-center rounded-full {{ $topScoreClasses($launchScore, $awaitingScan) }} text-3xl font-bold">
                    {{ $launchScore ?? 0 }}
                    <span class="text-xs font-medium">/ 100</span>
                </div>
                <div class="min-w-0">
                    <p x-cloak x-show="submitting" class="text-lg font-semibold text-slate-900">Running tests...</p>
                    <p x-show="!submitting" class="text-lg font-semibold text-slate-900">{{ $report['status_label'] ?? 'Awaiting scan' }}</p>
                    <p x-cloak x-show="submitting" class="mt-0.5 text-xs leading-5 text-slate-400">
                        Fetching the page and running checks…
                    </p>
                    <p x-show="!submitting" class="mt-0.5 text-xs leading-5 text-slate-400">
                        {{ ($summary['fatal_error'] ?? null) ?: ($postScanSummary !== '' ? $postScanSummary : 'Enter a URL and run an audit.') }}
                    </p>
                    @if(!empty($summary['final_url']))
                        @isset($summary['html_bytes'], $summary['page_fetch_seconds'])
                            <p class="mt-1 text-xs text-slate-600">Page fetch {{ $summary['page_fetch_seconds'] }}s · HTML {{ number_format($summary['html_bytes'] / 1024, 1) }} KiB</p>
                        @endisset
                        <p class="mt-1 break-all text-xs text-slate-600">Submitted: {{ $summary['submitted_url'] ?? $summary['normalized_url'] ?? $summary['final_url'] }}</p>
                        <p class="mt-1 break-all text-xs text-slate-600">Final: {{ $summary['final_url'] }}</p>
                    @endif
                </div>
            </div>

            @if($remediationPrompt !== '')
                <div class="shrink-0" x-data="{ copied: false }">
                    <button
                        type="button"
                        @click="
                            try {
                                await navigator.clipboard.writeText(@js($remediationPrompt));
                                copied = true;
                                setTimeout(() => copied = false, 1800);
                            } catch {
                                openFixPrompt(@js($remediationPrompt));
                            }
                        "
                        class="inline-flex min-h-10 items-center rounded-lg border border-[var(--lr-brand-edge)] bg-[var(--lr-brand)] px-4 text-sm font-bold text-[color:var(--lr-button-label)] shadow-[0_2px_0_var(--lr-brand-edge),0_4px_8px_rgba(15,23,42,0.12)] transition duration-150 hover:-translate-y-0.5 active:translate-y-0.5 active:shadow-none focus:outline-none focus:ring-2 focus:ring-[var(--lr-brand)] focus:ring-offset-2"
                    >
                        <span x-show="!copied">Copy fix prompt for AI</span>
                        <span x-cloak x-show="copied" role="status">Copied</span>
                    </button>
                </div>
            @endif
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
    @endif

    @include('tools.launch-readiness.partials.report-checks')
</div>
@endif
