@section('header-title')
    <h1 class="text-xl font-bold text-gray-800">{{ __('Write an Article') }}</h1>
@endsection

@extends('layouts.app')

@section('content')
<div class="bg-white overflow-hidden sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <form action="{{ route('articles.store') }}" method="POST">
            @csrf
            <input type="hidden" name="status" id="status_input" value="published">

            <!-- Main Content Section -->
            <div class="mb-6">
                <!-- <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Main Content') }}</h3> -->
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
                        <div id="quill-editor" style="height: 300px;" class="mt-1 bg-white text-gray-900 border border-gray-300 rounded-md">
                            {!! old('content') !!}
                        </div>
                        <input type="hidden" name="content" id="content" value="{{ old('content') }}">
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="meta_description" :value="__('Meta Description')" />
                        <textarea id="meta_description" name="meta_description" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3" maxlength="160">{{ old('meta_description') }}</textarea>
                        <div class="text-sm text-gray-500 mt-1">
                            <span id="meta_description_counter">0</span>/160 characters
                        </div>
                        <x-input-error :messages="$errors->get('meta_description')" class="mt-2" />
                    </div>

                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@section('right_sidebar_content')
<div class="space-y-4">
    <div class="bg-white p-4 rounded-lg">
        <div class="flex items-center justify-end space-x-3">
            <x-secondary-button onclick="document.getElementById('status_input').value = 'draft'; document.querySelector('form').submit();">
                {{ __('Save as Draft') }}
            </x-secondary-button>
            <x-primary-button-sky onclick="document.getElementById('status_input').value = 'published'; document.querySelector('form').submit();">
                {{ __('Publish') }}
            </x-primary-button-sky>
        </div>
    </div>
    <!-- Featured Image Section -->
    <div class="bg-white p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Featured Image') }}</h3>
        <div>
            <input type="file" id="featured_image_upload" class="hidden">
            <label for="featured_image_upload" id="featured_image_dropzone" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                <div id="featured_image_upload_prompt" class="flex flex-col items-center justify-center pt-5 pb-6">
                   
                    <svg class="w-8 h-8 mb-4 opacity-45" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 13a1 1 0 0 0-1 1v.38l-1.48-1.48a2.79 2.79 0 0 0-3.93 0l-.7.7l-2.48-2.48a2.85 2.85 0 0 0-3.93 0L4 12.6V7a1 1 0 0 1 1-1h7a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-5a1 1 0 0 0-1-1ZM5 20a1 1 0 0 1-1-1v-3.57l2.9-2.9a.79.79 0 0 1 1.09 0l3.17 3.17l4.3 4.3Zm13-1a.89.89 0 0 1-.18.53L13.31 15l.7-.7a.77.77 0 0 1 1.1 0L18 17.21Zm4.71-14.71l-3-3a1 1 0 0 0-.33-.21a1 1 0 0 0-.76 0a1 1 0 0 0-.33.21l-3 3a1 1 0 0 0 1.42 1.42L18 4.41V10a1 1 0 0 0 2 0V4.41l1.29 1.3a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.42Z"/></svg>
                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                    <p class="text-xs text-gray-500">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
                </div>
                <div id="featured_image_preview_container" class="hidden w-full h-full">
                    <img id="featured_image_preview" src="#" alt="Featured Image Preview" class="object-cover w-full h-full rounded-lg"/>
                </div>
            </label>
            <input type="hidden" name="featured_image_path" id="featured_image_path" value="{{ old('featured_image_path') }}">
            <button type="button" id="remove_featured_image" class="mt-1 text-xs text-red-600 hover:text-red-800 hidden">Remove Image</button>
            <div id="featured_image_upload_error" class="mt-1 text-xs text-red-600" style="display: none;"></div>
            <div id="featured_image_upload_progress" class="mt-2 w-full bg-gray-200 rounded-full h-2.5" style="display: none;">
                <div id="featured_image_progress_bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Taxonomy Section -->
    <div class="bg-white p-4 rounded-lg" x-data="articleForm({ categories: {{ $categories->toJson() }} })">
        
        <div class="space-y-4">
            <div>
                <x-input-label for="categories" :value="__('Category')" class="mb-2" />
                <input type="hidden" name="category_id" x-model="selectedCategory">
                <div class="relative" @click.away="dropdownOpen = false">
                    <input type="text"
                           x-model="categorySearchTerm"
                           @focus="dropdownOpen = true"
                           @keydown.arrow-down.prevent="highlightNext()"
                           @keydown.arrow-up.prevent="highlightPrevious()"
                           @keydown.enter.prevent="selectHighlightedCategory()"
                           @keydown.escape.window="dropdownOpen = false"
                           placeholder="Search categories..."
                           class="block w-full text-sm border-gray-300 focus:border-primary-500 rounded-md placeholder-gray-500 placeholder:text-xs">
                    <div x-show="dropdownOpen && categorySearchTerm" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                        <ul>
                            <template x-for="(category, index) in filteredCategories" :key="category.id">
                                <li @click="selectCategory(category)"
                                    :class="{ 'bg-gray-100': highlightedIndex === index }"
                                    class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-100"
                                    x-text="category.name"></li>
                            </template>
                        </ul>
                    </div>
                </div>
                <div x-show="selectedCategory" class="mt-2">
                    <span class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-700 bg-gray-100 rounded-full">
                        <span x-text="selectedCategoryName"></span>
                        <button @click="selectedCategory = null; categorySearchTerm = ''" type="button" class="flex-shrink-0 ml-1.5 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Remove category</span>
                            <svg class="w-2 h-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                            </svg>
                        </button>
                    </span>
                </div>
                <x-input-error :messages="$errors->get('categories')" class="mt-2" />
            </div>
        </div>
    </div>

</div>
@endsection

    @push('styles')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-editor {
            min-height: 250px;
            font-size: 1rem;
            background-color: #fff;
            color: #000;
        }
        .ql-toolbar {
            background-color: #f9fafb; /* A light gray, similar to other inputs */
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
        .ql-container {
            border-bottom-left-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        .ql-tooltip {
            z-index: 1000;
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
                ['link', 'image'],
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
               
                // Auto-generate meta description
                const text = quill.getText(0, 200).trim();
                const metaDescriptionInput = document.getElementById('meta_description');
                if (metaDescriptionInput.value === '' || !metaDescriptionInput.dataset.manualEdit) {
                    metaDescriptionInput.value = text.substring(0, 160);
                    updateMetaDescriptionCounter();
                }
            });

            quill.getModule('toolbar').container.querySelector('.ql-link').addEventListener('click', function() {
                setTimeout(() => {
                    const tooltip = quill.container.querySelector('.ql-tooltip');
                    if (tooltip.classList.contains('ql-editing')) {
                        const editorBounds = quill.container.getBoundingClientRect();
                        const tooltipBounds = tooltip.getBoundingClientRect();

                        if (tooltipBounds.left < editorBounds.left) {
                            tooltip.style.left = (editorBounds.left - tooltipBounds.left) + 'px';
                        }
                    }
                }, 100);
            });
 
            function imageHandler() {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.click();

                input.onchange = () => {
                    const file = input.files[0];
                    if (file) {
                        const formData = new FormData();
                        formData.append('image', file);

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', '{{ route("admin.articles.posts.uploadFeaturedImage") }}', true);
                        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                        xhr.onload = () => {
                            if (xhr.status === 200) {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    const range = quill.getSelection();
                                    quill.insertEmbed(range.index, 'image', response.url);
                                } else {
                                    console.error(response.message);
                                }
                            } else {
                                console.error('Upload failed');
                            }
                        };

                        xhr.send(formData);
                    }
                };
            }

            quill.getModule('toolbar').addHandler('image', imageHandler);

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
             const dropzone = document.getElementById('featured_image_dropzone');
             const uploadPrompt = document.getElementById('featured_image_upload_prompt');
 
             const metaDescriptionInput = document.getElementById('meta_description');
             const metaDescriptionCounter = document.getElementById('meta_description_counter');
 
             function updateMetaDescriptionCounter() {
                 const length = metaDescriptionInput.value.length;
                 metaDescriptionCounter.textContent = length;
             }
 
             metaDescriptionInput.addEventListener('input', function() {
                 this.dataset.manualEdit = 'true';
                 updateMetaDescriptionCounter();
             });
 
             // Initial counter update on page load
             updateMetaDescriptionCounter();

             function handleFile(file) {
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
                                 uploadPrompt.classList.add('hidden');
                                 imagePreviewContainer.classList.remove('hidden');
                                 removeImageButton.classList.remove('hidden');
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
             }

             imageUploadInput.addEventListener('change', (e) => handleFile(e.target.files[0]));

             dropzone.addEventListener('dragover', (e) => {
                 e.preventDefault();
                 dropzone.classList.add('border-blue-500');
             });

             dropzone.addEventListener('dragleave', (e) => {
                 e.preventDefault();
                 dropzone.classList.remove('border-blue-500');
             });

             dropzone.addEventListener('drop', (e) => {
                 e.preventDefault();
                 dropzone.classList.remove('border-blue-500');
                 const file = e.dataTransfer.files[0];
                 handleFile(file);
             });

             removeImageButton.addEventListener('click', function() {
                 imagePathInput.value = '';
                 imagePreview.src = '#';
                 uploadPrompt.classList.remove('hidden');
                 imagePreviewContainer.classList.add('hidden');
                 removeImageButton.classList.add('hidden');
                 imageUploadInput.value = '';
                 imageUploadError.style.display = 'none';
             });

             if (imagePathInput.value) {
                imagePreview.src = imagePathInput.value.startsWith('http') ? imagePathInput.value : '{{ Storage::url("/") }}' + imagePathInput.value.replace(/^\/?public\//, '');
                uploadPrompt.classList.add('hidden');
                imagePreviewContainer.classList.remove('hidden');
                removeImageButton.classList.remove('hidden');
             }
         });

    </script>
    @endpush

@push('form-scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('articleForm', (data) => ({
            allCategories: data.categories,
            categorySearchTerm: '',
            selectedCategory: null,
            dropdownOpen: false,
            highlightedIndex: -1,
            init() {
                this.$watch('categorySearchTerm', (value) => {
                    if (this.selectedCategory && this.categorySearchTerm !== this.selectedCategoryName) {
                        this.selectedCategory = null;
                    }
                    this.highlightedIndex = -1;
                    this.dropdownOpen = !!value;
                });
            },
            get filteredCategories() {
                if (!this.categorySearchTerm) {
                    return [];
                }
                return this.allCategories.filter(category => {
                    return category.name.toLowerCase().includes(this.categorySearchTerm.toLowerCase());
                });
            },
            selectCategory(category) {
                this.selectedCategory = category.id;
                this.categorySearchTerm = category.name;
                setTimeout(() => {
                    this.dropdownOpen = false;
                }, 100);
            },
            highlightNext() {
                if (this.highlightedIndex < this.filteredCategories.length - 1) {
                    this.highlightedIndex++;
                }
            },
            highlightPrevious() {
                if (this.highlightedIndex > 0) {
                    this.highlightedIndex--;
                }
            },
            selectHighlightedCategory() {
                if (this.highlightedIndex > -1 && this.filteredCategories[this.highlightedIndex]) {
                    this.selectCategory(this.filteredCategories[this.highlightedIndex]);
                }
            },
            get selectedCategoryName() {
                if (!this.selectedCategory) {
                    return '';
                }
                const category = this.allCategories.find(cat => cat.id == this.selectedCategory);
                return category ? category.name : '';
            }
        }));
    });
</script>
@endpush
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>
        </div>
    </div>