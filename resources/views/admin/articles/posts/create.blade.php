<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create New Article Post') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('admin.articles.posts.store') }}" method="POST">
                        @csrf

                        <!-- Main Content Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Main Content') }}</h3>
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="title" :value="__('Title')" />
                                    <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required autofocus />
                                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="slug" :value="__('Slug (optional - auto-generated if blank)')" />
                                    <x-text-input id="slug" class="block mt-1 w-full" type="text" name="slug" :value="old('slug')" />
                                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="content" :value="__('Content')" />
                                    {{-- Quill editor will be initialized on this div --}}
                                    <div id="quill-editor" style="height: 300px;" class="mt-1 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded-md">
                                        {!! old('content') !!} {{-- Populate with old input if validation fails --}}
                                    </div>
                                    {{-- Hidden input to store Quill's HTML content for form submission --}}
                                    <input type="hidden" name="content" id="content" value="{{ old('content') }}">
                                    <x-input-error :messages="$errors->get('content')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="featured_image_upload" :value="__('Featured Image')" />
                                    <input type="file" id="featured_image_upload" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" aria-describedby="featured_image_help">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300" id="featured_image_help">JPG, PNG, WEBP or GIF (MAX. 2MB).</p>
                                    <x-input-error :messages="$errors->get('featured_image_path')" class="mt-2" />
                                    <input type="hidden" name="featured_image_path" id="featured_image_path" :value="old('featured_image_path')">
                                    
                                    <div id="featured_image_preview_container" class="mt-2" style="display: none;">
                                        <img id="featured_image_preview" src="#" alt="Featured Image Preview" class="max-h-48 rounded shadow"/>
                                        <button type="button" id="remove_featured_image" class="mt-1 text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">Remove Image</button>
                                    </div>
                                    <div id="featured_image_upload_error" class="mt-1 text-xs text-red-600 dark:text-red-400" style="display: none;"></div>
                                    <div id="featured_image_upload_progress" class="mt-2 w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700" style="display: none;">
                                        <div id="featured_image_progress_bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Taxonomy Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Taxonomies') }}</h3>
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="categories" :value="__('Categories')" />
                                    <select name="categories[]" id="categories" multiple class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('categories')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="tags" :value="__('Tags')" />
                                     <select name="tags[]" id="tags" multiple class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                        @foreach($tags as $tag)
                                            <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'selected' : '' }}>{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('tags')" class="mt-2" />
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('(Tag input with creation on-the-fly could be an enhancement)') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status and Publishing Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Status & Publishing') }}</h3>
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    <select name="status" id="status" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                        @foreach($statuses as $key => $value)
                                            <option value="{{ $key }}" {{ old('status', 'draft') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="published_at" :value="__('Published At (optional - auto-filled if status is Published and left blank)')" />
                                    <x-text-input id="published_at" class="block mt-1 w-full" type="datetime-local" name="published_at" :value="old('published_at')" />
                                    <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- SEO Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('SEO & Meta Data') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="meta_title" :value="__('Meta Title')" />
                                    <x-text-input id="meta_title" class="block mt-1 w-full" type="text" name="meta_title" :value="old('meta_title')" />
                                    <x-input-error :messages="$errors->get('meta_title')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="meta_keywords" :value="__('Meta Keywords (comma-separated)')" />
                                    <x-text-input id="meta_keywords" class="block mt-1 w-full" type="text" name="meta_keywords" :value="old('meta_keywords')" />
                                    <x-input-error :messages="$errors->get('meta_keywords')" class="mt-2" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="meta_description" :value="__('Meta Description')" />
                                    <textarea id="meta_description" name="meta_description" rows="3" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('meta_description') }}</textarea>
                                    <x-input-error :messages="$errors->get('meta_description')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Open Graph Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Open Graph (for Social Sharing)') }}</h3>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="og_title" :value="__('OG: Title')" />
                                    <x-text-input id="og_title" class="block mt-1 w-full" type="text" name="og_title" :value="old('og_title')" />
                                    <x-input-error :messages="$errors->get('og_title')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="og_url" :value="__('OG: URL')" />
                                    <x-text-input id="og_url" class="block mt-1 w-full" type="url" name="og_url" :value="old('og_url')" placeholder="https://example.com/your-post-slug" />
                                    <x-input-error :messages="$errors->get('og_url')" class="mt-2" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="og_description" :value="__('OG: Description')" />
                                    <textarea id="og_description" name="og_description" rows="3" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('og_description') }}</textarea>
                                    <x-input-error :messages="$errors->get('og_description')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="og_image" :value="__('OG: Image URL')" />
                                    <x-text-input id="og_image" class="block mt-1 w-full" type="text" name="og_image" :value="old('og_image')" placeholder="https://example.com/image.jpg" />
                                    <x-input-error :messages="$errors->get('og_image')" class="mt-2" />
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('(Media library/upload will be integrated here)') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Twitter Card Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Twitter Card (for Social Sharing)') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="twitter_card" :value="__('Twitter: Card Type (e.g., summary, summary_large_image)')" />
                                    <x-text-input id="twitter_card" class="block mt-1 w-full" type="text" name="twitter_card" :value="old('twitter_card', 'summary_large_image')" />
                                    <x-input-error :messages="$errors->get('twitter_card')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="twitter_title" :value="__('Twitter: Title')" />
                                    <x-text-input id="twitter_title" class="block mt-1 w-full" type="text" name="twitter_title" :value="old('twitter_title')" />
                                    <x-input-error :messages="$errors->get('twitter_title')" class="mt-2" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="twitter_description" :value="__('Twitter: Description')" />
                                    <textarea id="twitter_description" name="twitter_description" rows="3" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('twitter_description') }}</textarea>
                                    <x-input-error :messages="$errors->get('twitter_description')" class="mt-2" />
                                </div>
                                {{-- <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 md:col-span-2">{{ __('Twitter image often uses the OG:Image.') }}</p> --}}
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.articles.posts.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Create Post') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    {{-- QuillJS Styles --}}
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-editor {
            min-height: 250px; /* Ensure editor has a decent min height */
            font-size: 1rem; /* Match Tailwind's base font size */
        }
        /* Basic dark mode theming for Quill toolbar (can be expanded) */
        .dark .ql-toolbar {
            border-color: #4a5568; /* gray-700 */
        }
        .dark .ql-toolbar .ql-stroke {
            stroke: #cbd5e0; /* gray-400 */
        }
        .dark .ql-toolbar .ql-fill {
            fill: #cbd5e0; /* gray-400 */
        }
        .dark .ql-toolbar .ql-picker-label {
            color: #cbd5e0; /* gray-400 */
        }
        .dark .ql-snow .ql-picker.ql-header .ql-picker-item::before {
            color: #cbd5e0;
        }
        .dark .ql-snow .ql-picker.ql-header .ql-picker-label::before {
            color: #cbd5e0;
        }
    </style>
    @endpush

    @push('scripts')
    {{-- QuillJS Script --}}
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toolbarOptions = [
                [{ 'header': [2, 3, 4, false] }], // H2, H3, H4
                ['bold', 'italic', 'underline'],        // toggled buttons
                ['blockquote'], // Removed code-block for now, can be added if syntax highlighting is also set up

                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                // [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript - not requested
                // [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent - not requested
                // [{ 'direction': 'rtl' }],                         // text direction - not requested

                // [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown - not requested
                

                [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme - not requested
                // [{ 'font': [] }], // - not requested
                // [{ 'align': [] }], // - not requested

                ['link'], // Add 'image' and 'video' here if direct embedding is desired and handled
                ['clean']                                         // remove formatting button
            ];

            var quill = new Quill('#quill-editor', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow',
                placeholder: 'Compose your masterpiece...'
            });

            // Sync Quill content to hidden input
            var hiddenInput = document.getElementById('content');
            quill.on('text-change', function(delta, oldDelta, source) {
                hiddenInput.value = quill.root.innerHTML;
            });
            
            // If there's old content (e.g., from validation error), set it into Quill
            // The initial content is already set by Blade's {!! old('content') !!} in the div
            // However, if old('content') was empty, Quill might not pick it up correctly without this.
            // If hiddenInput.value is not empty and Quill is empty, set Quill's content.
            // This is a bit tricky because Quill initializes with the div's content.
            // The {!! old('content') !!} in the div should handle repopulation.

            // Slug generation
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            let manualSlugEdit = false;
            let debounceTimer;

            function generateSlug(str) {
                str = str.toLowerCase();
                str = str.replace(/\s+/g, '-'); // Replace spaces with -
                str = str.replace(/[^\w-]+/g, ''); // Remove all non-word chars except -
                str = str.replace(/--+/g, '-'); // Replace multiple - with single -
                str = str.replace(/^-+/, ''); // Trim - from start of text
                str = str.replace(/-+$/, ''); // Trim - from end of text
                return str;
            }

            titleInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    if (!manualSlugEdit && slugInput.value === '' || slugInput.value === generateSlug(titleInput.value.slice(0, -1))) { // Check if slug was likely auto-generated
                        slugInput.value = generateSlug(titleInput.value);
                    }
                }, 300); // 300ms debounce
            });

            slugInput.addEventListener('input', function() {
                manualSlugEdit = true;
                // If user clears the slug, re-enable auto-generation
                if (slugInput.value === '') {
                    manualSlugEdit = false;
                    // Optionally, regenerate from title immediately if title has value
                    if (titleInput.value !== '') {
                         slugInput.value = generateSlug(titleInput.value);
                    }
                }
            });
             // Initial slug generation if title is pre-filled (e.g. from old input) and slug is empty
             if (titleInput.value !== '' && slugInput.value === '') {
                 slugInput.value = generateSlug(titleInput.value);
             }
 
             // Featured Image Upload
             const imageUploadInput = document.getElementById('featured_image_upload');
             const imagePathInput = document.getElementById('featured_image_path');
             const imagePreviewContainer = document.getElementById('featured_image_preview_container');
             const imagePreview = document.getElementById('featured_image_preview');
             const removeImageButton = document.getElementById('remove_featured_image');
             const imageUploadError = document.getElementById('featured_image_upload_error');
             const imageUploadProgress = document.getElementById('featured_image_upload_progress');
             const imageProgressBar = document.getElementById('featured_image_progress_bar');
 
             imageUploadInput.addEventListener('change', function(event) {
                 const file = event.target.files[0];
                 if (file) {
                     imageUploadError.style.display = 'none';
                     imageUploadError.textContent = '';
                     imageUploadProgress.style.display = 'block';
                     imageProgressBar.style.width = '0%';
 
                     // Client-side validation (optional, server-side is key)
                     const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                     if (!allowedTypes.includes(file.type)) {
                         imageUploadError.textContent = 'Invalid file type. Please upload JPG, PNG, GIF, or WEBP.';
                         imageUploadError.style.display = 'block';
                         imageUploadProgress.style.display = 'none';
                         imageUploadInput.value = ''; // Clear the input
                         return;
                     }
                     if (file.size > 2 * 1024 * 1024) { // 2MB
                         imageUploadError.textContent = 'File is too large. Maximum size is 2MB.';
                         imageUploadError.style.display = 'block';
                         imageUploadProgress.style.display = 'none';
                         imageUploadInput.value = ''; // Clear the input
                         return;
                     }
 
                     const formData = new FormData();
                     formData.append('featured_image', file);
 
                     const xhr = new XMLHttpRequest();
                     xhr.open('POST', '{{ route("admin.articles.posts.uploadFeaturedImage") }}', true);
                     xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content')); // Ensure CSRF token meta tag exists in layout
 
                     xhr.upload.onprogress = function(e) {
                         if (e.lengthComputable) {
                             const percentComplete = (e.loaded / e.total) * 100;
                             imageProgressBar.style.width = percentComplete + '%';
                         }
                     };
 
                     xhr.onload = function() {
                         imageUploadProgress.style.display = 'none';
                         if (xhr.status >= 200 && xhr.status < 300) {
                             const response = JSON.parse(xhr.responseText);
                             if (response.success) {
                                 imagePathInput.value = response.path;
                                 imagePreview.src = response.url;
                                 imagePreviewContainer.style.display = 'block';
                             } else {
                                 imageUploadError.textContent = response.message || 'Upload failed.';
                                 imageUploadError.style.display = 'block';
                                 imageUploadInput.value = '';
                             }
                         } else {
                             imageUploadError.textContent = 'Upload error: ' + xhr.statusText;
                             imageUploadError.style.display = 'block';
                             imageUploadInput.value = '';
                         }
                     };
 
                     xhr.onerror = function() {
                         imageUploadProgress.style.display = 'none';
                         imageUploadError.textContent = 'Network error during upload.';
                         imageUploadError.style.display = 'block';
                         imageUploadInput.value = '';
                     };
 
                     xhr.send(formData);
                 }
             });
 
             removeImageButton.addEventListener('click', function() {
                 imagePathInput.value = '';
                 imagePreview.src = '#';
                 imagePreviewContainer.style.display = 'none';
                 imageUploadInput.value = ''; // Clear the file input
                 imageUploadError.style.display = 'none';
             });
 
             // If there's an old value for featured_image_path (e.g. validation error page reload), show preview
             if (imagePathInput.value) {
                  // Assuming Storage::url() or asset() was used to generate the URL if it's a relative path
                 imagePreview.src = imagePathInput.value.startsWith('http') ? imagePathInput.value : '{{ Storage::url("/") }}' + imagePathInput.value.replace(/^\/?public\//, '');
                 imagePreviewContainer.style.display = 'block';
             }
         });
     </script>
     @endpush
 </x-app-layout>