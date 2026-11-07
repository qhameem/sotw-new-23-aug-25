@extends('layouts.app')

@section('title', 'Custom Pages')

@section('content')
    <div class="space-y-4 p-4">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">Custom Pages</h1>
            <a class="rounded bg-gray-900 px-4 py-2 text-white" href="{{ route('admin.pages.create') }}">New page</a>
        </div>

        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        <ul class="divide-y rounded border">
            @forelse ($pages as $page)
                <li class="flex flex-wrap items-center justify-between gap-2 p-3">
                    <a class="font-medium underline" href="{{ route('admin.pages.edit', $page) }}">{{ $page->title }}</a>
                    <span class="break-all">/{{ $page->slug }}</span>
                    <span>{{ $page->is_published ? 'Published' : 'Draft' }}</span>
                </li>
            @empty
                <li class="p-3">No custom pages yet.</li>
            @endforelse
        </ul>

        {{ $pages->links() }}
    </div>
@endsection
