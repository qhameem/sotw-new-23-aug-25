@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true])

@section('title', 'Articles | Software on the Web')
@section('meta_description', 'Practical guides, comparisons, and ideas for discovering, evaluating, and launching software.')
@section('canonical')
    <link rel="canonical" href="{{ route('articles.index') }}">
@endsection
@section('hide_desktop_page_header', 'true')

@section('content')
    <div class="min-h-screen bg-[#fafaf8] pb-24 pt-28 text-stone-950 md:pt-12">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
            <header class="border-b border-stone-200 pb-10 sm:pb-12">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600">Software on the Web</p>
                    <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em] text-stone-950 sm:text-5xl lg:text-6xl">Articles</h1>
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-stone-600">
                        Practical guides for discovering, evaluating, and launching better software.
                    </p>
                </div>

                <div class="mt-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <form action="{{ route('articles.search') }}" method="GET" class="relative w-full max-w-xl">
                        <label for="articles-search" class="sr-only">Search articles</label>
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <circle cx="8.5" cy="8.5" r="5.5"></circle>
                            <path d="m13 13 4 4"></path>
                        </svg>
                        <input
                            id="articles-search"
                            type="search"
                            name="query"
                            value="{{ request('query') }}"
                            placeholder="Search articles"
                            class="w-full rounded-full border border-stone-300 bg-white py-3 pl-12 pr-5 text-sm text-stone-900 shadow-none transition placeholder:text-stone-400 focus:border-stone-500 focus:ring-stone-500"
                        >
                    </form>

                    <nav class="flex max-w-full items-center gap-5 overflow-x-auto pb-1 text-sm" aria-label="Article views">
                        <a href="{{ route('articles.index') }}" class="whitespace-nowrap font-medium {{ $feed === 'latest' ? 'text-stone-950' : 'text-stone-500 hover:text-stone-950' }}">Latest</a>
                        <a href="{{ route('articles.index', ['view' => 'featured']) }}" class="whitespace-nowrap font-medium {{ $feed === 'featured' ? 'text-stone-950' : 'text-stone-500 hover:text-stone-950' }}">Featured</a>
                        <a href="{{ route('articles.index', ['view' => 'popular']) }}" class="whitespace-nowrap font-medium {{ $feed === 'popular' ? 'text-stone-950' : 'text-stone-500 hover:text-stone-950' }}">Popular</a>
                    </nav>
                </div>

                @if($topicCategories->isNotEmpty())
                    <nav class="mt-7 flex max-w-full items-center gap-2 overflow-x-auto pb-1" aria-label="Article topics">
                        @foreach($topicCategories as $topicCategory)
                            <a href="{{ route('articles.category', $topicCategory->slug) }}" class="whitespace-nowrap rounded-full border border-stone-200 bg-white px-4 py-2 text-sm text-stone-600 transition hover:border-stone-400 hover:text-stone-950">
                                {{ $topicCategory->name }}
                            </a>
                        @endforeach
                    </nav>
                @endif
            </header>

            @if($leadArticle)
                @php
                    $leadCategory = $leadArticle->categories->first();
                    $leadImage = $leadArticle->display_image_url;
                    $leadReadingTime = max(1, (int) ceil(str_word_count(strip_tags($leadArticle->content ?? '')) / 220));
                @endphp
                <section class="border-b border-stone-200 py-12 sm:py-16" aria-labelledby="featured-article-heading">
                    <a href="{{ route('articles.show', $leadArticle->slug) }}" class="group grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center lg:gap-14">
                        <div class="aspect-[16/9] overflow-hidden rounded-2xl bg-stone-100">
                            @if($leadImage)
                                <img src="{{ $leadImage }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]">
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600">{{ $leadCategory?->name ?? 'Featured' }}</p>
                            <h2 id="featured-article-heading" class="mt-4 text-3xl font-semibold leading-tight tracking-[-0.035em] text-stone-950 transition group-hover:text-primary-700 sm:text-4xl lg:text-5xl">
                                {{ $leadArticle->title }}
                            </h2>
                            <p class="mt-5 text-lg leading-8 text-stone-600">{{ $leadArticle->excerpt }}</p>
                            <div class="mt-6 flex items-center gap-2 text-sm text-stone-500">
                                <span>{{ $leadArticle->author?->name ?? 'Software on the Web' }}</span>
                                <span aria-hidden="true">·</span>
                                <time datetime="{{ $leadArticle->published_at?->toDateString() }}">{{ $leadArticle->published_at?->format('M j, Y') }}</time>
                                <span aria-hidden="true">·</span>
                                <span>{{ $leadReadingTime }} min read</span>
                            </div>
                        </div>
                    </a>
                </section>
            @endif

            <section class="py-12 sm:py-16" aria-labelledby="article-list-heading">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Browse</p>
                        <h2 id="article-list-heading" class="mt-2 text-2xl font-semibold tracking-tight text-stone-950 sm:text-3xl">
                            {{ ucfirst($feed) }} articles
                        </h2>
                    </div>
                    <span class="text-sm text-stone-500">{{ $posts->total() }} {{ Str::plural('article', $posts->total()) }}</span>
                </div>

                @if($posts->isNotEmpty())
                    <div class="mt-9 grid gap-x-8 gap-y-12 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($posts as $post)
                            @include('articles.partials._grid_card', ['post' => $post])
                        @endforeach
                    </div>

                    <div class="mt-14 border-t border-stone-200 pt-8">
                        {{ $posts->links() }}
                    </div>
                @elseif(!$leadArticle)
                    <div class="mt-9 rounded-xl border border-dashed border-stone-300 px-6 py-16 text-center">
                        <p class="text-stone-600">No articles found.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
