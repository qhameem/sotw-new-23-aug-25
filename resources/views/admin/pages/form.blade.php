@extends('layouts.app')

@section('title', $page->exists ? 'Edit Custom Page' : 'New Custom Page')

@section('content')
    <div class="space-y-4 p-4">
        <a class="underline" href="{{ route('admin.pages.index') }}">Back to pages</a>
        <h1 class="text-2xl font-bold">{{ $page->exists ? 'Edit Custom Page' : 'New Custom Page' }}</h1>

        @if (session('success'))
            <p role="status">{{ session('success') }}</p>
        @endif

        @if ($page->is_published)
            <a class="underline" href="{{ route('custom-pages.show', ['product_name' => $page->slug]) }}">View published page</a>
        @endif

        <form method="POST" class="space-y-4"
            action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
            @csrf
            @if ($page->exists)
                @method('PUT')
            @endif

            <div>
                <label class="block font-medium" for="title">Title</label>
                <input class="w-full rounded border" id="title" name="title" value="{{ old('title', $page->title) }}" maxlength="255" required>
                @error('title') <p class="text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-medium" for="slug">URL slug</label>
                <input class="w-full rounded border" id="slug" name="slug" value="{{ old('slug', $page->slug) }}"
                    placeholder="startup-directories" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" maxlength="120" required>
                <p class="text-sm text-gray-600">Use lowercase letters, numbers, and hyphens. Changing the slug changes the public URL.</p>
                @error('slug') <p class="text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-medium" for="body">Content (optional, Markdown)</label>
                <textarea class="w-full rounded border" id="body" name="body" rows="16" maxlength="100000">{{ old('body', $page->body) }}</textarea>
                @error('body') <p class="text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-medium" for="is_published">Visibility</label>
                <select class="rounded border" id="is_published" name="is_published">
                    <option value="0" @selected(!old('is_published', $page->is_published))>Draft</option>
                    <option value="1" @selected(old('is_published', $page->is_published))>Published</option>
                </select>
                @error('is_published') <p class="text-red-700">{{ $message }}</p> @enderror
            </div>

            <button class="rounded bg-gray-900 px-4 py-2 text-white" type="submit">Save page</button>
        </form>
    </div>
@endsection
