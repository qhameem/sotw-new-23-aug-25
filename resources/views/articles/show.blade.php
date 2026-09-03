@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true])

@php
    $description = $post->meta_description ?: $post->excerpt;
    $primaryCategory = $post->categories->first();
    $author = $post->author;
    $authorName = $author?->name ?? 'Software on the Web';
    $authorAvatar = $author?->avatar();
    $imageUrl = $post->display_image_url;
    $readingTime = max(1, (int) ceil(str_word_count(strip_tags($post->content ?? '')) / 220));
    $relatedArticles = $relatedArticles ?? collect();
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->title,
        'description' => $description,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'mainEntityOfPage' => route('articles.show', ['article' => $post->slug]),
        'author' => [
            '@type' => 'Person',
            'name' => $authorName,
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'Software on the Web'),
            'url' => config('app.url'),
        ],
    ];

    if ($imageUrl) {
        $articleSchema['image'] = [$imageUrl];
    }
@endphp

@section('title', $post->meta_title ?: ($post->title . ' | Software on the Web'))
@section('meta_description', $description)
@section('robots', ($isPreview ?? false) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large')
@section('og_type', 'article')
@section('canonical')
    <link rel="canonical" href="{{ route('articles.show', ['article' => $post->slug]) }}">
@endsection
@section('hide_desktop_page_header', 'true')

@push('styles')
    <style>
        .article-prose { color: #292524; font-size: 1.125rem; line-height: 1.8; }
        .article-prose > * + * { margin-top: 1.5em; }
        .article-prose h2 { margin-top: 2.35em; font-size: 1.875rem; line-height: 1.25; letter-spacing: -0.025em; font-weight: 650; color: #1c1917; }
        .article-prose h3 { margin-top: 2em; font-size: 1.45rem; line-height: 1.35; letter-spacing: -0.015em; font-weight: 650; color: #1c1917; }
        .article-prose a { color: var(--color-primary-700); text-decoration: underline; text-decoration-color: color-mix(in srgb, var(--color-primary-700) 35%, transparent); text-underline-offset: 3px; }
        .article-prose blockquote { border-left: 3px solid #d6d3d1; padding-left: 1.25rem; color: #57534e; font-style: italic; }
        .article-prose img { width: 100%; border-radius: .75rem; }
        .article-prose ul { list-style: disc; padding-left: 1.4rem; }
        .article-prose ol { list-style: decimal; padding-left: 1.4rem; }
        .article-prose pre { overflow-x: auto; border-radius: .75rem; background: #1c1917; padding: 1.25rem; color: #fafaf9; font-size: .9rem; line-height: 1.65; }
        .article-prose code:not(pre code) { border-radius: .3rem; background: #f5f5f4; padding: .12rem .35rem; font-size: .92em; }
        .article-prose hr { margin: 3rem 0; border-color: #e7e5e4; }
        @media (min-width: 768px) { .article-prose { font-size: 1.1875rem; } }
    </style>
@endpush

@section('content')
    <script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <div class="min-h-screen bg-[#fafaf8] pb-24 pt-28 text-stone-950 dark:bg-slate-950 dark:text-slate-50 md:pt-12">
        @if($isPreview ?? false)
            <div class="mx-auto mb-8 max-w-5xl px-5 sm:px-8">
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Preview mode. Search engines cannot index this page.
                </div>
            </div>
        @endif

        <article>
            <header class="mx-auto max-w-5xl px-5 sm:px-8">
                <nav class="flex flex-wrap items-center gap-2 text-sm text-stone-500" aria-label="Breadcrumb">
                    <a href="{{ route('articles.index') }}" class="transition hover:text-stone-950">Articles</a>
                    @if($primaryCategory)
                        <span aria-hidden="true">/</span>
                        <a href="{{ route('articles.category', $primaryCategory->slug) }}" class="transition hover:text-stone-950">{{ $primaryCategory->name }}</a>
                    @endif
                </nav>

                <div class="mt-8 max-w-4xl">
                    @if($primaryCategory)
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600">{{ $primaryCategory->name }}</p>
                    @endif
                    <h1 class="mt-4 text-4xl font-semibold leading-[1.08] tracking-[-0.045em] text-stone-950 sm:text-5xl lg:text-6xl">
                        {{ $post->title }}
                    </h1>
                    <p class="mt-6 max-w-3xl text-xl leading-8 text-stone-600">{{ $description }}</p>

                    <div class="mt-8 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-stone-500">
                        @if($authorAvatar)
                            <img src="{{ $authorAvatar }}" alt="" class="h-9 w-9 rounded-full object-cover">
                        @endif
                        <span class="font-medium text-stone-800">{{ $authorName }}</span>
                        <span aria-hidden="true">·</span>
                        <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('M j, Y') }}</time>
                        <span aria-hidden="true">·</span>
                        <span>{{ $readingTime }} min read</span>
                        @if($post->updated_at && $post->published_at && $post->updated_at->gt($post->published_at->copy()->addDay()))
                            <span aria-hidden="true">·</span>
                            <span>Updated {{ $post->updated_at->format('M j, Y') }}</span>
                        @endif
                    </div>
                </div>
            </header>

            @if($imageUrl)
                <figure class="mx-auto mt-12 max-w-6xl px-5 sm:px-8">
                    <div class="aspect-[16/9] overflow-hidden rounded-2xl bg-stone-100">
                        <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                    </div>
                </figure>
            @endif

            <div class="mx-auto mt-12 grid max-w-6xl gap-12 px-5 sm:px-8 lg:grid-cols-[13rem_minmax(0,48rem)] lg:justify-center lg:gap-16">
                <aside class="hidden lg:block">
                    <div class="sticky top-24 border-l border-stone-200 pl-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">On this page</p>
                        <nav class="mt-4 space-y-3 text-sm leading-5 text-stone-500" data-article-toc aria-label="Table of contents"></nav>
                    </div>
                </aside>

                <div class="min-w-0">
                    <div class="article-prose" data-article-content>
                        {!! \App\Support\OutboundLink::sanitizeHtml($post->content, 'article') !!}
                    </div>

                    @if($post->categories->isNotEmpty() || $post->tags->isNotEmpty())
                        <footer class="mt-14 border-t border-stone-200 pt-8">
                            <div class="flex flex-wrap gap-2">
                                @foreach($post->categories as $category)
                                    <a href="{{ route('articles.category', $category->slug) }}" class="rounded-full border border-stone-200 bg-white px-3.5 py-2 text-sm text-stone-600 transition hover:border-stone-400 hover:text-stone-950">{{ $category->name }}</a>
                                @endforeach
                                @foreach($post->tags as $tag)
                                    <a href="{{ route('articles.tag', $tag->slug) }}" class="rounded-full border border-stone-200 bg-white px-3.5 py-2 text-sm text-stone-600 transition hover:border-stone-400 hover:text-stone-950">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        </footer>
                    @endif

                    <section class="mt-12 rounded-2xl border border-stone-200 bg-white p-6 sm:p-8" aria-label="About the author">
                        <div class="flex items-start gap-5">
                            @if($authorAvatar)
                                <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" class="h-14 w-14 rounded-full object-cover">
                            @endif
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Written by</p>
                                <h2 class="mt-2 text-lg font-semibold text-stone-950">{{ $authorName }}</h2>
                                @if($author?->profile?->bio)
                                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $author->profile->bio }}</p>
                                @else
                                    <p class="mt-2 text-sm leading-6 text-stone-600">Contributor at Software on the Web.</p>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </article>

        @if($relatedArticles->isNotEmpty())
            <section class="mx-auto mt-20 max-w-7xl border-t border-stone-200 px-5 pt-12 sm:px-8 lg:px-12" aria-labelledby="related-articles-heading">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Continue reading</p>
                <h2 id="related-articles-heading" class="mt-2 text-2xl font-semibold tracking-tight text-stone-950 sm:text-3xl">Related articles</h2>
                <div class="mt-8 grid gap-x-8 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($relatedArticles as $relatedArticle)
                        @include('articles.partials._grid_card', ['post' => $relatedArticle])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const content = document.querySelector('[data-article-content]');
            const toc = document.querySelector('[data-article-toc]');

            if (!content || !toc) return;

            const headings = [...content.querySelectorAll('h2, h3')];

            if (!headings.length) {
                toc.closest('aside')?.classList.add('lg:hidden');
                return;
            }

            headings.forEach((heading, index) => {
                if (!heading.id) heading.id = `section-${index + 1}`;

                const link = document.createElement('a');
                link.href = `#${heading.id}`;
                link.textContent = heading.textContent;
                link.className = `block transition hover:text-stone-950 ${heading.tagName === 'H3' ? 'pl-3' : ''}`;
                toc.appendChild(link);
            });
        });
    </script>
@endpush
