@extends('layouts.app')

@section('title', 'Articles | Software on the Web')

@section('header-title')
    <h1 class="text-xl font-bold text-gray-800">{{ __('Articles') }}</h1>
@endsection

@section('actions')
    @auth
    <div class="flex items-center space-x-2">
        <x-add-product-button />
        <a href="{{ route('articles.create') }}" class="inline-flex items-center px-4 py-1 bg-gray-800 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ __('Write Article') }}
        </a>
    </div>
    @endauth
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