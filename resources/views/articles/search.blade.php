<x-app-layout :metaTitle="'Search Results for: ' . $query"
              :metaDescription="'Blog posts matching the search term: ' . $query">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800  leading-tight">
                {{ __('Search Results for') }}: <span class="text-primary-600 ">"{{ $query }}"</span>
            </h2>
             {{-- Blog Search Form --}}
            <form action="{{ route('articles.search') }}" method="GET" class="flex">
                <x-text-input type="search" name="query" placeholder="Search articles..." class="py-2 px-3 rounded-l-md" :value="$query" />
                <x-primary-button type="submit" class="rounded-l-none">
                    {{ __('Search') }}
                </x-primary-button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                {{-- Main Content Area --}}
                <div class="md:col-span-9">
                    @if($posts->isNotEmpty())
                        <p class="mb-6 text-gray-700 ">Found {{ $posts->total() }} {{ Str::plural('post', $posts->total()) }} matching your search.</p>
                        @foreach($posts as $post)
                            @include('articles.partials._post_card', ['post' => $post])
                        @endforeach

                        <div class="mt-12">
                            {{-- Append the query string to pagination links --}}
                            {{ $posts->appends(['query' => $query])->links() }}
                        </div>
                    @else
                        <div class="bg-white  shadow-lg rounded-lg p-6">
                            <p class="text-gray-700  text-center">{{ __('No blog posts found matching your search criteria.') }}</p>
                            <p class="text-gray-500  text-center mt-2">Try searching for something else or <a href="{{ route('articles.index') }}" class="text-primary-500 hover:underline">browse all posts</a>.</p>
                        </div>
                    @endif
                </div>

                {{-- Sidebar Area --}}
                <aside class="md:col-span-3 space-y-8">
                    {{-- Categories Widget --}}
                     @php
                        $allCategories = \App\Models\ArticleCategory::withCount(['articles' => function ($query) {
                            $query->published();
                        }])->orderBy('name')->get();
                    @endphp
                    @if($allCategories->where('articles_count', '>', 0)->isNotEmpty())
                    <div class="bg-white  shadow-lg rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900  mb-4">{{ __('Categories') }}</h3>
                        <ul class="space-y-2">
                            @foreach($allCategories as $category)
                                @if($category->articles_count > 0)
                                <li>
                                    <a href="{{ route('articles.category', $category->slug) }}" class="text-gray-700  hover:text-primary-500  transition duration-150 ease-in-out">
                                        {{ $category->name }} ({{ $category->articles_count }})
                                    </a>
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Tags Widget --}}
                     @php
                        $allTags = \App\Models\ArticleTag::withCount(['articles' => function ($query) {
                            $query->published();
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
</x-app-layout>
