@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true])

@section('title', 'Create Article Post | Software on the Web')

@section('header-title')
    <h1 class="text-xl font-bold text-gray-900">Create New Article Post</h1>
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
