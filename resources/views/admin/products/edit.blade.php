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

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="productForm(productData, formData, allCategoriesData)">
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

@push('scripts')
<script>
function productForm(productData = null, formData = [], allCategoriesData = {}) {
    let urlCheckTimeout;
    return {
        isEditMode: !!productData,
        quill: null,
        link: productData?.link || (formData && formData.link) || '',
        name: productData?.name || (formData && formData.name) || '',
        slug: productData?.slug || (formData && formData.slug) || '',
        tagline: (formData && formData.tagline !== undefined) ? formData.tagline : '',
        product_page_tagline: (formData && formData.product_page_tagline !== undefined) ? formData.product_page_tagline : '',
        description: (formData && formData.description !== undefined) ? formData.description : '',
        logoPreviewUrl: '',
        logoFileSelected: false,
        selectedCategories: (formData && Array.isArray(formData.current_categories) ? formData.current_categories : []).map(id => id.toString()),
        allCategories: allCategoriesData.map(cat => ({ ...cat, id: cat.id.toString(), types: Array.isArray(cat.types) ? cat.types : [] })),
        categorySearchTerm: '',
        softwareCategoriesList: [],
        selectedCategoriesDisplay: [],
        loadingMeta: false,
        urlExists: false,
        checkingUrl: false,
        autoSlug: !productData,

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
        },
        
        get pricingCategories() {
             return this.allCategories.filter(category =>
                category.types && category.types.includes('Pricing')
            ).sort((a, b) => a.name.localeCompare(b.name));
        },

        updateSelectedCategoriesDisplay() {
            this.selectedCategoriesDisplay = this.selectedCategories
                .map(id => this.allCategories.find(cat => cat.id === id.toString()))
                .filter(cat => cat)
                .sort((a, b) => a.name.localeCompare(b.name));
        },
        
        get canSubmitForm() {
            if (!this.isEditMode) {
                return !this.urlExists && !this.checkingUrl && this.link.length > 0 && this.name.length > 0 && this.slug.length > 0 && this.tagline.length > 0 && this.selectedCategories.length > 0;
            }
            return this.tagline.length > 0 && this.selectedCategories.length > 0;
        },

        fetchMetaAndFavicon() {
            if (!this.link || this.urlExists || this.isEditMode) return;
            this.loadingMeta = true;
            fetch(`/api/product-meta?url=${encodeURIComponent(this.link)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.title && !this.name) this.name = data.title;
                    if (data.slug && !this.slug && this.autoSlug) this.slug = data.slug;
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
                })
                .finally(() => {
                    this.loadingMeta = false;
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
                            this.fetchMetaAndFavicon();
                        }
                    })
                    .finally(() => {
                        this.checkingUrl = false;
                    });
            }, 400);
        },

        uploadLogo(event) {
            const file = event.target.files[0];
            if (file) {
                this.logoPreviewUrl = URL.createObjectURL(file);
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
            e.target.submit();
        },
        
        generateSlug(text) {
            return text.toString().toLowerCase()
                .replace(/\s+/g, '-')          
                .replace(/[^\w\-]+/g, '')      
                .replace(/\-\-+/g, '-')         
                .replace(/^-+/, '')             
                .replace(/-+$/, '');            
        },

        updateSlugOnNameChange() {
            if (this.autoSlug) {
                this.slug = this.generateSlug(this.name);
            }
        },
        deselectCategory(categoryId) {
            this.selectedCategories = this.selectedCategories.filter(id => id !== categoryId);
        }
    };
}
</script>
@endpush