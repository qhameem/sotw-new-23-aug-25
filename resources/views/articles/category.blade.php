@extends('layouts.app')

@section('title', 'Posts in Category: ' . $category->name . ' | Software on the Web')

@section('meta_description', 'Browse articles categorized under ' . $category->name . ($category->description ? ' - ' . Str::limit($category->description, 120) : ''))

@section('header-title')
    <span>Category Archives: {{ $category->name }}</span>
@endsection

@section('actions')
    <form action="{{ route('articles.search') }}" method="GET" class="flex">
        <x-text-input type="search" name="query" placeholder="Search articles..." class="py-2 px-3 rounded-l-md" :value="request('query')" />
        <x-primary-button type="submit" class="rounded-l-none">
            {{ __('Search') }}
        </x-primary-button>
    </form>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($category->description)
                <p class="mb-6 text-sm text-gray-600">{{ $category->description }}</p>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                <div class="md:col-span-9">
                    @if($posts->isNotEmpty())
                        @foreach($posts as $post)
                            @include('articles.partials._post_card', ['post' => $post])
                        @endforeach

                        <div class="mt-12">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <div class="bg-white  shadow-lg rounded-lg p-6">
                            <p class="text-gray-700  text-center">{{ __('No articles found in this category.') }}</p>
                        </div>
                    @endif
                </div>

                <aside class="md:col-span-3 space-y-8">
                    @php
                        $allCategories = \App\Models\ArticleCategory::withCount(['articles' => function ($query) {
                            $query->where('status', 'published')->where('published_at', '<=', now());
                        }])->orderBy('name')->get();
                    @endphp
                    @if($allCategories->where('articles_count', '>', 0)->isNotEmpty())
                    <div class="bg-white  shadow-lg rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900  mb-4">{{ __('All Categories') }}</h3>
                        <ul class="space-y-2">
                            @foreach($allCategories as $archiveCategory)
                                @if($archiveCategory->articles_count > 0)
                                <li>
                                    <a href="{{ route('articles.category', $archiveCategory->slug) }}"
                                       class="text-gray-700 hover:text-primary-500 transition duration-150 ease-in-out @if($archiveCategory->id === $category->id) font-bold text-primary-500 @endif">
                                        {{ $archiveCategory->name }} ({{ $archiveCategory->articles_count }})
                                    </a>
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @php
                        $allTags = \App\Models\ArticleTag::withCount(['articles' => function ($query) {
                            $query->where('status', 'published')->where('published_at', '<=', now());
                        }])->orderBy('name')->get();
                    @endphp
                    @if($allTags->where('articles_count', '>', 0)->isNotEmpty())
                    <div class="bg-white  shadow-lg rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900  mb-4">{{ __('Popular Tags') }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($allTags as $tag)
                                @if($tag->articles_count > 0)
                                <a href="{{ route('articles.tag', $tag->slug) }}" class="inline-block bg-indigo-100  rounded-full px-3 py-1 text-xs font-semibold text-indigo-700  hover:bg-indigo-200  transition duration-150 ease-in-out">
                                    #{{ $tag->name }} ({{$tag->articles_count}})
                                </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>
@endsection
