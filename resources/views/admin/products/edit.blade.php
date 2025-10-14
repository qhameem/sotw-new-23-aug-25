@extends('layouts.app')

@section('content')
@php
    $product->load('media', 'categories.types');
    $allCategoriesMapped = $allCategories->map(fn($cat) => ['id' => (string)$cat->id, 'name' => $cat->name, 'types' => $cat->types->pluck('name')])->values();
    $bestForCategoriesMapped = $bestForCategories->map(fn($cat) => ['id' => (string)$cat->id, 'name' => $cat->name])->values();
    $pricingCategoriesMapped = $pricingCategories->map(fn($cat) => ['id' => (string)$cat->id, 'name' => $cat->name])->values();
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
     x-data="productForm"
     data-product='@json($product)'
     data-all-categories='@json($allCategoriesMapped)'
     data-best-for-categories='@json($bestForCategoriesMapped)'
     data-pricing-categories='@json($pricingCategoriesMapped)'>
    <h1 class="font-noto-serif text-2xl font-semibold text-gray-700 mb-4">
        Edit Product: {{ $product->name }}
    </h1>

    @if(session('success'))
        <div class="mb-4 text-green-700 bg-green-100 rounded p-2">{{ session('success') }}</div>
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
            <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="bg-gradient-to-t from-white to-gray-50 border border-gray-300 text-sm p-6 rounded md:px-8 w-full" @submit.prevent="submitForm">
                @method('PUT')
                @include('products.partials._form', ['types' => $types])
            </form>
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
   }
</style>
@endpush

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productForm', function () {
            return {
                productData: JSON.parse(this.$el.dataset.product),
                allCategoriesData: JSON.parse(this.$el.dataset.allCategories),
                bestForCategoriesData: JSON.parse(this.$el.dataset.bestForCategories),
                pricingCategoriesData: JSON.parse(this.$el.dataset.pricingCategories),
                isEditMode: true,
                autoSlug: false,
                productSlug: '',
                quill: null,
                link: '',
                name: '',
                tagline: '',
                product_page_tagline: '',
                description: '',
                video_url: '',
                existingLogoUrl: '',
                logoPreviewUrl: '',
                selectedLogoUrl: '',
                logoFileSelected: false,
                logoUploadError: '',
                mediaPreviewUrls: [],
                allCategories: [],
                categorySearchTerm: '',
                softwareCategoriesList: [],
                bestForCategoriesList: [],
                pricingCategoriesList: [],
                bestForSearchTerm: '',
                selectedCategories: [],
                selectedCategoriesDisplay: [],
                showName: true,
                showTagline: true,
                showProductPageTagline: true,
                showDescription: true,
                showVideoUrl: true,
                showLogo: true,
                showCategories: true,
                showSubmit: true,
                fetchingDetails: false,
                loadingMeta: false,
                checkingUrl: false,
 
                init() {
                    this.productSlug = this.productData.slug || '';
                    this.link = this.productData.link || '';
                    this.name = this.productData.name || '';
                    this.tagline = this.productData.tagline || '';
                    this.product_page_tagline = this.productData.product_page_tagline || '';
                    this.description = this.productData.description || '';
                    this.video_url = this.productData.video_url || '';
                    this.existingLogoUrl = this.productData.logo_url || '';
                    this.selectedCategories = this.productData.categories.map(cat => cat.id.toString()) || [];
                    this.allCategories = this.allCategoriesData.map(cat => ({ ...cat, id: cat.id.toString(), types: Array.isArray(cat.types) ? cat.types : [] }));
                    this.bestForCategoriesList = this.bestForCategoriesData.map(cat => ({ ...cat, id: cat.id.toString() }));
                    this.pricingCategoriesList = this.pricingCategoriesData.map(cat => ({ ...cat, id: cat.id.toString() }));

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
                        });
                    });

                    this.softwareCategoriesList = this.allCategories.filter(category =>
                        !category.types.includes('Best For') && !category.types.includes('Pricing')
                    ).sort((a, b) => a.name.localeCompare(b.name));

                    this.updateSelectedCategoriesDisplay();

                    this.$watch('bestForSearchTerm', (value) => {
                        const bestForSearch = value.toLowerCase().trim();
                        if (!bestForSearch) {
                            this.bestForCategoriesList = [...this.bestForCategoriesData].sort((a, b) => a.name.localeCompare(b.name));
                        } else {
                            this.bestForCategoriesList = this.bestForCategoriesData.filter(category =>
                                category.name.toLowerCase().includes(bestForSearch)
                            ).sort((a, b) => a.name.localeCompare(b.name));
                        }
                    });

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
                    }, { deep: true });

                    this.$watch('name', (val) => {
                        this.productSlug = this.generateSlug(val);
                    });
                },

                updateSelectedCategoriesDisplay() {
                    this.selectedCategoriesDisplay = this.selectedCategories
                        .map(id => {
                            const category = this.allCategories.find(cat => cat.id === id.toString());
                            return category;
                        })
                        .filter(cat => cat)
                        .sort((a, b) => a.name.localeCompare(b.name));
                },

                get canSubmitForm() {
                    return this.tagline.length > 0 && this.selectedCategories.length > 0;
                },

                uploadLogo(event) {
                    const file = event.target.files[0];
                    if (file) {
                        if (file.type === 'image/svg+xml') {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.logoPreviewUrl = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        } else {
                            this.logoPreviewUrl = URL.createObjectURL(file);
                        }
                        this.logoFileSelected = true;
                    }
                },

                selectLogo(logo) {
                    this.selectedLogoUrl = logo;
                    this.logoPreviewUrl = '';
                    this.logoFileSelected = false;
                    document.getElementById('logoInput').value = null;
                },

                showMediaPreview(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.mediaPreviewUrl = URL.createObjectURL(file);
                    }
                },

                removePreviewLogo() {
                    this.logoPreviewUrl = '';
                    this.logoFileSelected = false;
                    document.getElementById('logoInput').value = null;
                },

                submitForm(e) {
                    if (!this.canSubmitForm) return;
                    document.querySelector('input[name=description]').value = this.quill.root.innerHTML;
                    e.target.submit();
                },

                fetchUrlData() {
                    if (!this.link) {
                        this.fetchError = 'Please enter a URL.';
                        return;
                    }
                    this.fetchingDetails = true;
                    this.fetchingStatusMessage = 'Fetching details...';
                    this.fetchError = '';

                    fetch(`/api/fetch-product-meta?url=${encodeURIComponent(this.link)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                this.fetchError = data.error;
                                return;
                            }
                            this.name = data.name || this.name;
                            this.tagline = data.tagline || this.tagline;
                            this.product_page_tagline = data.product_page_tagline || this.product_page_tagline;
                            this.description = data.description || this.description;
                            if (this.quill) {
                                this.quill.root.innerHTML = this.description;
                            }
                            this.fetchedLogos = data.logos || [];
                            if (data.favicon && !this.selectedLogoUrl && !this.logoFileSelected) {
                                this.selectedLogoUrl = data.favicon;
                            }
                            this.fetchedOgImages = data.og_images || [];
                        })
                        .catch(error => {
                            this.fetchError = 'Failed to fetch data. Please try again.';
                        })
                        .finally(() => {
                            this.fetchingDetails = false;
                            this.fetchingStatusMessage = '';
                        });
                },

                checkUrlUnique() {
                    if (this.isEditMode) return;
                    this.loadingMeta = true;
                    this.checkingUrl = true;
                    setTimeout(() => {
                        this.loadingMeta = false;
                        this.checkingUrl = false;
                    }, 2000);
                },

                deselectCategory(categoryId) {
                    this.selectedCategories = this.selectedCategories.filter(id => id.toString() !== categoryId.toString());
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
            };
        });
    });
</script>
@endpush
