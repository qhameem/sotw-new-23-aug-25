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
            <div class="flex flex-col items-center gap-2.5 sm:flex-row sm:justify-center sm:gap-3">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-3xl">
                    <img src="{{ $toolBrandingLogoUrl }}" alt="{{ $toolBrandingSiteName }} logo" class="h-12 w-12 object-contain">
                </div>
                <h1 class="text-[28.7px] font-semibold tracking-tight text-slate-900 sm:text-[34.7px]">{{ $toolHomepageH1 }}</h1>
            </div>
            <p class="mx-auto mt-3 max-w-2xl text-xs leading-6 text-slate-500 sm:text-sm">
                Run a lightweight homepage audit for technical, SEO, trust, and AI visibility signals before launch day.
            </p>
        </section>

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div x-cloak x-show="errorMessage" x-text="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
        <div x-cloak x-show="noticeMessage" x-text="noticeMessage" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"></div>

        @unless($toolTablesReady ?? false)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                The tool database tables are not migrated yet. Run the new migration before using Analyze.
            </div>
        @endunless

        @include('tools.launch-readiness.partials.analyze-form', ['submittedUrl' => old('url')])

        <div x-ref="reportContainer">
            @include('tools.launch-readiness.partials.report', ['report' => $report])
        </div>

        @include('tools.launch-readiness.partials.share-modal')
    </div>
@endsection
