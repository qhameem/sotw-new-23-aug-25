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
                <p class="text-sm text-gray-600">Use [year] for the current year, for example: Best SaaS Directories ([year]). Works in content too.</p>
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
                <label class="block font-medium" for="template">Template</label>
                <select class="rounded border" id="template" name="template" required>
                    @foreach(\App\Models\CustomPage::TEMPLATES as $value => $label)
                        <option value="{{ $value }}" @selected(old('template', $page->template ?? 'default') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-600">Standard keeps the existing layout. Blank uses a wide content column with only the top menubar and footer. Sidebar settings apply to Standard only.</p>
                @error('template') <p class="text-red-700">{{ $message }}</p> @enderror
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

            <fieldset class="space-y-2 rounded border p-4">
                <legend class="font-medium">Sidebar sections</legend>
                <p class="text-sm text-gray-600">Choose sections for this page. Unchecked sections are removed from the public HTML. Uncheck all to use the full content width.</p>
                @foreach(\App\Models\CustomPage::SIDEBAR_SECTIONS as $key => $label)
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="sidebar_sections[{{ $key }}]" value="0">
                        <input type="checkbox" name="sidebar_sections[{{ $key }}]" value="1"
                            @checked(old("sidebar_sections.$key", $page->sidebarSections()[$key]))>
                        <span>{{ $label }}</span>
                    </label>
                    @error("sidebar_sections.$key") <p class="text-red-700">{{ $message }}</p> @enderror
                @endforeach
                @error('sidebar_sections') <p class="text-red-700">{{ $message }}</p> @enderror
            </fieldset>

            <button class="rounded bg-gray-900 px-4 py-2 text-white" type="submit">Save page</button>
        </form>
    </div>
@endsection
