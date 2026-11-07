@extends('layouts.app')

@section('title', e($page->title).' | Software on the Web')

@section('content')
    @if(auth()->user()?->hasRole('admin'))
        <div class="m-4 space-y-3 rounded border p-4" aria-label="Page editing controls">
            <a class="underline" href="{{ route('admin.pages.edit', $page) }}">Edit page content and settings</a>
            @if(session('success'))
                <p role="status">{{ session('success') }}</p>
            @endif
            @if($errors->any())
                <p role="alert">{{ $errors->first() }}</p>
            @endif
            <details>
                <summary class="cursor-pointer">Manage sidebar sections</summary>
                <div class="mt-3 space-y-2">
                    @foreach($page->sidebarSections() as $sectionKey => $sectionEnabled)
                        @include('site.partials.sidebar-section-control', ['sidebarPage' => $page])
                    @endforeach
                </div>
            </details>
        </div>
    @endif
    <article class="prose max-w-none p-4">
        <h1 class="site-heading-text">{{ $page->title }}</h1>
        {!! \Illuminate\Support\Str::markdown($page->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </article>
@endsection
