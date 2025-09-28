@extends('layouts.app')

@section('title', 'Articles | Software on the Web')

@section('header-title')
    <div class="flex items-center space-x-4">
        <h1 class="text-xl font-bold text-gray-800">{{ __('Articles') }}</h1>
        @auth
            <a href="{{ route('articles.create') }}" class="inline-flex items-center px-4 py-1 border border-gray-300 rounded-lg font-medium text-sm text-gray-600 hover:bg-gray-50 active:bg-gray-900 focus:outline-none disabled:opacity-25 transition ease-in-out duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {{ __('Write article') }}
            </a>
        @endauth
    </div>
@endsection

@section('actions')
@endsection

@section('content')
    <div>
        @if($posts->isNotEmpty())
            @foreach($posts as $post)
                @include('articles.partials._post_card', ['post' => $post])
            @endforeach

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-700">{{ __('No articles found.') }}</p>
            </div>
        @endif
    </div>
@endsection
