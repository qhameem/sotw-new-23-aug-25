@php
    $summary = $report['summary'] ?? [];
    $statusClasses = [
        'pass' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'fail' => 'border-rose-200 bg-rose-50 text-rose-700',
        'pending' => 'border-slate-200 bg-slate-50 text-slate-500',
    ];
    $scoreClasses = static function (?int $score): string {
        if ($score === null) {
            return 'border-slate-200 bg-slate-50 text-slate-900';
        }

        return match (true) {
            $score >= 90 => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            $score >= 75 => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-rose-200 bg-rose-50 text-rose-700',
        };
    };

    $launchScore = isset($report['launch_score']) ? (int) $report['launch_score'] : null;
    $seoScore = isset($report['seo_score']) ? (int) $report['seo_score'] : null;
    $aiScore = isset($report['ai_score']) ? (int) $report['ai_score'] : null;
@endphp

<div class="space-y-6">
    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-full border text-lg font-semibold {{ $scoreClasses($launchScore) }}">
                    {{ $launchScore ?? 0 }}
                </div>
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ $report['status_label'] ?? 'Awaiting scan' }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $summary['fatal_error'] ?: 'Enter a URL and run an audit.' }}
                    </p>
                    @if(!empty($summary['final_url']))
                        <p class="mt-2 text-xs text-slate-400">{{ $summary['final_url'] }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 sm:gap-8">
                <div class="text-center">
                    <div class="text-2xl font-semibold text-slate-900">{{ $report['passed_checks'] ?? 0 }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.22em] text-slate-400">Passed</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-amber-500">{{ $report['warning_checks'] ?? 0 }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.22em] text-slate-400">Warnings</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-semibold text-rose-500">{{ $report['failed_checks'] ?? 0 }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.22em] text-slate-400">Errors</div>
                </div>
            </div>
        </div>

        @if(!empty($summary['final_url']))
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border px-4 py-3 {{ $scoreClasses($launchScore) }}">
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Launch</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $launchScore }}</p>
                </div>
                <div class="rounded-2xl border px-4 py-3 {{ $scoreClasses($seoScore) }}">
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">SEO</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $seoScore }}</p>
                </div>
                <div class="rounded-2xl border px-4 py-3 {{ $scoreClasses($aiScore) }}">
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">AI Visibility</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $aiScore }}</p>
                </div>
            </div>
        @endif
    </section>

    @foreach(($report['categories'] ?? []) as $category)
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
            <div class="mb-5 flex items-center gap-2">
                <div class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06L9.25 10.69 7.78 9.22a.75.75 0 1 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h2 class="text-sm font-semibold text-slate-900">{{ $category['label'] }}</h2>
            </div>

            <div class="space-y-4">
                @foreach($category['checks'] as $check)
                    <div class="border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-200 text-[10px] text-slate-500">
                                        <svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-7.25-3.5a.75.75 0 0 0-1.5 0v4a.75.75 0 0 0 .22.53l2 2a.75.75 0 0 0 1.06-1.06l-1.78-1.78V6.5Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <p class="text-sm font-medium text-slate-900">{{ $check['label'] }}</p>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $check['summary'] }}</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-3 py-1 text-xs font-medium {{ $statusClasses[$check['status']] ?? $statusClasses['pending'] }}">
                                {{ ucfirst($check['status']) }}
                            </span>
                        </div>

                        @if(!empty($check['fix']))
                            <div class="mt-4 rounded-3xl border border-amber-200 bg-amber-50/60 px-4 py-4 sm:px-6">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-amber-300 bg-white text-amber-700">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.486 0l4.58 8.145c.75 1.334-.213 2.993-1.742 2.993H5.419c-1.53 0-2.492-1.66-1.743-2.993l4.58-8.145ZM11 7a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0V7Zm-1 6a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 10 13Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <p class="text-sm font-semibold text-slate-900">How to fix</p>
                                </div>
                                <div class="mt-3">
                                    <p class="inline-flex rounded-2xl border border-amber-200 bg-[#fff7e8] px-3 py-2 font-mono text-sm text-amber-900">
                                        {{ $check['fix'] }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
