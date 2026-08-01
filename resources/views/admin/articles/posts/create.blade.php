@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true, 'mainPadding' => 'px-0', 'headerPadding' => 'px-4 sm:px-6 md:px-0'])

@section('title', 'Create Article Post | Software on the Web')
@section('hide_desktop_page_header', 'true')

@section('header-title')
    <div class="w-full md:mx-auto md:max-w-7xl md:px-10 xl:px-12">
        <h1 class="text-xl font-bold text-gray-900">Create New Article Post</h1>
    </div>
@endsection

@section('content')
    @include('articles.partials.editor-form', [
        'article' => $article,
        'categories' => $categories,
        'tags' => $tags,
        'statuses' => $statuses,
        'revisions' => $revisions,
        'context' => $context,
        'formAction' => route('admin.articles.posts.store'),
        'formMethod' => 'POST',
        'cancelUrl' => route('admin.articles.posts.index'),
        'submitLabel' => 'Create Post',
    ])
@endsection
