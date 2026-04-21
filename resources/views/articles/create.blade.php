@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true])

@section('title', 'Write an Article | Software on the Web')

@section('header-title')
    <h1 class="text-xl font-bold text-gray-900">Write an Article</h1>
@endsection

@section('content')
    @include('articles.partials.editor-form', [
        'article' => $article,
        'categories' => $categories,
        'tags' => $tags,
        'statuses' => $statuses,
        'revisions' => $revisions,
        'context' => $context,
        'formAction' => route('articles.store'),
        'formMethod' => 'POST',
        'cancelUrl' => route('articles.my'),
        'submitLabel' => 'Save Article',
    ])
@endsection
