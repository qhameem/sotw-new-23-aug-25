@extends('layouts.launch-readiness')

@section('title', (($report['summary']['page_title'] ?? null) ?: 'Launch Readiness Result').' - Software on the Web')
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
        x-data="launchReadinessAnalyzer({ csrfToken: @js(csrf_token()), initialNotice: @js(! $scan?->save_to_history ? 'This result was not saved to the public history feed. Anyone with this result URL can still open it.' : '') })"
    >
        <section class="pt-4 text-center">
            <div class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm shadow-slate-200/60 ring-1 ring-slate-200">
                <img src="{{ asset('images/tools/launch-readiness-icon.svg') }}" alt="Launch Readiness Checker icon" class="h-12 w-12 rounded-2xl">
            </div>
            <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Launch Readiness Report</h1>
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

        <section class="rounded-[28px] border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60 sm:p-5">
            <form
                method="POST"
                action="{{ route('launch-readiness.analyze', ['toolSlug' => $toolSlug]) }}"
                class="flex flex-col gap-3 lg:flex-row"
                @submit.prevent="submitAnalyze($event)"
            >
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="url" class="sr-only">URL</label>
                    <div class="flex h-12 items-center rounded-2xl border border-slate-200 bg-slate-50 px-4">
                        <svg class="mr-3 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.25 2A1.75 1.75 0 0 1 18 3.75v12.5A1.75 1.75 0 0 1 16.25 18H3.75A1.75 1.75 0 0 1 2 16.25V3.75A1.75 1.75 0 0 1 3.75 2h12.5ZM5 6.75A.75.75 0 0 0 5.75 7.5h8.5a.75.75 0 0 0 0-1.5h-8.5A.75.75 0 0 0 5 6.75Zm0 3.5a.75.75 0 0 0 .75.75h8.5a.75.75 0 0 0 0-1.5h-8.5a.75.75 0 0 0-.75.75Zm.75 4.25a.75.75 0 0 1 0-1.5h5a.75.75 0 0 1 0 1.5h-5Z" clip-rule="evenodd" />
                        </svg>
                        <input id="url" name="url" type="text" value="{{ old('url', $scan?->submitted_url) }}" placeholder="https://your-site.com" class="h-full w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="submit"
                        :disabled="submitting"
                        :class="submitting ? 'cursor-wait bg-slate-700' : 'hover:bg-slate-800'"
                        class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-slate-900 px-6 text-sm font-semibold text-white transition disabled:opacity-100"
                    >
                        <svg
                            x-cloak
                            x-show="submitting"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                        </svg>
                        <span x-text="submitting ? 'Analyzing...' : 'Analyze'"></span>
                    </button>

                    <label class="inline-flex h-12 items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 text-sm text-slate-700" :class="submitting ? 'opacity-60 pointer-events-none' : ''">
                        <span>Save to history</span>
                        <span class="relative inline-flex items-center">
                            <input type="hidden" name="save_to_history" value="0">
                            <input type="checkbox" name="save_to_history" value="1" checked class="peer sr-only">
                            <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-slate-900"></span>
                            <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
            </form>
        </section>

        <div x-ref="reportContainer">
            @include('tools.launch-readiness.partials.report', ['report' => $report])
        </div>
    </div>
@endsection
