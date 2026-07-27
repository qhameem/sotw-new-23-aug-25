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

        <div x-cloak x-show="errorMessage" x-text="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
        <div x-cloak x-show="noticeMessage" x-text="noticeMessage" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"></div>

        @unless($toolTablesReady ?? false)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                The tool database tables are not migrated yet. Run the new migration before using Analyze.
            </div>
        @endunless

        @include('tools.launch-readiness.partials.analyze-form', ['submittedUrl' => old('url', $scan?->submitted_url)])

        <div x-ref="reportContainer">
            @include('tools.launch-readiness.partials.report', ['report' => $report])
        </div>

        @include('tools.launch-readiness.partials.share-modal')

        <div
            x-cloak
            x-show="fixPromptModalOpen"
            x-transition.opacity
            @keydown.escape.window="closeFixPrompt()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="fix-prompt-title"
        >
            <div @click.outside="closeFixPrompt()" class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="fix-prompt-title" class="text-lg font-semibold text-slate-900">Fix issues with an LLM</h2>
                        <p class="mt-1 text-sm text-slate-500">Copy this scan-specific prompt into ChatGPT, Claude, Gemini, or another coding assistant.</p>
                    </div>
                    <button type="button" @click="closeFixPrompt()" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close prompt">&times;</button>
                </div>
                <textarea x-ref="fixPromptTextarea" x-model="fixPrompt" readonly class="mt-5 min-h-72 w-full flex-1 resize-y rounded-xl border border-slate-200 bg-slate-50 p-4 font-mono text-xs leading-5 text-slate-700 focus:border-slate-300 focus:ring-slate-300"></textarea>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="copyFixPrompt()" class="inline-flex h-10 items-center rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800" x-text="fixPromptCopied ? 'Copied' : 'Copy prompt'"></button>
                </div>
            </div>
        </div>
    </div>
@endsection
