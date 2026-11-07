@extends('layouts.app')

@section('title', e($page->title).' | Software on the Web')

@section('content')
    <article class="prose max-w-none p-4">
        <h1 class="site-heading-text">{{ $page->title }}</h1>
        {!! \Illuminate\Support\Str::markdown($page->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </article>
@endsection
