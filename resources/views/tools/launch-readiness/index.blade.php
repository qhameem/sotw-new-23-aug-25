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
        x-data="launchReadinessAnalyzer({ csrfToken: @js(csrf_token()), initialNotice: '', toolName: @js($toolBrandingSiteName) })"
    >
        <section class="pt-4 text-center">
            <h1 class="text-[28.7px] font-semibold tracking-tight text-slate-900 sm:text-[52px]">Website Launch Readiness Checker</h1>
            <p class="mx-auto mt-3 max-w-2xl text-[18px] leading-7 text-slate-500">
                Is your site ready for launch? Audit your homepage for SEO, accessibility, trust, and AI visibility signals. Get passed checks, warnings, errors, and suggested fixes.
            </p>
            <div class="mt-5 inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-800">
                <span class="h-2 w-2 rounded-full bg-green-500 motion-safe:animate-pulse" aria-hidden="true"></span>
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
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
                <a href="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="font-semibold underline underline-offset-2">Recent public scans</a>
                @foreach($recentHistory->take(3) as $scan)
                    <span class="max-w-full truncate rounded-full border border-slate-200 bg-white px-3 py-1">{{ parse_url($scan->final_url ?: $scan->submitted_url, PHP_URL_HOST) }} · {{ $scan->launch_score }}/100</span>
                @endforeach
            </div>
        @endif

        <p x-cloak x-show="submitting" role="status" class="text-sm text-slate-600">Fetching the page and checking metadata, technical settings, and AI visibility signals…</p>

        <div x-ref="reportContainer" aria-live="polite">
            @if(!empty($report['summary']['final_url']))
                @include('tools.launch-readiness.partials.report', ['report' => $report])
            @endif
        </div>

        @include('tools.launch-readiness.partials.checker-guide')

        @include('tools.launch-readiness.partials.share-modal')
    </div>
@endsection
