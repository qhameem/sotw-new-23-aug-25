@extends('layouts.app')

@section('title', e($page->renderedTitle()).' | Software on the Web')

@if(isset($directories) && $directoryFilters)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    @php
        $canEditDirectories = isset($directories) && auth()->user()?->hasRole('admin');
        $useDirectoryBreadcrumb = isset($directories) && strcasecmp(trim($page->renderedTitle()), 'Directories') === 0;
    @endphp
    @if($canEditDirectories && session('directory_status'))
        <p role="status" class="mb-3 text-sm">{{ session('directory_status') }}</p>
    @endif
    @if($canEditDirectories && $errors->any())
        <p role="alert" class="mb-3 text-sm text-red-700">{{ $errors->first() }}</p>
    @endif
    @if(auth()->user()?->hasRole('admin'))
        @include('site.partials.text-style-editor')
    @endif
    <article @class(['prose max-w-none', 'p-4' => !($blankPageTemplate ?? false)])>
        @if($useDirectoryBreadcrumb)
            <nav class="directory-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Directories</span>
            </nav>
        @else
            <h1 class="site-heading-text" data-page-style-key="title" style="{{ \App\Services\CustomPageContent::style(($page->text_styles ?? [])['title'] ?? []) }}">{{ $page->renderedTitle() }}</h1>
        @endif
        @if($canEditDirectories)
            @include('site.directories.content-editor', ['field' => 'title', 'label' => 'title', 'currentValue' => $page->title])
        @endif
        {!! \App\Services\CustomPageContent::render($page) !!}
        @if($canEditDirectories)
            @include('site.directories.content-editor', ['field' => 'body', 'label' => 'page content', 'currentValue' => $page->body])
        @endif
    </article>
    @isset($directories)
        @include('site.directories.catalog', ['editable' => $canEditDirectories])
    @endisset
@endsection
