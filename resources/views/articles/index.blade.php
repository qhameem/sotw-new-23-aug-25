@extends('layouts.app')

@section('title', 'Articles | Software on the Web')

@section('header-title')
    <h1 class="text-xl font-bold text-gray-800">{{ __('Articles') }}</h1>
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
