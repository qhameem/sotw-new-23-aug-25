@extends('layouts.app')

@section('content')
@php
    $productDataJson = json_encode($product ?? null);
    $formDataJson = json_encode($displayData ?? []);
    $allCategoriesDataJson = json_encode($categories->map(fn($cat) => ['id' => (string)$cat->id, 'name' => $cat->name, 'types' => $cat->types->pluck('name')]));
@endphp

<script>
    const productData = <?php echo $productDataJson; ?>;
    const formData = <?php echo $formDataJson; ?>;
    const allCategoriesData = <?php echo $allCategoriesDataJson; ?>;
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="productForm(productData, allCategoriesData)">
    <h1 class="font-noto-serif text-2xl font-semibold text-gray-700 mb-4">
        Edit Product: {{ $displayData['name'] ?? $product->name }}
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
    function productForm(productData, allCategoriesData) {
        const formId = `product_form_${productData.id}`;

        return {
            isEditMode: true,
            autoSlug: false, // Never auto-slug on edit from name, unless name is changed.
            productSlug: productData?.slug || '',
            quill: null,
            link: productData?.link || '',
            name: productData?.name || '',
            tagline: productData?.tagline || '',
            product_page_tagline: productData?.product_page_tagline || '',
            description: productData?.description || '',
            video_url: productData?.video_url || '',
            existingLogoUrl: productData?.logo_url || '',
            logoPreviewUrl: '',
            selectedLogoUrl: '',
            logoFileSelected: false,
            logoUploadError: '',
            allCategories: allCategoriesData.map(cat => ({ ...cat, id: cat.id.toString(), types: Array.isArray(cat.types) ? cat.types : [] })),
            categorySearchTerm: '',
            softwareCategoriesList: [],
            selectedCategories: productData?.categories.map(cat => cat.id.toString()) || [],
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
                }, { deep: true });

                this.$watch('name', (val) => {
                    this.productSlug = this.generateSlug(val);
                });
            },

            updateSelectedCategoriesDisplay() {
                this.selectedCategoriesDisplay = this.selectedCategories
                    .map(id => this.allCategories.find(cat => cat.id === id.toString()))
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
    }
</script>
@endpush