@extends('layouts.app')

@section('title')
    {{-- This is intentionally left blank to give more prominence to the article's h1 title --}}
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <article class="bg-white  shadow-xl rounded-lg overflow-hidden">
                @if($post->featured_image_path)
                    <img class="w-full h-auto max-h-[500px] object-cover object-center"
                         src="{{ Str::startsWith($post->featured_image_path, ['http://', 'https://']) ? $post->featured_image_path : asset('storage/' . $post->featured_image_path) }}"
                         alt="{{ $post->meta_title ?: $post->title }}">
                @endif

                <div class="p-6 md:p-8 lg:p-10">
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900  mb-4">
                        {{ $post->title }}
                    </h1>
                    
                    <div class="text-base text-gray-600  mb-6">
                        <span>Published on {{ $post->published_at ? $post->published_at->format('F j, Y') : 'Date N/A' }}</span>
                        @if($post->updated_at && $post->published_at && $post->updated_at->gt($post->published_at->addMinutes(5))) {{-- Show updated if significantly different --}}
                            <span class="mx-2">-></span>
                            <span>Updated on {{ $post->updated_at->format('F j, Y') }}</span>
                        @endif
                    </div>

                    {{-- Rich Text Content --}}
                    <div class="prose  max-w-none text-gray-800  text-lg leading-relaxed">
                        {!! $post->content !!} {{-- Make sure content is sanitized if it comes from untrusted WYSIWYG --}}
                    </div>

                    <hr class="my-8 border-gray-200 ">

                    {{-- Categories and Tags --}}
                    <div class="space-y-4">
                        @if($post->categories->isNotEmpty())
                            <div>
                                <span class="font-semibold text-gray-800 ">Categories:</span>
                                @foreach($post->categories as $category)
                                    <a href="{{ route('articles.category', $category->slug) }}" class="ml-2 inline-block bg-gray-200  rounded-full px-3 py-1 text-sm font-semibold text-gray-700  hover:bg-gray-300  transition duration-150 ease-in-out">
                                        {{ $category->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($post->tags->isNotEmpty())
                            <div>
                                <span class="font-semibold text-gray-800 ">Tags:</span>
                                @foreach($post->tags as $tag)
                                    <a href="{{ route('articles.tag', $tag->slug) }}" class="ml-2 inline-block bg-indigo-100  rounded-full px-3 py-1 text-sm font-semibold text-indigo-700  hover:bg-indigo-200  transition duration-150 ease-in-out">
                                        #{{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-10 text-center">
                        <a href="{{ route('articles.index') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out">
                            &larr; Back to Articles
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </div>
@endsection