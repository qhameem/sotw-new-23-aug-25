<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Write a New Article') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('articles.store') }}" method="POST">
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
                                    <div id="quill-editor" style="height: 300px;" class="mt-1 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-700 rounded-md">
                                        {!! old('content') !!}
                                    </div>
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
                                </div>
                            </div>
                        </div>

                        <!-- Status and Publishing Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Status') }}</h3>
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
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('articles.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Submit Article') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-editor {
            min-height: 250px;
            font-size: 1rem;
        }
        .dark .ql-toolbar {
            border-color: #4a5568;
        }
        .dark .ql-toolbar .ql-stroke {
            stroke: #cbd5e0;
        }
        .dark .ql-toolbar .ql-fill {
            fill: #cbd5e0;
        }
        .dark .ql-toolbar .ql-picker-label {
            color: #cbd5e0;
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
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toolbarOptions = [
                [{ 'header': [2, 3, 4, false] }],
                ['bold', 'italic', 'underline'],
                ['blockquote'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ];

            var quill = new Quill('#quill-editor', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow',
                placeholder: 'Compose your masterpiece...'
            });

            var hiddenInput = document.getElementById('content');
            quill.on('text-change', function(delta, oldDelta, source) {
                hiddenInput.value = quill.root.innerHTML;
            });

            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            let manualSlugEdit = false;
            let debounceTimer;

            function generateSlug(str) {
                str = str.toLowerCase();
                str = str.replace(/\s+/g, '-');
                str = str.replace(/[^\w-]+/g, '');
                str = str.replace(/--+/g, '-');
                str = str.replace(/^-+/, '');
                str = str.replace(/-+$/, '');
                return str;
            }

            titleInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    if (!manualSlugEdit && slugInput.value === '' || slugInput.value === generateSlug(titleInput.value.slice(0, -1))) {
                        slugInput.value = generateSlug(titleInput.value);
                    }
                }, 300);
            });

            slugInput.addEventListener('input', function() {
                manualSlugEdit = true;
                if (slugInput.value === '') {
                    manualSlugEdit = false;
                    if (titleInput.value !== '') {
                         slugInput.value = generateSlug(titleInput.value);
                    }
                }
            });
             if (titleInput.value !== '' && slugInput.value === '') {
                 slugInput.value = generateSlug(titleInput.value);
             }
 
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
 
                     const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                     if (!allowedTypes.includes(file.type)) {
                         imageUploadError.textContent = 'Invalid file type. Please upload JPG, PNG, GIF, or WEBP.';
                         imageUploadError.style.display = 'block';
                         imageUploadProgress.style.display = 'none';
                         imageUploadInput.value = '';
                         return;
                     }
                     if (file.size > 2 * 1024 * 1024) { // 2MB
                         imageUploadError.textContent = 'File is too large. Maximum size is 2MB.';
                         imageUploadError.style.display = 'block';
                         imageUploadProgress.style.display = 'none';
                         imageUploadInput.value = '';
                         return;
                     }
 
                     const formData = new FormData();
                     formData.append('featured_image', file);
 
                     const xhr = new XMLHttpRequest();
                     xhr.open('POST', '{{ route("admin.articles.posts.uploadFeaturedImage") }}', true);
                     xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
 
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
                 imageUploadInput.value = '';
                 imageUploadError.style.display = 'none';
             });
 
             if (imagePathInput.value) {
                imagePreview.src = imagePathInput.value.startsWith('http') ? imagePathInput.value : '{{ Storage::url("/") }}' + imagePathInput.value.replace(/^\/?public\//, '');
                imagePreviewContainer.style.display = 'block';
             }
         });
    </script>
    @endpush
</x-app-layout>