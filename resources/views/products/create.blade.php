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
<div class="relative" x-data="productForm('{{ json_encode($product ?? null) }}', '{{ json_encode($displayData ?? []) }}', '{{ json_encode($allCategoriesData ?? []) }}', '{{ json_encode($allTechStacksData ?? []) }}')">
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
    <div class="sticky top-6 space-y-6">
        <!-- Submission Checklist -->
        <div x-data="{
            isUrlProvided: false,
            isProductIdentityComplete: false,
            isCategorizationComplete: false,
            isMediaAndBrandingComplete: false,
            isDescriptionComplete: false,
        }" @checklist-update.window="
            if ($event.detail.isUrlProvided !== undefined) isUrlProvided = $event.detail.isUrlProvided;
            if ($event.detail.isProductIdentityComplete !== undefined) isProductIdentityComplete = $event.detail.isProductIdentityComplete;
            if ($event.detail.isCategorizationComplete !== undefined) isCategorizationComplete = $event.detail.isCategorizationComplete;
            if ($event.detail.isMediaAndBrandingComplete !== undefined) isMediaAndBrandingComplete = $event.detail.isMediaAndBrandingComplete;
            if ($event.detail.isDescriptionComplete !== undefined) isDescriptionComplete = $event.detail.isDescriptionComplete;
        " class="md:w-5/6 mx-auto w-full mt-6 p-4 rounded-lg bg-white border">
            <h2 class="font-semibold text-gray-800 mb-3 text-base">Submission Checklist</h2>
            <ul class="space-y-2 text-xs">
                <li class="flex items-center transition-all duration-300" :class="{ 'text-gray-800 font-semibold': isUrlProvided, 'text-gray-400': !isUrlProvided }">
                    <svg x-show="isUrlProvided" class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="!isUrlProvided" class="w-4 h-4 text-gray-300 mr-2" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/></svg>
                    <span>Product URL</span>
                </li>
                <li class="flex items-center transition-all duration-300" :class="{ 'text-gray-800 font-semibold': isProductIdentityComplete, 'text-gray-400': !isProductIdentityComplete }">
                    <svg x-show="isProductIdentityComplete" class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="!isProductIdentityComplete" class="w-4 h-4 text-gray-300 mr-2" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/></svg>
                    <span>Product Identity</span>
                </li>
                <li class="flex items-center transition-all duration-300" :class="{ 'text-gray-800 font-semibold': isCategorizationComplete, 'text-gray-400': !isCategorizationComplete }">
                    <svg x-show="isCategorizationComplete" class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="!isCategorizationComplete" class="w-4 h-4 text-gray-300 mr-2" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/></svg>
                    <span>Categorization</span>
                </li>
                <li class="flex items-center transition-all duration-300" :class="{ 'text-gray-800 font-semibold': isMediaAndBrandingComplete, 'text-gray-400': !isMediaAndBrandingComplete }">
                    <svg x-show="isMediaAndBrandingComplete" class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="!isMediaAndBrandingComplete" class="w-4 h-4 text-gray-300 mr-2" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/></svg>
                    <span>Media and Branding</span>
                </li>
                <li class="flex items-center transition-all duration-300" :class="{ 'text-gray-800 font-semibold': isDescriptionComplete, 'text-gray-400': !isDescriptionComplete }">
                    <svg x-show="isDescriptionComplete" class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="!isDescriptionComplete" class="w-4 h-4 text-gray-300 mr-2" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/></svg>
                    <span>Detailed Description</span>
                </li>
            </ul>
        </div>

        <!-- Tips Section -->
        <div class="md:w-5/6 mx-auto w-full p-2 rounded flex flex-col bg-gradient-to-tr from-white to-gray-50">
            <h2 class="font-noto-serif text-lg text-gray-700 font-semibold mb-4">&#10003; Tips</h2>
            <div class="prose prose-xs text-xs max-w-none text-gray-600 space-y-3">
                <p class="text-gray-800 font-medium"><span>Product URL</span></p>
                <ul class="list-disc ml-3 space-y-2 text-gray-600">
                    <li>Provide a direct link to your product's main page.</li>
                    <li>Avoid links to articles, blog posts, or press releases.</li>
                </ul>
                <p class="text-gray-800 font-medium"><span>Name & Tagline</span></p>
                <ul class="list-disc ml-3 space-y-2 text-gray-600">
                    <li>Use the official product name.</li>
                    <li>The tagline should be a concise summary.</li>
                </ul>
                <p class="text-gray-800 font-medium"><span>Description</span></p>
                <ul class="list-disc ml-3 space-y-2 text-gray-600">
                    <li>Briefly describe your product, its features, and value proposition.</li>
                </ul>
                <p class="text-gray-800 font-medium"><span>Logo</span></p>
                <ul class="list-disc ml-3 space-y-2 text-gray-600">
                    <li>Upload a clear, high-quality logo (square is preferred).</li>
                </ul>
                <p class="text-gray-800 font-medium"><span>Categories</span></p>
                <ul class="list-disc ml-3 space-y-2 text-gray-600">
                    <li>Select the most relevant categories.</li>
                    <li>Choose at least one "Pricing" and one "Software Category".</li>
                </ul>
                <p>Submissions are reviewed by our team. Thank you for contributing!</p>
            </div>
        </div>
    </div>
@endsection


@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
   .ql-toolbar {
        background-color: #f9fafb;
        border-top-left-radius: 0.375rem;
        border-top-right-radius: 0.375rem;
        border-color: #d1d5db;
    }
    .ql-container {
        border-bottom-left-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
        border-color: #d1d5db;
    }
   .ql-editor {
       min-height: 250px;
       font-size: 0.875rem;
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
    const formId = "{{ isset($product) ? 'product_form_' . $product->id : 'new_product_form' }}";
    if (confirm('Are you sure you want to clear the form? All unsaved changes will be lost.')) {
        localStorage.removeItem(formId);
        window.location.reload();
    }
}

function productForm(productDataJson, formDataJson, allCategoriesDataJson, allTechStacksDataJson) {
    const productData = JSON.parse(productDataJson);
    const formData = JSON.parse(formDataJson);
    const allCategoriesData = JSON.parse(allCategoriesDataJson);
    const allTechStacksData = JSON.parse(allTechStacksDataJson);
    let urlCheckTimeout;
    const formId = productData ? `product_form_${productData.id}` : 'new_product_form';

    return {
        isEditMode: !!productData,
        autoSlug: !productData,
        quill: null,
        name_max_length: 50,
        tagline_max_length: 60,
        product_page_tagline_max_length: 250,
        logoPreviewUrl: '',
        fetchedLogos: [],
        selectedLogoUrl: '',
        existingLogoUrl: '',
        fetchedOgImage: '',
        fetchedOgImages: [],
        selectedOgImages: [],
        logoFileSelected: false,
        logoUploadError: '',
        mediaPreviewUrl: '',
        allCategories: [],
        categorySearchTerm: '',
        techStackSearchTerm: '',
        softwareCategoriesList: [],
        techStacksList: [],
        selectedCategoriesDisplay: [],
        selectedTechStacksDisplay: [],
        loadingMeta: false,
        urlExists: false,
        checkingUrl: false,
        errors: {},
        fetchError: false,
        fetchingStatusMessage: '',

        // Directly initialize reactive properties from formData
        link: formData.link || '',
        name: formData.name || '',
        productSlug: formData.slug || '',
        tagline: formData.tagline || '',
        product_page_tagline: formData.product_page_tagline || '',
        description: formData.description || '',
        video_url: productData?.video_url || '',
        selectedCategories: (Array.isArray(formData.current_categories) ? formData.current_categories : []).map(id => id.toString()),
        selectedTechStacks: (Array.isArray(formData.current_tech_stacks) ? formData.current_tech_stacks : []).map(id => id.toString()),

        init() {
            this.allCategories = allCategoriesData.map(cat => ({ ...cat, id: cat.id.toString(), types: Array.isArray(cat.types) ? cat.types : [] }));
            this.allTechStacks = allTechStacksData.map(ts => ({ ...ts, id: ts.id.toString() }));
            this.techStacksList = [...this.allTechStacks].sort((a, b) => a.name.localeCompare(b.name));

            this.$nextTick(() => {
                this.existingLogoUrl = productData?.logo ? (productData.logo.startsWith('http') ? productData.logo : `/storage/${productData.logo}`) : '';
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
                    this.$dispatch('checklist-update', { isDescriptionComplete: this.isDescriptionComplete });
                });
            });

            this.pricingCategoriesList = this.allCategories.filter(category =>
                category.types && category.types.includes('Pricing')
            ).sort((a, b) => a.name.localeCompare(b.name));

            this.softwareCategoriesList = this.allCategories.filter(category =>
                !category.types || !category.types.includes('Pricing')
            ).sort((a, b) => a.name.localeCompare(b.name));
            
            this.updateSelectedCategoriesDisplay();
            this.updateSelectedTechStacksDisplay();

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
                this.$dispatch('checklist-update', { isCategorizationComplete: this.isCategorizationComplete });
            }, { deep: true });

            this.$watch('selectedTechStacks', () => {
                this.updateSelectedTechStacksDisplay();
            }, { deep: true });

            this.$watch('techStackSearchTerm', (value) => {
                const searchTerm = value.toLowerCase().trim();
                if (!searchTerm) {
                    this.techStacksList = [...this.allTechStacks].sort((a, b) => a.name.localeCompare(b.name));
                } else {
                    this.techStacksList = this.allTechStacks.filter(ts =>
                        ts.name.toLowerCase().includes(searchTerm)
                    ).sort((a, b) => a.name.localeCompare(b.name));
                }
            });

            this.$watch('link', (newLink, oldLink) => {
                if (newLink !== oldLink) {
                    this.resetFormFields();
                }
                this.$dispatch('checklist-update', { isUrlProvided: this.isUrlProvided });
            });

            this.$watch('name', (val) => {
                this.productSlug = this.generateSlug(val);
                if (val.length > this.name_max_length) {
                    this.name = val.substring(0, this.name_max_length);
                }
                this.$dispatch('checklist-update', { isProductIdentityComplete: this.isProductIdentityComplete });
            });

            this.$watch('tagline', (val) => {
                if (val.length > this.tagline_max_length) {
                    this.tagline = val.substring(0, this.tagline_max_length);
                }
                this.$dispatch('checklist-update', { isProductIdentityComplete: this.isProductIdentityComplete });
            });

            this.$watch('product_page_tagline', (val) => {
                if (val.length > this.product_page_tagline_max_length) {
                    this.product_page_tagline = val.substring(0, this.product_page_tagline_max_length);
                }
                this.$dispatch('checklist-update', { isProductIdentityComplete: this.isProductIdentityComplete });
            });
        },

        resetFormFields() {
            this.name = '';
            this.tagline = '';
            this.product_page_tagline = '';
            if (this.quill) {
                this.quill.root.innerHTML = '';
            }
            this.description = '';
            this.video_url = '';
            this.selectedCategories = [];
            this.selectedTechStacks = [];
            this.logoPreviewUrl = '';
            this.fetchedLogos = [];
            this.selectedLogoUrl = '';
            this.fetchedOgImage = '';
            this.fetchedOgImages = [];
            this.selectedOgImages = [];
            this.logoFileSelected = false;
            this.logoUploadError = '';
            this.urlExists = false;
            this.fetchError = false;
            this.fetchingStatusMessage = '';
        },

        updateSelectedCategoriesDisplay() {
            this.selectedCategoriesDisplay = this.selectedCategories
                .map(id => this.allCategories.find(cat => cat.id === id.toString()))
                .filter(cat => cat)
                .sort((a, b) => a.name.localeCompare(b.name));
        },

        updateSelectedTechStacksDisplay() {
            this.selectedTechStacksDisplay = this.selectedTechStacks
                .map(id => this.allTechStacks.find(ts => ts.id === id.toString()))
                .filter(ts => ts)
                .sort((a, b) => a.name.localeCompare(b.name));
        },

        get isUrlProvided() {
            return this.link.trim() !== '' && this.link.startsWith('http');
        },

        get isProductIdentityComplete() {
            return this.name.trim() !== '' && this.tagline.trim() !== '' && this.product_page_tagline.trim() !== '';
        },

        get isCategorizationComplete() {
            const hasPricing = this.selectedCategories.some(id => {
                const cat = this.allCategories.find(c => c.id === id);
                return cat && cat.types.includes('Pricing');
            });
            const hasSoftware = this.selectedCategories.some(id => {
                const cat = this.allCategories.find(c => c.id === id);
                return cat && !cat.types.includes('Pricing');
            });
            return hasPricing && hasSoftware;
        },

        get isMediaAndBrandingComplete() {
            return this.logoFileSelected || this.selectedLogoUrl || (this.isEditMode && this.existingLogoUrl);
        },

        get isDescriptionComplete() {
            const cleanDescription = this.description.replace(/<[^>]*>/g, '').trim();
            return cleanDescription.length > 0;
        },

        get canSubmitForm() {
            if (!this.isEditMode) {
                return !this.urlExists && !this.checkingUrl && this.link.length > 0 && this.name.length > 0 && this.tagline.length > 0 && this.selectedCategories.length > 0;
            }
            return this.tagline.length > 0 && this.selectedCategories.length > 0;
        },


        fetchUrlData() {
            if (!this.link || this.isEditMode) return;
            this.loadingMeta = true;
            this.fetchError = false;
            this.fetchingStatusMessage = 'Fetching data from URL...';

            fetch(`/fetch-url-data?url=${encodeURIComponent(this.link)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    this.$nextTick(() => {
                        this.name = data.title || this.name;
                        this.tagline = data.description || this.tagline;
                        this.product_page_tagline = data.description || this.product_page_tagline;
                        if (this.quill) {
                            this.quill.root.innerHTML = data.description || '';
                        }
                        if (data.og_images && data.og_images.length > 0) {
                            this.fetchedOgImages = data.og_images;
                        }
                        if (data.logos && data.logos.length > 0) {
                            this.fetchedLogos = data.logos;
                        }
                        if (data.categories && Array.isArray(data.categories) && data.categories.length > 0) {
                            const categoryIds = data.categories.map(id => id.toString());
                            this.selectedCategories = Array.from(new Set([...this.selectedCategories, ...categoryIds]));
                        }
                        if (data.tech_stacks && Array.isArray(data.tech_stacks) && data.tech_stacks.length > 0) {
                            const techStackIds = data.tech_stacks.map(id => id.toString());
                            this.selectedTechStacks = Array.from(new Set([...this.selectedTechStacks, ...techStackIds]));
                        }
                        this.fetchingStatusMessage = 'Data fetched successfully!';
                    });
                })
                .catch(error => {
                    console.error('Error fetching URL data:', error);
                    this.fetchError = true;
                    this.fetchingStatusMessage = '';
                })
                .finally(() => {
                    this.loadingMeta = false;
                });
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
            this.selectedLogoUrl = ''; // Clear fetched logo selection
            this.$dispatch('checklist-update', { isMediaAndBrandingComplete: this.isMediaAndBrandingComplete });
        },

        removePreviewLogo() {
            this.logoPreviewUrl = '';
            this.logoFileSelected = false;
            this.selectedLogoUrl = '';
            document.getElementById('logoInput').value = null;
        },

        selectLogo(logoUrl) {
            this.selectedLogoUrl = logoUrl;
            this.$dispatch('checklist-update', { isMediaAndBrandingComplete: this.isMediaAndBrandingComplete });
            this.logoPreviewUrl = ''; // Clear file preview
            this.logoFileSelected = false;
            document.getElementById('logoInput').value = null;
        },

        removeFetchedOgImage() {
            this.fetchedOgImage = '';
        },

        showMediaPreview(event) {
            const file = event.target.files[0];
            if (file) {
                this.mediaPreviewUrl = URL.createObjectURL(file);
            } else {
                this.mediaPreviewUrl = '';
            }
        },

        selectFetchedImage(imageUrl) {
            this.selectedLogoUrl = imageUrl;
            this.logoPreviewUrl = '';
            this.logoFileSelected = false;
            document.getElementById('logoInput').value = null;
        },

        toggleOgImage(imageUrl) {
            const index = this.selectedOgImages.indexOf(imageUrl);
            if (index > -1) {
                this.selectedOgImages.splice(index, 1);
            } else {
                if (this.selectedOgImages.length < 2) {
                    this.selectedOgImages.push(imageUrl);
                }
            }
        },

        validateForm() {
            this.errors = {};
            if (!this.name) {
                this.errors.name = 'Product Name is required.';
            }
            if (!this.tagline) {
                this.errors.tagline = 'Tagline (List Page) is required.';
            }
            if (!this.product_page_tagline) {
                this.errors.product_page_tagline = 'Tagline (Details Page) is required.';
            }

            const hasPricingCategory = this.selectedCategories.some(id => {
                const cat = this.allCategories.find(c => c.id === id);
                return cat && cat.types.includes('Pricing');
            });

            const hasSoftwareCategory = this.selectedCategories.some(id => {
                const cat = this.allCategories.find(c => c.id === id);
                return cat && !cat.types.includes('Pricing');
            });

            if (!hasPricingCategory || !hasSoftwareCategory) {
                this.errors.categories = 'At least one Software and one Pricing category are required.';
            }

            return Object.keys(this.errors).length === 0;
        },

        showLoader() {
            const button = document.getElementById('submit-product-button');
            const content = document.getElementById('button-content');
            const loaderContainer = document.getElementById('loader-container');
            
            if (button && content && loaderContainer) {
                // Hide text and show loader
                content.innerHTML = ''; // Clear the button text
                loaderContainer.innerHTML = `
                    <div class="loader">
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                    </div>
                `;
                button.disabled = true;
            }
        },

        submitForm(e) {
            if (!this.validateForm() || this.logoUploadError) {
                this.$nextTick(() => {
                    const firstError = this.$el.querySelector('.text-red-600');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                return;
            }
            this.showLoader();
            this.$nextTick(() => {
                e.target.submit();
            });
        },

        clearForm() {
            if (confirm('Are you sure you want to clear the form? All unsaved changes will be lost.')) {
                window.location.reload();
            }
        },
        
        deselectCategory(categoryId) {
            const catIdStr = categoryId.toString();
            this.selectedCategories = this.selectedCategories.filter(id => id !== catIdStr);
        },

        deselectTechStack(techStackId) {
            const tsIdStr = techStackId.toString();
            this.selectedTechStacks = this.selectedTechStacks.filter(id => id !== tsIdStr);
        },

        generateSlug(text) {
            return text
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim()
                .replace(/\s+/g, '-')
                .replace(/[^\w-]+/g, '')
                .replace(/--+/g, '-');
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
</script>
@endpush
