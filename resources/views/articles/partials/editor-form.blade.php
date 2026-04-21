@php
    $isEditing = isset($article) && $article->exists;
    $formMethod = strtoupper($formMethod ?? 'POST');
    $selectedCategories = old('categories', $isEditing ? $article->categories->modelKeys() : []);
    $selectedTags = old('tags', $isEditing ? $article->tags->modelKeys() : []);
    $previewUrl = $isEditing ? route('articles.preview', ['article' => $article->id]) : '';
    $editUrl = $isEditing
        ? ($context === 'admin'
            ? route('admin.articles.posts.edit', ['post' => $article->id])
            : route('articles.edit', ['article' => $article->id]))
        : '';
    $updateUrl = $isEditing
        ? ($context === 'admin'
            ? route('admin.articles.posts.update', ['post' => $article->id])
            : route('articles.update', ['article' => $article->id]))
        : '';
    $currentStatus = old('status', $article->status ?? 'draft');
    $publishedAtValue = old('published_at', $article->published_at?->format('Y-m-d\TH:i') ?? '');
    $currentCanonical = $isEditing
        ? route('articles.show', ['article' => $article->slug ?: 'preview'])
        : url('/articles/your-article-slug');
@endphp

@once
    @push('styles')
        <style>
            .article-editor-card {
                border: 1px solid rgb(229 231 235);
                border-radius: 1rem;
                background: #ffffff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            }

            .article-editor-field-help {
                margin-top: 0.5rem;
                font-size: 0.75rem;
                color: rgb(107 114 128);
            }

            .article-editor-quill .ql-editor {
                min-height: 24rem;
                font-size: 1rem;
                line-height: 1.75;
            }

            .article-editor-quill .ql-toolbar.ql-snow,
            .article-editor-quill .ql-container.ql-snow {
                border-color: rgb(209 213 219);
            }

            .article-editor-quill .ql-toolbar.ql-snow {
                border-top-left-radius: 1rem;
                border-top-right-radius: 1rem;
            }

            .article-editor-quill .ql-container.ql-snow {
                border-bottom-left-radius: 1rem;
                border-bottom-right-radius: 1rem;
            }

            .article-editor-layout {
                display: grid;
                gap: 2rem;
            }

            main:has(form[data-article-editor-root]) {
                max-width: none !important;
                width: 100% !important;
                flex-basis: 100% !important;
            }

            .article-editor-shell {
                width: 100%;
                max-width: 112rem;
                margin: 0 auto;
            }

            .article-editor-main {
                min-width: 0;
            }

            @media (min-width: 1280px) {
                .article-editor-layout {
                    grid-template-columns: minmax(0, 1.9fr) minmax(18rem, 0.72fr);
                    align-items: start;
                }
            }

            @media (min-width: 1536px) {
                .article-editor-layout {
                    grid-template-columns: minmax(0, 2.2fr) 22rem;
                }
            }
        </style>
    @endpush
@endonce

<form
    action="{{ $formAction }}"
    method="POST"
    class="w-full px-4 py-8 sm:px-6 lg:px-8"
    data-article-editor-root
    data-context="{{ $context }}"
    data-article-id="{{ $isEditing ? $article->id : '' }}"
    data-preview-url="{{ $previewUrl }}"
    data-edit-url="{{ $editUrl }}"
    data-update-url="{{ $updateUrl }}"
    data-current-status="{{ $article->status ?? 'draft' }}"
    data-current-published-at="{{ $publishedAtValue }}"
    data-autosave-url="{{ route('articles.editor.autosave') }}"
    data-image-upload-url="{{ route('articles.editor.upload-image') }}"
>
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="article-editor-shell">
        <div class="article-editor-layout">
            <div class="article-editor-main space-y-6">
                <div class="article-editor-card p-6">
                    <div class="space-y-6">
                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input
                            id="title"
                            class="mt-2 block w-full text-lg"
                            type="text"
                            name="title"
                            :value="old('title', $article->title ?? '')"
                            required
                            autofocus
                        />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <x-input-label for="slug" :value="__('Slug')" />
                            <span class="text-xs text-gray-500">Auto-generated until you edit it manually</span>
                        </div>
                        <x-text-input
                            id="slug"
                            class="mt-2 block w-full"
                            type="text"
                            name="slug"
                            :value="old('slug', $article->slug ?? '')"
                        />
                        <p class="article-editor-field-help">
                            Preview URL:
                            <span class="font-medium text-gray-700" data-article-seo-url-preview>{{ $currentCanonical }}</span>
                        </p>
                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <x-input-label for="content" :value="__('Content')" />
                            <div class="text-xs text-gray-500">
                                <span data-article-word-count>0 words</span>
                                <span class="mx-1">•</span>
                                <span data-article-reading-time>0 min read</span>
                            </div>
                        </div>

                        <div class="article-editor-quill mt-2">
                            <div data-article-quill></div>
                        </div>
                        <input type="hidden" name="content" id="content" value="{{ old('content', $article->content ?? '') }}">
                        <p class="article-editor-field-help">
                            Autosave keeps draft edits safe while you write. Use Preview to open the rendered article in a new tab.
                        </p>
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    </div>
                    </div>
                </div>

            <div class="article-editor-card p-6">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between gap-3">
                            <x-input-label for="featured_image_upload" :value="__('Featured Image')" />
                            <span class="text-xs text-gray-500">JPG, PNG, GIF, WEBP, AVIF, SVG up to 2MB</span>
                        </div>

                        <input
                            type="file"
                            id="featured_image_upload"
                            class="hidden"
                            accept="image/*"
                            data-featured-image-input
                        >
                        <input
                            type="hidden"
                            name="featured_image_path"
                            value="{{ old('featured_image_path', $article->featured_image_path ?? '') }}"
                            data-featured-image-path
                        >

                        <label
                            for="featured_image_upload"
                            class="mt-2 flex min-h-64 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center transition hover:border-primary-500 hover:bg-white"
                            data-featured-image-dropzone
                        >
                            <div data-featured-image-empty class="{{ old('featured_image_path', $article->featured_image_path ?? '') ? 'hidden' : '' }}">
                                <p class="text-sm font-semibold text-gray-700">Click to upload or drag and drop</p>
                                <p class="mt-2 text-xs text-gray-500">Use a clear landscape image to improve sharing previews.</p>
                            </div>

                            <div class="w-full {{ old('featured_image_path', $article->featured_image_path ?? '') ? '' : 'hidden' }}" data-featured-image-preview-wrapper>
                                <img
                                    src="{{ old('featured_image_path', $article->featured_image_path ?? '') ? (\Illuminate\Support\Str::startsWith(old('featured_image_path', $article->featured_image_path ?? ''), ['http://', 'https://']) ? old('featured_image_path', $article->featured_image_path ?? '') : \Illuminate\Support\Facades\Storage::url(old('featured_image_path', $article->featured_image_path ?? ''))) : '' }}"
                                    alt="Featured image preview"
                                    class="mx-auto max-h-80 rounded-xl object-cover shadow-sm"
                                    data-featured-image-preview
                                >
                            </div>
                        </label>

                        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-500" data-featured-image-error></span>
                            <button
                                type="button"
                                class="font-medium text-red-600 hover:text-red-700 {{ old('featured_image_path', $article->featured_image_path ?? '') ? '' : 'hidden' }}"
                                data-featured-image-remove
                            >
                                Remove image
                            </button>
                        </div>
                        <div class="mt-3 hidden h-2 overflow-hidden rounded-full bg-gray-200" data-featured-image-progress>
                            <div class="h-full rounded-full bg-primary-600" style="width: 0%" data-featured-image-progress-bar></div>
                        </div>
                        <x-input-error :messages="$errors->get('featured_image_path')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="categories" :value="__('Categories')" />
                        <select
                            name="categories[]"
                            id="categories"
                            multiple
                            class="mt-2 block min-h-40 w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategories))>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="article-editor-field-help">Hold Cmd/Ctrl to select multiple categories.</p>
                        <x-input-error :messages="$errors->get('categories')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tags" :value="__('Tags')" />
                        <select
                            name="tags[]"
                            id="tags"
                            multiple
                            class="mt-2 block min-h-40 w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array($tag->id, $selectedTags))>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="article-editor-field-help">Tags help readers browse related content faster.</p>
                        <x-input-error :messages="$errors->get('tags')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="article-editor-card p-6">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <x-input-label for="meta_title" :value="__('Meta Title')" />
                        <x-text-input id="meta_title" class="mt-2 block w-full" type="text" name="meta_title" :value="old('meta_title', $article->meta_title ?? '')" />
                        <p class="article-editor-field-help"><span data-article-meta-title-count>0</span>/60 recommended characters.</p>
                        <x-input-error :messages="$errors->get('meta_title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="meta_keywords" :value="__('Meta Keywords')" />
                        <x-text-input id="meta_keywords" class="mt-2 block w-full" type="text" name="meta_keywords" :value="old('meta_keywords', $article->meta_keywords ?? '')" />
                        <x-input-error :messages="$errors->get('meta_keywords')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="meta_description" :value="__('Meta Description')" />
                        <textarea
                            id="meta_description"
                            name="meta_description"
                            rows="4"
                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >{{ old('meta_description', $article->meta_description ?? '') }}</textarea>
                        <p class="article-editor-field-help"><span data-article-meta-description-count>0</span>/160 recommended characters.</p>
                        <x-input-error :messages="$errors->get('meta_description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="og_title" :value="__('OG Title')" />
                        <x-text-input id="og_title" class="mt-2 block w-full" type="text" name="og_title" :value="old('og_title', $article->og_title ?? '')" />
                        <x-input-error :messages="$errors->get('og_title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="og_url" :value="__('OG URL')" />
                        <x-text-input id="og_url" class="mt-2 block w-full" type="url" name="og_url" :value="old('og_url', $article->og_url ?? '')" />
                        <x-input-error :messages="$errors->get('og_url')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="og_description" :value="__('OG Description')" />
                        <textarea
                            id="og_description"
                            name="og_description"
                            rows="3"
                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >{{ old('og_description', $article->og_description ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('og_description')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="og_image" :value="__('OG Image URL')" />
                        <x-text-input id="og_image" class="mt-2 block w-full" type="text" name="og_image" :value="old('og_image', $article->og_image ?? '')" />
                        <x-input-error :messages="$errors->get('og_image')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="twitter_card" :value="__('Twitter Card')" />
                        <x-text-input id="twitter_card" class="mt-2 block w-full" type="text" name="twitter_card" :value="old('twitter_card', $article->twitter_card ?? 'summary_large_image')" />
                        <x-input-error :messages="$errors->get('twitter_card')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="twitter_title" :value="__('Twitter Title')" />
                        <x-text-input id="twitter_title" class="mt-2 block w-full" type="text" name="twitter_title" :value="old('twitter_title', $article->twitter_title ?? '')" />
                        <x-input-error :messages="$errors->get('twitter_title')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="twitter_description" :value="__('Twitter Description')" />
                        <textarea
                            id="twitter_description"
                            name="twitter_description"
                            rows="3"
                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >{{ old('twitter_description', $article->twitter_description ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('twitter_description')" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>

            <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start xl:min-w-[18rem]">
                <div class="article-editor-card p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Publishing</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select
                                name="status"
                                id="status"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="published_at" :value="__('Publish At')" />
                            <x-text-input
                                id="published_at"
                                class="mt-2 block w-full"
                                type="datetime-local"
                                name="published_at"
                                :value="$publishedAtValue"
                            />
                            <p class="article-editor-field-help">Required only when scheduling a post.</p>
                            <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
                        </div>

                        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600">
                            <div class="font-medium text-gray-700" data-article-save-state>
                                {{ $isEditing ? 'Saved changes will appear here' : 'Draft autosave starts once you begin writing' }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                Autosave never promotes a draft to published on its own.
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                data-article-preview-button
                            >
                                Preview
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                data-article-force-status="draft"
                            >
                                Save Draft
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                            >
                                {{ $submitLabel ?? ($isEditing ? 'Save Changes' : 'Create Article') }}
                            </button>

                            <a href="{{ $cancelUrl }}" class="text-center text-sm font-medium text-gray-500 hover:text-gray-700">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="article-editor-card p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">SEO Preview</h2>

                    <div class="mt-4 rounded-2xl border border-gray-200 p-4">
                        <div class="truncate text-sm font-medium text-blue-700" data-article-seo-title-preview>
                            {{ old('meta_title', $article->meta_title ?: ($article->title ?? 'Untitled article')) }}
                        </div>
                        <div class="mt-1 truncate text-xs text-green-700" data-article-seo-url-preview-secondary>
                            {{ $currentCanonical }}
                        </div>
                        <p class="mt-2 text-sm text-gray-600" data-article-seo-description-preview>
                            {{ old('meta_description', $article->meta_description ?: 'Write a concise meta description so search results show a useful summary.') }}
                        </p>
                    </div>
                </div>

                @if($isEditing && ($revisions ?? collect())->isNotEmpty())
                    <div class="article-editor-card p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Recent Revisions</h2>
                            <span class="text-xs text-gray-400">{{ $revisions->count() }}</span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach($revisions as $revision)
                                <div class="rounded-xl border border-gray-200 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-medium text-gray-700">
                                                {{ ucfirst(str_replace('-', ' ', $revision->reason)) }}
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $revision->created_at->format('M j, Y g:i A') }}
                                                @if($revision->user)
                                                    • {{ $revision->user->name ?? $revision->user->email }}
                                                @endif
                                            </div>
                                        </div>

                                        <form action="{{ route('articles.revisions.restore', ['revision' => $revision->id]) }}" method="POST">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="text-xs font-medium text-primary-600 hover:text-primary-700"
                                                onclick="return confirm('Restore this revision? Your current draft will be captured before restoring.')"
                                            >
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</form>
