@extends('layouts.app')

@section('header-title')
    <div class="flex gap-4 justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-700 py-[1px]">
            {{ isset($product) ? 'Edit Product: ' . ($displayData['name'] ?? $product->name) : 'Add Your Product' }}
        </h1>
        <button data-tooltip-target="tooltip-clear-form" onclick="clearForm()" type="button" class="bg-white border border-gray-300 hover:bg-gray-100 text-xs font-semibold py-1 px-3 rounded-lg transition-all duration-200 ease-in-out">
            Clear Form
        </button>
        <div id="tooltip-clear-form" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-xs text-white transition-opacity duration-300 bg-gray-700 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
            Refresh the page and clear all existing form data
            <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>
@endsection

@section('actions')
    {{-- No actions needed for this page --}}
@endsection

@section('content')
<div class="relative" x-data="productForm('{{ json_encode($product ?? null) }}', '{{ json_encode($displayData ?? []) }}', '{{ json_encode($allCategoriesData ?? []) }}')">
    @guest
    <div class="mt-10 inset-0 bg-white bg-opacity-75 z-10 flex items-center justify-center">
        <div class="text-center p-8 bg-white border rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Please log in to add your product</h2>
            <p class="text-gray-600 mb-4 text-sm tracking-tight">Join our community and showcase your product to a wider audience.</p>
            <button @click.prevent="$dispatch('open-login-modal')" class="bg-primary-500 text-white font-semibold text-sm hover:bg-primary-600 transition-colors duration-200 py-1 px-4 rounded-md hover:opacity-90">
                Log in or Sign up &rarr;
            </button>
        </div>
    </div>
    @endguest
    <div class="mx-auto px-4 sm:px-6 lg:px-2 py-6 pb-24 @guest blur-sm pointer-events-none @endguest">
        @if(isset($product))
            <div class="mb-4 p-3 rounded-md bg-blue-50 border border-blue-300 text-blue-700 text-sm">
                <strong>Note:</strong> Product Name, URL, and Slug cannot be changed through this form. To request changes to these fields, please contact support (support system to be implemented).
            </div>
        @endif

        @if(isset($product) && $product->approved && $product->has_pending_edits)
            <div class="mb-4 p-3 rounded-md bg-yellow-50 border border-yellow-400 text-yellow-800 text-sm">
                <strong>Pending Review:</strong> You have submitted edits for this product that are currently awaiting administrator approval. The changes you make below will update your pending proposal. The live product will not change until an admin approves your edits.
            </div>
        @endif
       
        @if($errors->any())
            <div class="bg-red-100 text-red-800 p-2 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="flex flex-col md:flex-row gap-6">
            <div class="md:w-full">
            <form action="{{ isset($product) ? route('products.update', $product) : route('products.store') }}" method="POST" enctype="multipart/form-data" class="text-sm p-0 rounded md:px-2 w-full" @submit.prevent="submitForm">
                @include('products.partials._form', ['types' => $types])
            </form>
           </div>
       </div>
   </div>
</div>
@endsection

@section('right_sidebar_content')
    <div class="md:w-5/6 mx-auto w-full mt-6 p-2 rounded flex flex-col bg-gradient-to-tr from-white to-gray-50">
        <h2 class="font-noto-serif text-lg text-gray-700 font-semibold mb-4">&#10003; Tips</h2>
        <div class="prose prose-xs text-xs max-w-none text-gray-600 space-y-3">
            <!-- <p>Please ensure your product submission adheres to the following guidelines:</p> -->
            <p class="text-gray-800 font-medium">
                <span>Product URL</span>
            </p>
            <ul class="list-disc ml-3 space-y-2 text-gray-600">
                <li>Provide a direct link to your product's main page.</li>
                <li>Avoid links to articles, blog posts, or press releases unless they are the primary product page.</li>
            </ul>

            <p class="text-gray-800 font-medium">
                <span>Name & Tagline</span>
            </p>
            <ul class="list-disc ml-3 space-y-2 text-gray-600">
                <li>Use the official product name.</li>
                <li>The tagline should be a concise and compelling summary of your product.</li>
            </ul>

            <p class="text-gray-800 font-medium">
                <span>Description</span>
            </p>
            <ul class="list-disc ml-3 space-y-2 text-gray-600">
                <li>Briefly describe your product.</li>
                <li>Highlight its key features.</li>
                <li>Clearly state its value proposition.</li>
                <li>Keep it informative and to the point.</li>
            </ul>

            <p class="text-gray-800 font-medium">
                <span>Logo</span>
            </p>
            <ul class="list-disc ml-3 space-y-2 text-gray-600">
                <li>Upload a clear, high-quality logo.</li>
                <li>A square aspect ratio is preferred.</li>
                <li>If a favicon is fetched automatically, you can still upload a custom logo to override it.</li>
            </ul>

            <p class="text-gray-800 font-medium">
                <span>Categories</span>
            </p>
            <ul class="list-disc ml-3 space-y-2 text-gray-600">
                <li>Select the most relevant categories that accurately describe your product.</li>
                <li>This helps users discover your product.</li>
                <li>Please select at least one "Pricing" category.</li>
                <li>Please also select one "Software Category".</li>
            </ul>
            <p>Submissions are reviewed by our team. Approved products will typically appear on the site based on their publish date (if set during approval) or immediately if no specific publish date is chosen by the admin.</p>
            <p>Thank you for contributing!</p>
        </div>
    </div>
@endsection


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
function clearForm() {
    const formId = '{{ isset($product) ? 'product_form_' . $product->id : 'new_product_form' }}';
    if (confirm('Are you sure you want to clear the form? All unsaved changes will be lost.')) {
        localStorage.removeItem(formId);
        window.location.reload();
    }
}

function productForm(productDataJson, formDataJson, allCategoriesDataJson) {
    const productData = JSON.parse(productDataJson);
    const formData = JSON.parse(formDataJson);
    const allCategoriesData = JSON.parse(allCategoriesDataJson);
    let urlCheckTimeout;
    const formId = productData ? `product_form_${productData.id}` : 'new_product_form';

    return {
        isEditMode: !!productData,
        quill: null,
        link: '',
        name: '',
        tagline: '',
        product_page_tagline: '',
        description: '',
        video_url: '',
        selectedCategories: [],
        logoPreviewUrl: '',
        logoFileSelected: false,
        logoUploadError: '',
        allCategories: [],
        categorySearchTerm: '',
        softwareCategoriesList: [],
        selectedCategoriesDisplay: [],
        loadingMeta: false,
        urlExists: false,
        checkingUrl: false,
        showName: false,
        showTagline: false,
        showProductPageTagline: false,
        showDescription: false,
        showVideoUrl: false,
        showLogo: false,
        showCategories: false,
        showSubmit: false,

        init() {
            this.allCategories = allCategoriesData.map(cat => ({ ...cat, id: cat.id.toString(), types: Array.isArray(cat.types) ? cat.types : [] }));
            this.loadState();

            if (this.isEditMode) {
                this.showName = true;
                this.showTagline = true;
                this.showProductPageTagline = true;
                this.showDescription = true;
                this.showVideoUrl = true;
                this.showLogo = true;
                this.showCategories = true;
                this.showSubmit = true;
            }

            this.$nextTick(() => {
                this.quill = new Quill('#quill-editor', {
                    modules: {
                        toolbar: [
                            [{ 'header': [2, 3, 4, false] }],
                            ['bold', 'italic', 'underline'],
                            ['blockquote'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link'],
                            ['clean']
                        ]
                    },
                    theme: 'snow',
                    placeholder: 'Provide a detailed description of your product...'
                });

                if (this.description) {
                    this.quill.root.innerHTML = this.description;
                }

                this.quill.on('text-change', () => {
                    this.description = this.quill.root.innerHTML;
                    this.saveState();
                });
            });

            this.pricingCategoriesList = this.allCategories.filter(category =>
                category.types && category.types.includes('Pricing')
            ).sort((a, b) => a.name.localeCompare(b.name));

            this.softwareCategoriesList = this.allCategories.filter(category =>
                !category.types || !category.types.includes('Pricing')
            ).sort((a, b) => a.name.localeCompare(b.name));
            
            this.updateSelectedCategoriesDisplay();

            this.$watch('categorySearchTerm', (value) => {
                const searchTerm = value.toLowerCase().trim();
                const nonPricingCategories = this.allCategories.filter(category =>
                    !category.types || !category.types.includes('Pricing')
                );
                if (!searchTerm) {
                    this.softwareCategoriesList = [...nonPricingCategories].sort((a, b) => a.name.localeCompare(b.name));
                } else {
                    this.softwareCategoriesList = nonPricingCategories.filter(category =>
                        category.name.toLowerCase().includes(searchTerm)
                    ).sort((a, b) => a.name.localeCompare(b.name));
                }
            });

            this.$watch('selectedCategories', () => {
                this.updateSelectedCategoriesDisplay();
                this.saveState();
            }, { deep: true });

            // Watch for changes and save state
            const fieldsToWatch = ['link', 'name', 'tagline', 'product_page_tagline', 'video_url'];
            fieldsToWatch.forEach(field => {
                this.$watch(field, () => this.saveState());
            });
        },

        loadState() {
            const savedState = localStorage.getItem(formId);
            const initialState = {
                link: productData?.link || (formData && formData.link) || '',
                name: productData?.name || (formData && formData.name) || '',
                tagline: (formData && formData.tagline !== undefined) ? formData.tagline : '',
                product_page_tagline: (formData && formData.product_page_tagline !== undefined) ? formData.product_page_tagline : '',
                description: (formData && formData.description !== undefined) ? formData.description : '',
                video_url: (formData && formData.video_url !== undefined) ? formData.video_url : '',
                selectedCategories: (formData && Array.isArray(formData.current_categories) ? formData.current_categories : []).map(id => id.toString()),
            };

            if (savedState) {
                const parsedState = JSON.parse(savedState);
                // We merge saved state with initial state, giving precedence to initial state (from controller) if it exists
                this.link = initialState.link || parsedState.link || '';
                this.name = initialState.name || parsedState.name || '';
                this.tagline = initialState.tagline || parsedState.tagline || '';
                this.product_page_tagline = initialState.product_page_tagline || parsedState.product_page_tagline || '';
                this.description = initialState.description || parsedState.description || '';
                this.video_url = initialState.video_url || parsedState.video_url || '';
                this.selectedCategories = initialState.selectedCategories.length ? initialState.selectedCategories : (parsedState.selectedCategories || []);
            } else {
                Object.assign(this, initialState);
            }
        },

        saveState() {
            const state = {
                link: this.link,
                name: this.name,
                tagline: this.tagline,
                product_page_tagline: this.product_page_tagline,
                description: this.description,
                video_url: this.video_url,
                selectedCategories: this.selectedCategories,
            };
            localStorage.setItem(formId, JSON.stringify(state));
        },

        clearState() {
            localStorage.removeItem(formId);
        },


        updateSelectedCategoriesDisplay() {
            this.selectedCategoriesDisplay = this.selectedCategories
                .map(id => this.allCategories.find(cat => cat.id === id.toString()))
                .filter(cat => cat)
                .sort((a, b) => a.name.localeCompare(b.name));
        },

        get canSubmitForm() {
            if (!this.isEditMode) {
                return !this.urlExists && !this.checkingUrl && this.link.length > 0 && this.name.length > 0 && this.tagline.length > 0 && this.selectedCategories.length > 0;
            }
            return this.tagline.length > 0 && this.selectedCategories.length > 0;
        },

        fetchMetaAndFavicon() {
            return new Promise((resolve, reject) => {
                if (!this.link || this.urlExists || this.isEditMode) {
                    resolve();
                    return;
                };
                
                this.loadingMeta = true;
                
                fetch(`/api/product-meta?url=${encodeURIComponent(this.link)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.title && !this.name) this.name = data.title;
                        if (data.description) {
                            if (this.quill && this.quill.getText().trim() === '') {
                                this.description = data.description;
                                this.quill.root.innerHTML = data.description;
                            }
                        }
                        if (data.favicon && !this.logoFileSelected) {
                            if(!this.isEditMode) {
                            this.logoPreviewUrl = data.favicon;
                            }
                        }
                        if (data.title && !this.tagline && !this.isEditMode) {
                            this.tagline = data.title;
                            this.product_page_tagline = data.title;
                        }
                        resolve();
                    })
                    .catch(error => {
                        console.error('Error fetching meta and favicon:', error);
                        reject(error);
                    })
                    .finally(() => {
                        this.loadingMeta = false;
                    });
            });
        },

        sequentiallyRevealFields() {
            const fields = [
                'showName', 'showTagline', 'showProductPageTagline', 
                'showDescription', 'showVideoUrl', 'showLogo', 
                'showCategories', 'showSubmit'
            ];
            
            let delay = 200;

            this.fetchMetaAndFavicon().finally(() => {
                fields.forEach((field, index) => {
                    setTimeout(() => {
                        this[field] = true;
                    }, index * delay);
                });
            });
        },

        checkUrlUnique() {
            if (!this.link || this.isEditMode) return;
            this.checkingUrl = true;
            clearTimeout(urlCheckTimeout);
            urlCheckTimeout = setTimeout(() => {
                fetch(`/check-product-url?url=${encodeURIComponent(this.link)}`)
                    .then(res => res.json())
                    .then(data => {
                        this.urlExists = data.exists;
                        if (!data.exists) {
                            this.sequentiallyRevealFields();
                        }
                    })
                    .finally(() => {
                        this.checkingUrl = false;
                    });
            }, 400);
        },

        uploadLogo(event) {
            const file = event.target.files[0];
            if (!file) return;

            const allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp', 'image/avif'];
            const maxFileSize = 2048 * 1024; // 2MB

            if (!allowedTypes.includes(file.type)) {
                this.logoUploadError = 'Unsupported file type. Please upload a PNG, JPG, GIF, SVG, WEBP, or AVIF image.';
                this.removePreviewLogo();
                return;
            }

            if (file.size > maxFileSize) {
                this.logoUploadError = 'File is too large. Please upload an image smaller than 2MB.';
                this.removePreviewLogo();
                return;
            }

            this.logoUploadError = '';
            this.logoPreviewUrl = URL.createObjectURL(file);
            this.logoFileSelected = true;
        },

        removePreviewLogo() {
            this.logoPreviewUrl = '';
            this.logoFileSelected = false;
            document.getElementById('logoInput').value = null;
        },

        submitForm(e) {
            if (!this.canSubmitForm || this.logoUploadError) return;
            this.clearState();
            e.target.submit();
        },

        clearForm() {
            if (confirm('Are you sure you want to clear the form? All unsaved changes will be lost.')) {
                this.clearState();
                window.location.reload();
            }
        },
        
        deselectCategory(categoryId) {
            const catIdStr = categoryId.toString();
            this.selectedCategories = this.selectedCategories.filter(id => id !== catIdStr);
        },

        $watch: {
            name(val) {
                // Slug generation is now handled by the backend.
            }
        }
    }
}
</script>
@endpush

@push('styles')
<style>
.loader {
    position: relative;
    width: 60px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.loader .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #ffffff;
    margin: 0 3px;
    animation: dotPulse 1.4s infinite ease-in-out;
}
.loader .dot:nth-child(1) {
    animation-delay: -0.32s;
}
.loader .dot:nth-child(2) {
    animation-delay: -0.16s;
}
.loader .dot:nth-child(3) {
    animation-delay: 0s;
}
@keyframes dotPulse {
    0%, 60%, 100% {
        transform: scale(0.6);
        opacity: 0.4;
    }
    30% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
@endpush

@push('scripts')
<script>
const button = document.getElementById('submit-product-button');
const content = document.getElementById('button-content');
const form = button.closest('form');

form.addEventListener('submit', function (e) {
    // Get current size
    const width = button.offsetWidth;
    const height = button.offsetHeight;

    // Lock size to prevent collapsing
    button.style.width = width + 'px';
    button.style.minHeight = height + 'px';

    // Replace content with loader
    content.innerHTML = `
        <div class="loader">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    `;

    // Allow form to submit
});
</script>
@endpush
