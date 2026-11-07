@extends('layouts.app')

@section('title', e($page->renderedTitle()).' | Software on the Web')

@section('content')
    @if(auth()->user()?->hasRole('admin'))
        @include('site.partials.text-style-editor')
    @endif
    <article @class(['prose max-w-none', 'p-4' => !($blankPageTemplate ?? false)])>
        <h1 class="site-heading-text" data-page-style-key="title" style="{{ \App\Services\CustomPageContent::style(($page->text_styles ?? [])['title'] ?? []) }}">{{ $page->renderedTitle() }}</h1>
        {!! \App\Services\CustomPageContent::render($page) !!}
    </article>
@endsection
