@extends('layouts.launch-readiness')

@section('title', $guide['seo_title'])
@section('og_title', $guide['article_title'])
@section('meta_description', $guide['meta_description'])
@section('schema')
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $guide['article_title'],
            'description' => $guide['meta_description'],
            'url' => url()->current(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Software on the Web'),
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <style>
            .lr-guide-content h2 {
                margin-top: 2.5rem;
                font-size: 1.35rem;
                font-weight: 600;
                letter-spacing: -0.01em;
                color: #0f172a;
            }

            .lr-guide-content p,
            .lr-guide-content ul,
            .lr-guide-content ol,
            .lr-guide-content pre {
                margin-top: 1rem;
            }

            .lr-guide-content ul,
            .lr-guide-content ol {
                padding-left: 1.25rem;
            }

            .lr-guide-content ul {
                list-style: disc;
            }

            .lr-guide-content ol {
                list-style: decimal;
            }

            .lr-guide-content li + li {
                margin-top: 0.5rem;
            }

            .lr-guide-content strong {
                font-weight: 600;
                color: #0f172a;
            }

            .lr-guide-content pre {
                overflow-x: auto;
                border-radius: 1rem;
                background: #020617;
                padding: 1rem;
                font-size: 0.875rem;
                line-height: 1.6;
                color: #e2e8f0;
            }

            .lr-guide-content :not(pre) > code {
                border-radius: 0.375rem;
                background: #f1f5f9;
                padding: 0.15rem 0.375rem;
                font-size: 0.92em;
                color: #0f172a;
            }
        </style>

        <div class="rounded-[28px] border border-slate-200 bg-white px-6 py-8 shadow-sm shadow-slate-200/60 sm:px-8 sm:py-10">
            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}" class="underline decoration-slate-300 underline-offset-4 transition hover:text-slate-900 hover:decoration-slate-900">
                    Website Launch Checker
                </a>
                <span aria-hidden="true">/</span>
                <span>{{ $guide['label'] }}</span>
            </div>

            <article class="mt-6">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-[2.1rem]">{{ $guide['article_title'] }}</h1>

                <div class="lr-guide-content mt-6 text-[15px] leading-7 text-slate-700">
                    {!! $guideHtml !!}
                </div>
            </article>

            <div class="mt-10 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-5">
                <p class="text-sm font-medium text-slate-900">Run this check on your own page</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Open the tool and analyze a public URL to see this section inside the full report.</p>
                <a
                    href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}"
                    class="mt-4 inline-flex items-center justify-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-900 transition hover:border-slate-400 hover:bg-white"
                >
                    Back to checker
                </a>
            </div>
        </div>
    </div>
@endsection
