@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true])

@section('title', 'Edit Article | Software on the Web')

@section('header-title')
    <h1 class="text-xl font-bold text-gray-900">Edit Article</h1>
@endsection

@section('content')
    @include('articles.partials.editor-form', [
        'article' => $article,
        'categories' => $categories,
        'tags' => $tags,
        'statuses' => $statuses,
        'revisions' => $revisions,
        'context' => $context,
        'formAction' => route('articles.update', ['article' => $article->id]),
        'formMethod' => 'PUT',
        'cancelUrl' => route('articles.my'),
        'submitLabel' => 'Save Changes',
    ])
@endsection
