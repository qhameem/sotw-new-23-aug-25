@extends('layouts.launch-readiness')

@section('title', $toolHomepageTitleTag)
@section('og_title', $toolHomepageTitleTag)
@section('meta_description', $toolHomepageMetaDescription)
@section('schema')
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => $toolBrandingSiteName,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => url()->current(),
            'description' => 'Free homepage audit for launch readiness, SEO basics, and AI visibility signals.',
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Software on the Web'),
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
    <div
        class="mx-auto max-w-4xl space-y-8"
        x-data="launchReadinessAnalyzer({ csrfToken: @js(csrf_token()), initialNotice: '', hasScanResult: @js(!empty($report['summary']['final_url'])), toolName: @js($toolBrandingSiteName) })"
    >
        <section class="pt-4 text-center">
            <h1 class="text-[28.7px] font-semibold tracking-[-1.5px] text-black sm:text-[52px]">Website Launch Readiness Checker</h1>
            <p class="mx-auto mt-1 max-w-2xl text-[18px] leading-7 text-[#4b5563]">
                Check your site’s SEO, accessibility, trust, and AI visibility before launch. Paste your URL to find issues and get clear steps to fix them.
            </p>
            <div class="mt-5 inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-800">
                <span class="relative h-2 w-2 shrink-0 rounded-full bg-green-500" aria-hidden="true">
                    <span class="absolute inset-0 rounded-full bg-green-500 opacity-60 motion-safe:animate-ping motion-reduce:hidden"></span>
                </span>
                <span>Free • No signup required</span>
            </div>
        </section>

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div x-cloak x-show="errorMessage" x-text="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
        <div x-cloak x-show="noticeMessage" x-text="noticeMessage" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"></div>

        @unless($toolTablesReady ?? false)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                The tool database tables are not migrated yet. Run the new migration before using Analyze.
            </div>
        @endunless

        @include('tools.launch-readiness.partials.analyze-form', ['submittedUrl' => old('url')])

        @if(($recentHistory ?? collect())->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600" x-data
                x-init="
                    const row = $el;
                    const fifth = $refs.fifthScan;
                    if (fifth) {
                        const update = () => {
                            fifth.hidden = false;
                            fifth.hidden = fifth.offsetTop !== row.firstElementChild.offsetTop
                                && fifth.getBoundingClientRect().top >= row.firstElementChild.getBoundingClientRect().bottom;
                        };
                        const observer = new ResizeObserver(update);
                        observer.observe(row);
                        document.fonts.ready.then(update);
                    }
                ">
                <a href="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="font-semibold underline underline-offset-2">Recent public scans</a>
                @foreach($recentHistory->take(5) as $scan)
                    @php
                        $host = parse_url($scan->final_url ?: $scan->submitted_url, PHP_URL_HOST);
                        $label = preg_replace('/^www\./i', '', $host ?: $scan->submitted_url);
                        $favicon = collect($scan->audit_payload['categories'] ?? [])
                            ->flatMap(fn (array $category) => $category['checks'] ?? [])
                            ->firstWhere('key', 'favicon');
                        $iconUrl = ($favicon['status'] ?? null) === 'pass' ? ($favicon['meta']['preview_url'] ?? '') : '';
                        $hasIcon = parse_url($iconUrl, PHP_URL_SCHEME) === 'https';
                    @endphp
                    <span @if($loop->iteration === 5) x-ref="fifthScan" hidden @endif
                        class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 [&[hidden]]:hidden">
                        <span x-data="{ iconLoaded: false }" class="relative h-3.5 w-3.5 shrink-0" aria-hidden="true">
                            <span x-show="!iconLoaded">@svg('carbon-globe', 'h-3.5 w-3.5')</span>
                            @if($hasIcon)
                                <img src="{{ $iconUrl }}" alt="" referrerpolicy="no-referrer" x-cloak
                                    x-show="iconLoaded" x-init="iconLoaded = $el.complete && $el.naturalWidth > 0"
                                    x-on:load="iconLoaded = true" x-on:error="iconLoaded = false"
                                    class="absolute inset-0 h-full w-full object-contain">
                            @endif
                        </span>
                        <span class="truncate">{{ $label }}</span>
                        @include('tools.launch-readiness.partials.score-ring', ['score' => $scan->launch_score, 'compact' => true])
                    </span>
                @endforeach
            </div>
        @endif

        <p x-cloak x-show="submitting" role="status" class="text-sm text-slate-600" x-text="scanProgress"></p>

        <div x-ref="reportContainer" aria-live="polite">
            @if(!empty($report['summary']['final_url']))
                @include('tools.launch-readiness.partials.report', ['report' => $report])
            @endif
        </div>

        <div x-show="!hasScanResult" @if(!empty($report['summary']['final_url'])) x-cloak @endif>
            @include('tools.launch-readiness.partials.checker-guide')
        </div>

        @include('tools.launch-readiness.partials.share-modal')
        @include('tools.launch-readiness.partials.fix-prompt-modal')
    </div>
@endsection
