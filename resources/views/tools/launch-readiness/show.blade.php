@extends('layouts.launch-readiness')

@section('title', (($report['summary']['page_title'] ?? null) ?: 'Launch Readiness Result').' - '.$toolBrandingSiteName)
@section('og_title', 'Launch Readiness Report')
@section('meta_description', 'Detailed launch-readiness report with SEO, AI visibility, and technical audit results.')
@section('schema')
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Launch Readiness Report',
            'url' => url()->current(),
            'description' => 'Detailed launch-readiness report with SEO, AI visibility, and technical audit results.',
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
    <div
        class="mx-auto max-w-4xl space-y-8"
        x-data="launchReadinessAnalyzer({ csrfToken: @js(csrf_token()), initialNotice: @js(! $scan?->save_to_history ? 'This result was not saved to the public history feed. Anyone with this result URL can still open it.' : ''), toolName: @js($toolBrandingSiteName) })"
    >
        <section class="pt-4 text-center">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center sm:gap-5">
                <img src="{{ $toolBrandingLogoUrl }}" alt="{{ $toolBrandingSiteName }} logo" class="h-12 w-12 object-contain">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Launch Readiness Report</h1>
            </div>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base">
                Review the current result, then run another scan or browse public history.
            </p>
        </section>

        <div x-cloak x-show="errorMessage" x-text="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
        <div x-cloak x-show="noticeMessage" x-text="noticeMessage" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"></div>

        @unless($toolTablesReady ?? false)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                The tool database tables are not migrated yet. Run the new migration before using Analyze.
            </div>
        @endunless

        @include('tools.launch-readiness.partials.analyze-form', ['submittedUrl' => old('url', $scan?->submitted_url)])

        <div x-ref="reportContainer">
            @include('tools.launch-readiness.partials.report', ['report' => $report])
        </div>

        @include('tools.launch-readiness.partials.share-modal')
    </div>
@endsection
