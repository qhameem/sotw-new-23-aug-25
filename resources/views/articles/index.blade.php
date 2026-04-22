@extends('layouts.app')

@section('title', 'Articles | Software on the Web')

@section('header-title')
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-stone-950">{{ __('Articles') }}</h1>
        <p class="mt-1 text-sm text-stone-500">Stories, walkthroughs, and practical thinking from the Software on the Web community.</p>
    </div>
@endsection

@section('below_header')
    <nav class="overflow-x-auto pb-1">
        <div class="flex min-w-max items-center gap-6 border-b border-stone-200 text-sm">
            <a
                href="{{ route('articles.index') }}"
                class="relative -mb-px border-b-2 px-1 pb-4 pt-1 transition {{ $feed === 'latest' ? 'border-stone-900 font-medium text-stone-950' : 'border-transparent text-stone-500 hover:text-stone-900' }}"
            >
                Latest
            </a>
            <a
                href="{{ route('articles.index', ['view' => 'featured']) }}"
                class="relative -mb-px border-b-2 px-1 pb-4 pt-1 transition {{ $feed === 'featured' ? 'border-stone-900 font-medium text-stone-950' : 'border-transparent text-stone-500 hover:text-stone-900' }}"
            >
                Featured
            </a>
            <a
                href="{{ route('articles.index', ['view' => 'popular']) }}"
                class="relative -mb-px border-b-2 px-1 pb-4 pt-1 transition {{ $feed === 'popular' ? 'border-stone-900 font-medium text-stone-950' : 'border-transparent text-stone-500 hover:text-stone-900' }}"
            >
                Popular
            </a>
        </div>
    </nav>
@endsection

@section('right_sidebar_content')
    <div class="space-y-10 md:space-y-12">
        <section>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500">Featured</h2>
                <a href="{{ route('articles.index', ['view' => 'featured']) }}" class="text-xs font-medium text-stone-500 transition hover:text-stone-900">
                    View all
                </a>
            </div>

            <div class="mt-5 space-y-6">
                @forelse($featuredPosts as $featuredPost)
                    @include('articles.partials._sidebar_post_item', ['post' => $featuredPost])
                @empty
                    <p class="text-sm text-stone-500">No featured articles have been selected yet.</p>
                @endforelse
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500">Most Popular</h2>
                <a href="{{ route('articles.index', ['view' => 'popular']) }}" class="text-xs font-medium text-stone-500 transition hover:text-stone-900">
                    Show full list
                </a>
            </div>

            <div class="mt-5 space-y-6">
                @forelse($popularPosts as $index => $popularPost)
                    @include('articles.partials._sidebar_post_item', ['post' => $popularPost, 'rank' => $index + 1])
                @empty
                    <p class="text-sm text-stone-500">Popular stories will appear here once traffic data is available.</p>
                @endforelse
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500">Explore Topics</h2>
                <a href="{{ route('articles.index') }}" class="text-xs font-medium text-stone-500 transition hover:text-stone-900">
                    All articles
                </a>
            </div>

            @if($topicCategories->isNotEmpty())
                <div class="mt-5 flex flex-wrap gap-2.5">
                    @foreach($topicCategories as $topicCategory)
                        <a
                            href="{{ route('articles.category', $topicCategory->slug) }}"
                            class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-700 transition hover:border-stone-300 hover:bg-white hover:text-stone-950"
                        >
                            <span>{{ $topicCategory->name }}</span>
                            <span class="text-xs text-stone-400">{{ $topicCategory->published_articles_count }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="mt-5 text-sm text-stone-500">Topics will appear here as articles are published.</p>
            @endif
        </section>
    </div>
@endsection

@section('content')
    <div class="pb-14">
        @if($posts->isNotEmpty())
            @foreach($posts as $post)
                @include('articles.partials._post_card', ['post' => $post])
            @endforeach

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-stone-300 bg-stone-50 px-6 py-16 text-center">
                <p class="text-base text-stone-600">{{ __('No articles found.') }}</p>
            </div>
        @endif
    </div>
@endsection
