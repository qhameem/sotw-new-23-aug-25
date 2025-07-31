@csrf
@if(isset($product))
    @method('PUT')
@endif

<div class="space-y-6">
    <!-- Product URL -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4" for="product_url">Product URL<span class="text-red-500 ml-1"><span class="text-red-500 ml-1">*</span></span></label>
           
        </div>
        <div class="md:col-span-3">
            <div class="flex items-center relative">
                <input type="url" id="product_url" name="link" x-model="link" value="{{ old('link', $product->link ?? '') }}" @input.debounce.500ms="checkUrlUnique" @blur="checkUrlUnique" @keydown.enter.prevent="checkUrlUnique" class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 text-sm focus:border-primary-500 focus:ring-primary-500" required>
                <template x-if="checkingUrl && !isEditMode">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </template>
            </div>
            <template x-if="urlExists && !checkingUrl && !isEditMode">
                <div class="text-red-600 text-sm mt-1">This URL is already listed. Please enter a different product URL.</div>
            </template>
             <span class="text-xs bg-amber-50 text-gray-600 tracking-tight">* Paste URL → wait a few seconds → we'll try to fill out the other fields automatically</span>
            
        </div>
    </div>

    <!-- Name -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4" for="product_name">Name<span class="text-red-500 ml-1">*</span></label>
        </div>
        <div class="md:col-span-3">
            <input type="text" id="product_name" name="name" x-model="name" value="{{ old('name', $product->name ?? '') }}" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500" required @input="updateSlugOnNameChange">
            <template x-if="slug && !isEditMode">
                <div class="mt-1">
                    <span class="text-xs text-gray-500">Slug:</span>
                    <span class="text-xs text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded-md" x-text="slug"></span>
                    <input type="hidden" name="slug" x-model="slug">
                </div>
            </template>
            <template x-if="slug && isEditMode">
                <div class="mt-1">
                    <span class="text-xs text-gray-500">Slug:</span>
                    <span class="text-xs text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded-md" x-text="slug"></span>
                    <input type="hidden" name="slug" x-model="slug">
                </div>
            </template>
        </div>
        
    </div>

    <!-- Tagline -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4" for="tagline">Tagline<span class="text-red-500 ml-1">*</span></label>
        </div>
        <div class="md:col-span-3">
            <input type="text" id="tagline" name="tagline" x-model="tagline" value="{{ old('tagline', $product->tagline ?? '') }}" maxlength="150" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500" required>
            <p class="text-xs text-gray-500 mt-1">Describe your product in 150 characters or less.</p>
        </div>
    </div>

    <!-- Tagline on Product Page -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4" for="product_page_tagline">Product Page Tagline<span class="text-red-500 ml-1">*</span></label>
        </div>
        <div class="md:col-span-3">
            <input type="text" id="product_page_tagline" name="product_page_tagline" value="{{ old('product_page_tagline', $product->product_page_tagline ?? '') }}" x-model="product_page_tagline" maxlength="150" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500" required>
        </div>
    </div>

    <!-- Description -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4" for="quill-editor">Description<span class="text-red-500 ml-1">*</span></label>
        </div>
        <div class="md:col-span-3">
            <div id="quill-editor" style="height: 300px;" class="mt-1 bg-white text-gray-900 border border-gray-300 rounded-md"></div>
            <input type="hidden" name="description" id="description" x-model="description">
        </div>
    </div>

    <!-- Video URL -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4" for="video_url">Video URL</label>
        </div>
        <div class="md:col-span-3">
            <input type="url" id="video_url" name="video_url" x-model="video_url" value="{{ old('video_url', $product->video_url ?? '') }}" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500">
            <p class="text-xs text-gray-500 mt-1">Enter a YouTube or Vimeo URL to embed a video on the product page.</p>
        </div>
    </div>

    <!-- Logo Upload Section -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4">{{ isset($product) && $product->logo ? 'Logo' : 'Product Logo' }}</label>
        </div>
        <div class="md:col-span-3">
            @if(isset($product) && $product->logo)
            <div class="mb-2">
                <label class="block mb-1 text-xs font-medium text-gray-600">Current Live Logo</label>
                <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="Current Live Logo" class="w-20 h-20 object-cover rounded-md mb-2 border">
            </div>
            @endif

            @if(isset($product) && $product->approved && $product->has_pending_edits && $product->proposed_logo_path)
            <div class="mb-4">
                <label class="block mb-1 text-xs font-medium text-yellow-700">Currently Proposed Logo (Pending Review)</label>
                <img src="{{ asset('storage/' . $product->proposed_logo_path) }}" alt="Proposed Logo" class="w-20 h-20 object-cover rounded-md mb-2 border border-yellow-400">
            </div>
            @endif

            <input type="file" name="logo" id="logoInput" class="hidden" accept="image/png,image/jpeg,image/gif,image/svg+xml,image/webp,image/avif" @change="uploadLogo">

            <div x-show="!logoPreviewUrl"
                 @dragover.prevent="$el.classList.add('border-primary-500', 'bg-gray-50')"
                 @dragleave.prevent="$el.classList.remove('border-primary-500', 'bg-gray-50')"
                 @drop.prevent="
                    $el.classList.remove('border-primary-500', 'bg-gray-50');
                    const files = $event.dataTransfer.files;
                    if (files.length > 0) {
                        document.getElementById('logoInput').files = files;
                        uploadLogo({ target: { files: files } });
                    }
                 "
                 @click="document.getElementById('logoInput').click()"
                 class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-primary-500 transition-colors duration-150 ease-in-out">
                <div class="space-y-1 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex text-sm text-gray-600">
                        <p class="pl-1">Drag & drop, or click to select a new logo</p>
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG, GIF, SVG, WEBP, AVIF up to 2MB</p>
                </div>
            </div>

            <template x-if="logoPreviewUrl">
                <div class="mt-2 relative group">
                    <label class="block mb-1 text-xs font-medium text-gray-600">New Logo Preview:</label>
                    <img :src="logoPreviewUrl" alt="New Logo Preview" class="w-24 h-24 object-cover rounded-md border border-gray-300">
                    <button @click="removePreviewLogo()" type="button" class="absolute top-0 right-0 -mt-2 -mr-2 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-opacity focus:outline-none">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
            </template>
            <template x-if="logoUploadError">
                <div class="text-red-600 text-sm mt-1" x-text="logoUploadError"></div>
            </template>

            @if(isset($product))
            <div class="mt-4">
                <label class="flex items-center text-sm">
                    <input type="checkbox" name="remove_logo" value="1" class="mr-2 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                    Propose to remove logo
                </label>
            </div>
            @endif
        </div>
    </div>

    <!-- Categories Section -->
    <div class="grid md:grid-cols-4 gap-4 items-start">
        <div class="md:col-span-1">
            <label class="block font-semibold md:text-left md:pr-4">Categories<span class="text-red-500 ml-1">*</span></label>
        </div>
        <div class="md:col-span-3">
            @error('categories')<div class="text-red-600 text-sm mb-2">{{ $message }}</div>@enderror

            <div class="mb-3 min-h-[2.5rem]">
                <div class="flex flex-wrap gap-2" x-show="selectedCategoriesDisplay.length > 0">
                    <template x-for="category in selectedCategoriesDisplay" :key="category.id">
                        <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full group hover:bg-gray-200">
                            <span x-text="category.name" class="truncate max-w-[180px]" :title="category.name"></span>
                            <button @click.prevent="deselectCategory(category.id)" type="button" class="ml-1.5 -mr-1 flex-shrink-0 inline-flex items-center justify-center h-4 w-4 rounded-full text-gray-400 hover:text-gray-600 focus:outline-none focus:bg-gray-300" :aria-label="'Remove ' + category.name">
                                <span class="sr-only" x-text="'Remove ' + category.name"></span>
                                <svg class="h-2.5 w-2.5" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                    <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                </svg>
                            </button>
                        </span>
                    </template>
                </div>
                <p class="text-xs text-gray-500" x-show="selectedCategoriesDisplay.length === 0">
                    Selected categories will appear here.
                </p>
            </div>

            <div class="mb-4">
                <h3 class="text-md font-semibold text-gray-600 mb-2">Pricing Model<span class="text-red-500 text-xs"><span class="text-red-500 ml-1">*</span> (select at least one)</span></h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-1 border rounded-md p-3">
                    <template x-for="category in pricingCategoriesList" :key="category.id">
                        <label class="flex items-center py-1 text-xs cursor-pointer hover:bg-gray-50 px-1 rounded">
                            <input type="checkbox" name="categories[]" :value="category.id" x-model="selectedCategories" class="mr-2 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                            <span x-text="category.name" class="text-gray-700"></span>
                        </label>
                    </template>
                    <template x-if="pricingCategoriesList.length === 0">
                        <p class="col-span-full text-center text-xs text-gray-500 py-2">No pricing categories available.</p>
                    </template>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-md font-semibold text-gray-600 mb-2">Software Categories <span class="text-red-500 text-xs"><span class="text-red-500 ml-1">*</span> (select at least one)</span></h3>
                <div class="mb-2 relative">
                    <input type="text" x-model="categorySearchTerm" x-ref="categorySearchInput" placeholder="Search software categories..." class="w-full text-sm text-gray-700 border-gray-300 rounded-md px-3 py-2 focus:ring-primary-500 placeholder-gray-400 focus:border-primary-500 pr-8">
                    <button type="button" x-show="categorySearchTerm.length > 0" @click="categorySearchTerm = ''; $refs.categorySearchInput.focus()" class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600" aria-label="Clear search">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-1 max-h-60 overflow-y-auto pr-2 border rounded-md p-3">
                    <template x-for="category in softwareCategoriesList" :key="category.id">
                        <label class="flex items-center py-1 text-xs cursor-pointer hover:bg-gray-50 px-1 rounded">
                            <input type="checkbox" name="categories[]" :value="category.id" x-model="selectedCategories" class="mr-2 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                            <span x-text="category.name" class="text-gray-700"></span>
                        </label>
                    </template>
                    <template x-if="softwareCategoriesList.length === 0 && categorySearchTerm !== ''">
                        <p class="col-span-full text-center text-xs text-gray-500 py-2">No matching software categories found.</p>
                    </template>
                    <template x-if="softwareCategoriesList.length === 0 && categorySearchTerm === ''">
                        <p class="col-span-full text-center text-xs text-gray-500 py-2">No software categories available or all filtered out.</p>
                    </template>
                </div>
            </div>
            
            <template x-for="selectedCatId in selectedCategories">
                <input type="hidden" name="categories[]" :value="selectedCatId">
            </template>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="grid md:grid-cols-4 gap-4 mt-6">
        <div class="md:col-start-2 md:col-span-3 flex justify-between items-center">
            <div class="text-xs text-gray-500">
                Selected: <span x-text="selectedCategories.length"></span>.
                @php
                    $pricingTypeFromLoop = $types->firstWhere('name', 'Pricing');
                    $softwareTypeFromLoop = $types->firstWhere('name', 'Software Categories');
                @endphp
                @if($pricingTypeFromLoop) <span class="text-red-500"><span class="text-red-500 ml-1">*</span>Min 1 from {{ $pricingTypeFromLoop->name }}</span>@endif
                @if($softwareTypeFromLoop) <span class="text-red-500 ml-1"><span class="text-red-500 ml-1">*</span>Min 1 from {{ $softwareTypeFromLoop->name }}</span>@endif
            </div>
            <div>
                <button type="submit" id="submit-product-button"
                class="bg-primary-500 hover:bg-rose-400 text-white text-sm font-semibold py-1.5 px-3 rounded-md transition duration-300 shadow inline-flex items-center justify-center gap-2 relative"
                 x-bind:disabled="!canSubmitForm">
                    <span id="button-content">{{ isset($product) ? 'Update Product' : 'Submit Product' }}</span>
                </button>
            </div>
        </div>
    </div>
</div>