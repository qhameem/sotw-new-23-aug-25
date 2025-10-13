@csrf
<input type="hidden" name="slug" x-model="productSlug">
@if(isset($product))
    @method('PUT')
@endif

<div class="space-y-6">
    <!-- Product URL -->
    <div class="grid md:grid-cols-3 gap-1 items-start rounded-2xl border-2 border-dashed border-sky-300 p-4 bg-gradient-to-t from-white to-stone-100">
        
        <div class="md:col-span-2 px-1">
            <div class="font-semibold">Autofill details</div>
            <div class="text-xs">
                <template x-if="!fetchError && !fetchingStatusMessage">
                    <span>Just input your product URL, and we'll automatically fill in the details for you!</span>
                </template>
                <template x-if="fetchError">
                    <span class="text-red-600">Data fetching failed. Please check the URL and try again, or fill out the details manually.</span>
                </template>
                <template x-if="fetchingStatusMessage">
                    <span class="text-sky-600" x-text="fetchingStatusMessage"></span>
                </template>
            </div>
            <label class="block text-sm font-semibold md:text-left mt-3" for="product_url">Product URL<span class="text-red-500 ml-1"><span class="text-red-500">*</span></span></label>
           
        </div>
        <div class="md:col-span-3">
            <div class="flex items-center space-x-2">
                <input type="url" id="product_url" name="link" x-model="link" class="flex-grow border border-gray-300 rounded-md px-3 py-2 text-sm placeholder:text-sm placeholder-gray-400" placeholder="https://" required>
                <button @click.prevent="fetchUrlData" type="button" class="inline-flex items-center justify-center px-4 py-2 border border-sky-500 text-xs font-medium rounded-md text-sky-600 bg-white hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 w-24" :disabled="loadingMeta">
                    <div class="flex items-center justify-center">
                        <template x-if="loadingMeta && !isEditMode">
                            <svg class="animate-spin h-5 w-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-show="!loadingMeta || isEditMode">Fetch Data</span>
                    </div>
                </button>
            </div>
            <template x-if="urlExists && !checkingUrl && !isEditMode">
                <div class="text-red-600 text-sm mt-1">This URL is already listed. Please enter a different product URL.</div>
            </template>
        </div>
    </div>

    <!-- Section: Basic Information -->
    <div class="p-4 border rounded-lg">
        <h2 class="text-sm font-semibold mb-4 text-gray-600 flex items-center">
            <span class="bg-gray-100 text-gray-600 rounded-md h-5 px-2 flex items-center justify-center text-xs mr-2">1 of 5</span> Product Identity
            <template x-if="isProductIdentityComplete">
                <svg class="w-5 h-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </template>
        </h2>
        <div class="space-y-4">
            <!-- Name -->
            <div>
                <label class="block text-xs font-semibold mb-1" for="product_name">Product Name<span class="text-red-500 ml-1">*</span></label>
                <input type="text" id="product_name" name="name" x-model="name" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2" required>
                <template x-if="errors.name"><p class="text-red-600 text-sm mt-1" x-text="errors.name"></p></template>
                <div class="flex justify-end">
                    <p class="text-xs text-gray-500 mt-1"><span x-text="name.length"></span> / <span x-text="name_max_length"></span></p>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-600">Product Taglines</h3>
                <!-- Tagline -->
                <div>
                    <label class="block text-xs font-semibold mb-1" for="tagline">Tagline (List Page)<span class="text-red-500 ml-1">*</span></label>
                    <input type="text" id="tagline" name="tagline" x-model="tagline" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2" required>
                    <template x-if="errors.tagline"><p class="text-red-600 text-sm mt-1" x-text="errors.tagline"></p></template>
                    <div class="flex justify-between">
                        <p class="text-xs text-gray-500 mt-1">Shown on the product list page. Keep it short and punchy.</p>
                        <p class="text-xs text-gray-500 mt-1"><span x-text="tagline.length"></span> / <span x-text="tagline_max_length"></span></p>
                    </div>
                </div>

                <!-- Tagline on Product Page -->
                <div>
                    <label class="block text-xs font-semibold mb-1" for="product_page_tagline">Tagline (Details Page)<span class="text-red-500 ml-1">*</span></label>
                    <input type="text" id="product_page_tagline" name="product_page_tagline" x-model="product_page_tagline" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2" required>
                    <template x-if="errors.product_page_tagline"><p class="text-red-600 text-sm mt-1" x-text="errors.product_page_tagline"></p></template>
                    <div class="flex justify-between">
                        <p class="text-xs text-gray-500 mt-1">Shown on your product's detail page. Use 1-2 sentences to describe your product clearly.</p>
                        <p class="text-xs text-gray-500 mt-1"><span x-text="product_page_tagline.length"></span> / <span x-text="product_page_tagline_max_length"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Categorization -->
    <div class="p-4 border rounded-lg">
        <h2 class="text-sm font-semibold mb-4 text-gray-600 flex items-center">
            <span class="bg-gray-100 text-gray-600 rounded-md h-5 px-2 flex items-center justify-center text-xs mr-2">2 of 5</span> Categorization
            <template x-if="isCategorizationComplete">
                <svg class="w-5 h-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </template>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
            <!-- Categories Section -->
            <div x-data="{
                    highlightedCategoryIndex: -1,
                    isCategoryDropdownOpen: false,
                    toggleCategory(categoryId) {
                        const index = this.selectedCategories.indexOf(categoryId);
                        if (index === -1) {
                            this.selectedCategories.push(categoryId);
                        } else {
                            this.selectedCategories.splice(index, 1);
                        }
                    }
                 }"
                 x-init="$watch('selectedCategories', () => { categorySearchTerm = ''; }); $watch('highlightedCategoryIndex', (value) => { if(value > -1) { const el = $refs.categoryDropdown.children[value]; el.scrollIntoView({ block: 'nearest' }); } })">
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-semibold md:text-left md:pr-4 mb-1">Category<span class="text-red-500 ml-1">*</span></label>
                    <span class="text-xs text-gray-600">Select at least 1 and upto 3</span>
                </div>
                @error('categories')<div class="text-red-600 text-sm mb-2">{{ $message }}</div>@enderror
                <template x-if="errors.categories"><p class="text-red-600 text-sm mb-2" x-text="errors.categories"></p></template>
                <div class="mb-4">
                    <div class="mb-2 relative" @click.away="isCategoryDropdownOpen = false">
                        <div class="w-full text-sm text-gray-700 border-gray-300 rounded-md p-2 placeholder-gray-400 pr-8 flex flex-wrap gap-2 items-center border" @click="isCategoryDropdownOpen = true; $refs.categorySearchInput.focus()">
                            <template x-for="category in selectedCategoriesDisplay.filter(c => !c.isBestFor)" :key="category.id">
                                <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full group hover:bg-gray-200">
                                    <span x-text="category.name" class="truncate max-w-[180px]" :title="category.name"></span>
                                    <button @click.prevent.stop="deselectCategory(category.id)" type="button" class="ml-1.5 -mr-1 flex-shrink-0 inline-flex items-center justify-center h-4 w-4 rounded-full text-gray-400 hover:text-gray-600 focus:outline-none focus:bg-gray-300" :aria-label="'Remove ' + category.name">
                                        <span class="sr-only" x-text="'Remove ' + category.name"></span>
                                        <svg class="h-2.5 w-2.5" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                            <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                            <input type="text"
                                   x-model="categorySearchTerm"
                                   x-ref="categorySearchInput"
                                   :placeholder="selectedCategories.length > 0 ? 'Click here to see more categories' : 'Search software categories...'"
                                   class="flex-grow border-none focus:ring-0 p-0 text-sm placeholder-gray-400 placeholder:text-xs focus:outline-none"
                                   @keydown.arrow-down.prevent="highlightedCategoryIndex = Math.min(highlightedCategoryIndex + 1, softwareCategoriesList.length - 1)"
                                   @keydown.arrow-up.prevent="highlightedCategoryIndex = Math.max(highlightedCategoryIndex - 1, -1)"
                                   @keydown.enter.prevent="if (highlightedCategoryIndex > -1) { toggleCategory(softwareCategoriesList[highlightedCategoryIndex].id); highlightedCategoryIndex = -1; }"
                                   @focus="isCategoryDropdownOpen = true">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2" @click.stop="isCategoryDropdownOpen = !isCategoryDropdownOpen">
                                 <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        
                        <div x-show="isCategoryDropdownOpen" x-transition class="absolute z-20 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm" x-ref="categoryDropdown">
                            <template x-for="(category, index) in softwareCategoriesList" :key="category.id">
                                <div @click="toggleCategory(category.id)"
                                     class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-gray-100"
                                     :class="{ 'bg-gray-200': highlightedCategoryIndex === index }">
                                    <span x-text="category.name" class="font-normal block truncate"></span>
                                </div>
                            </template>
                            <template x-if="softwareCategoriesList.length === 0 && categorySearchTerm !== ''">
                                <p class="text-center text-xs text-gray-500 py-2">No matching software categories found.</p>
                            </template>
                            <template x-if="softwareCategoriesList.length === 0 && categorySearchTerm === ''">
                                <p class="text-center text-xs text-gray-500 py-2">No software categories available.</p>
                            </template>
                        </div>
                    </div>
                </div>
                
                <template x-for="selectedCatId in selectedCategories">
                    <input type="hidden" name="categories[]" :value="selectedCatId">
                </template>
            </div>

            <!-- Best For Section -->
            <div x-data="{
                    highlightedBestForIndex: -1,
                    isBestForDropdownOpen: false,
                    toggleBestFor(categoryId) {
                        const index = this.selectedCategories.indexOf(categoryId);
                        if (index === -1) {
                            this.selectedCategories.push(categoryId);
                        } else {
                            this.selectedCategories.splice(index, 1);
                        }
                    }
                 }"
                 x-init="$watch('selectedCategories', () => { bestForSearchTerm = ''; }); $watch('highlightedBestForIndex', (value) => { if(value > -1) { const el = $refs.bestForDropdown.children[value]; el.scrollIntoView({ block: 'nearest' }); } })">
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-semibold md:text-left md:pr-4 mb-1">Best for<span class="text-red-500 ml-1">*</span></label>
                    <span class="text-xs text-gray-600">Select at least 1</span>
                </div>
               
                <div class="mb-4">
                    <div class="mb-2 relative" @click.away="isBestForDropdownOpen = false">
                        <div class="w-full text-sm text-gray-700 border-gray-300 rounded-md p-2 placeholder-gray-400 pr-8 flex flex-wrap gap-2 items-center border" @click="isBestForDropdownOpen = true; $refs.bestForSearchInput.focus()">
                            <template x-for="category in selectedCategoriesDisplay.filter(c => c.isBestFor)" :key="category.id">
                                <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full group hover:bg-gray-200">
                                    <span x-text="category.name" class="truncate max-w-[180px]" :title="category.name"></span>
                                    <button @click.prevent.stop="deselectCategory(category.id)" type="button" class="ml-1.5 -mr-1 flex-shrink-0 inline-flex items-center justify-center h-4 w-4 rounded-full text-gray-400 hover:text-gray-600 focus:outline-none focus:bg-gray-300" :aria-label="'Remove ' + category.name">
                                        <span class="sr-only" x-text="'Remove ' + category.name"></span>
                                        <svg class="h-2.5 w-2.5" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                            <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                            <input type="text"
                                   x-model="bestForSearchTerm"
                                   x-ref="bestForSearchInput"
                                   placeholder="Search 'best for' categories..."
                                   class="flex-grow border-none focus:ring-0 p-0 text-sm placeholder-gray-400 placeholder:text-xs focus:outline-none"
                                   @keydown.arrow-down.prevent="highlightedBestForIndex = Math.min(highlightedBestForIndex + 1, bestForCategoriesList.length - 1)"
                                   @keydown.arrow-up.prevent="highlightedBestForIndex = Math.max(highlightedBestForIndex - 1, -1)"
                                   @keydown.enter.prevent="if (highlightedBestForIndex > -1) { toggleBestFor(bestForCategoriesList[highlightedBestForIndex].id); highlightedBestForIndex = -1; }"
                                   @focus="isBestForDropdownOpen = true">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2" @click.stop="isBestForDropdownOpen = !isBestForDropdownOpen">
                                 <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        
                        <div x-show="isBestForDropdownOpen" x-transition class="absolute z-20 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm" x-ref="bestForDropdown">
                            <template x-for="(category, index) in bestForCategoriesList" :key="category.id">
                                <div @click="toggleBestFor(category.id)"
                                     class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-gray-100"
                                     :class="{ 'bg-gray-200': highlightedBestForIndex === index }">
                                    <span x-text="category.name" class="font-normal block truncate"></span>
                                </div>
                            </template>
                            <template x-if="bestForCategoriesList.length === 0 && bestForSearchTerm !== ''">
                                <p class="text-center text-xs text-gray-500 py-2">No matching 'best for' categories found.</p>
                            </template>
                            <template x-if="bestForCategoriesList.length === 0 && bestForSearchTerm === ''">
                                <p class="text-center text-xs text-gray-500 py-2">No 'best for' categories available.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Model -->
            <div class="mb-4">
                   <div class="flex justify-between items-center">
                    <label class="block text-xs font-semibold md:text-left md:pr-4 mb-1">Pricing Model<span class="text-red-500 ml-1">*</span></label>
                    <span class="text-xs text-gray-600">Select at least 1</span>
                </div>
                
                <div class="grid grid-cols-2 gap-y-1 border rounded-md p-3">
                    <template x-for="category in pricingCategoriesList" :key="category.id">
                        <label class="flex items-center py-1 text-xs cursor-pointer hover:bg-gray-50 px-1 rounded">
                            <input type="checkbox" name="categories[]" :value="category.id" x-model="selectedCategories" class="mr-2 rounded border-gray-300 text-primary-600 shadow-sm">
                            <span x-text="category.name" class="text-gray-700"></span>
                        </label>
                    </template>
                    <template x-if="pricingCategoriesList.length === 0">
                        <p class="col-span-full text-center text-xs text-gray-500 py-2">No pricing categories available.</p>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Categorization -->
    <div class="p-4 border rounded-lg">
        <h2 class="text-sm font-semibold mb-4 text-gray-600 flex items-center">
            <span class="bg-gray-100 text-gray-600 rounded-md h-5 px-2 flex items-center justify-center text-xs mr-2">3 of 5</span> Tech Stack
        </h2>
        <div class="grid grid-cols-1 gap-4 items-start">
            <!-- Tech Stacks Section -->
            <div x-data="{
                    highlightedTechStackIndex: -1,
                    isTechStackDropdownOpen: false,
                    toggleTechStack(techStackId) {
                        const index = this.selectedTechStacks.indexOf(techStackId);
                        if (index === -1) {
                            this.selectedTechStacks.push(techStackId);
                        } else {
                            this.selectedTechStacks.splice(index, 1);
                        }
                    }
                 }"
                 x-init="$watch('selectedTechStacks', () => { techStackSearchTerm = ''; }); $watch('highlightedTechStackIndex', (value) => { if(value > -1) { const el = $refs.techStackDropdown.children[value]; el.scrollIntoView({ block: 'nearest' }); } })">
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-2">List the main technologies used to build your product. We'll try to detect these automatically from the URL.</p>
                    <div class="mb-2 relative" @click.away="isTechStackDropdownOpen = false">
                        <div class="w-full text-sm text-gray-700 border-gray-300 rounded-md p-2 placeholder-gray-400 pr-8 flex flex-wrap gap-2 items-center border" @click="isTechStackDropdownOpen = true; $refs.techStackSearchInput.focus()">
                            <template x-for="techStack in selectedTechStacksDisplay" :key="techStack.id">
                                <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full group hover:bg-gray-200">
                                    <span x-text="techStack.name" class="truncate max-w-[180px]" :title="techStack.name"></span>
                                    <button @click.prevent.stop="deselectTechStack(techStack.id)" type="button" class="ml-1.5 -mr-1 flex-shrink-0 inline-flex items-center justify-center h-4 w-4 rounded-full text-gray-400 hover:text-gray-600 focus:outline-none focus:bg-gray-300" :aria-label="'Remove ' + techStack.name">
                                        <span class="sr-only" x-text="'Remove ' + techStack.name"></span>
                                        <svg class="h-2.5 w-2.5" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                            <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                            <input type="text"
                                   x-model="techStackSearchTerm"
                                   x-ref="techStackSearchInput"
                                   placeholder="Search tech stacks..."
                                   class="flex-grow border-none focus:ring-0 p-0 text-sm placeholder-gray-400 placeholder:text-xs focus:outline-none"
                                   @keydown.arrow-down.prevent="highlightedTechStackIndex = Math.min(highlightedTechStackIndex + 1, techStacksList.length - 1)"
                                   @keydown.arrow-up.prevent="highlightedTechStackIndex = Math.max(highlightedTechStackIndex - 1, -1)"
                                   @keydown.enter.prevent="if (highlightedTechStackIndex > -1) { toggleTechStack(techStacksList[highlightedTechStackIndex].id); highlightedTechStackIndex = -1; }"
                                   @focus="isTechStackDropdownOpen = true">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2" @click.stop="isTechStackDropdownOpen = !isTechStackDropdownOpen">
                                 <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        
                        <div x-show="isTechStackDropdownOpen" x-transition class="absolute z-20 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm" x-ref="techStackDropdown">
                            <template x-for="(techStack, index) in techStacksList" :key="techStack.id">
                                <div @click="toggleTechStack(techStack.id)"
                                     class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-gray-100"
                                     :class="{ 'bg-gray-200': highlightedTechStackIndex === index }">
                                    <span x-text="techStack.name" class="font-normal block truncate"></span>
                                </div>
                            </template>
                            <template x-if="techStacksList.length === 0 && techStackSearchTerm !== ''">
                                <p class="text-center text-xs text-gray-500 py-2">No matching tech stacks found.</p>
                            </template>
                            <template x-if="techStacksList.length === 0 && techStackSearchTerm === ''">
                                <p class="text-center text-xs text-gray-500 py-2">No tech stacks available.</p>
                            </template>
                        </div>
                    </div>
                </div>
                
                <template x-for="selectedTechStackId in selectedTechStacks">
                    <input type="hidden" name="tech_stacks[]" :value="selectedTechStackId">
                </template>
            </div>
        </div>
    </div>

    <!-- Section: Media and Branding -->
    <div class="p-4 border rounded-lg">
        <h2 class="text-sm font-semibold mb-4 text-gray-600 flex items-center">
            <span class="bg-gray-100 text-gray-600 rounded-md h-5 px-2 flex items-center justify-center text-xs mr-2">4 of 5</span> Media and Branding
            <template x-if="isMediaAndBrandingComplete">
                <svg class="w-5 h-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </template>
        </h2>
        <div class="space-y-4">
            <!-- Logo Upload Section -->
            <div>
                <label class="block text-xs font-semibold mb-1">Logo (Max 1MB) <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 mb-2">Recommended: 1:1 square image (e.g., 256x256px).</p>
                <div class="flex items-center space-x-4">
                    <div class="shrink-0" x-show="logoPreviewUrl || existingLogoUrl">
                        <img :src="logoPreviewUrl || existingLogoUrl" alt="Logo" class="w-20 h-20 object-contain rounded-md border">
                    </div>
                    <div class="flex-grow">
                        <label for="logoInput" class="cursor-pointer inline-flex items-center px-4 py-1 border border-sky-500 text-xs font-medium rounded-md text-sky-600 bg-white hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                            <svg class="w-5 h-5 mr-2 -ml-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 3H7.8C6.11984 3 5.27976 3 4.63803 3.32698C4.07354 3.6146 3.6146 4.07354 3.32698 4.63803C3 5.27976 3 6.11984 3 7.8V16.2C3 17.8802 3 18.7202 3.32698 19.362C3.6146 19.9265 4.07354 20.3854 4.63803 20.673C5.27976 21 6.11984 21 7.8 21H17C17.93 21 18.395 21 18.7765 20.8978C19.8117 20.6204 20.6204 19.8117 20.8978 18.7765C21 18.395 21 17.93 21 17M19 8V2M16 5H22M10.5 8.5C10.5 9.60457 9.60457 10.5 8.5 10.5C7.39543 10.5 6.5 9.60457 6.5 8.5C6.5 7.39543 7.39543 6.5 8.5 6.5C9.60457 6.5 10.5 7.39543 10.5 8.5ZM14.99 11.9181L6.53115 19.608C6.05536 20.0406 5.81747 20.2568 5.79643 20.4442C5.77819 20.6066 5.84045 20.7676 5.96319 20.8755C6.10478 21 6.42628 21 7.06929 21H16.456C17.8951 21 18.6147 21 19.1799 20.7582C19.8894 20.4547 20.4547 19.8894 20.7582 19.1799C21 18.6147 21 17.8951 21 16.456C21 15.9717 21 15.7296 20.9471 15.5042C20.8805 15.2208 20.753 14.9554 20.5733 14.7264C20.4303 14.5442 20.2412 14.3929 19.8631 14.0905L17.0658 11.8527C16.6874 11.5499 16.4982 11.3985 16.2898 11.3451C16.1061 11.298 15.9129 11.3041 15.7325 11.3627C15.5279 11.4291 15.3486 11.5921 14.99 11.9181Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            {{ isset($product) && $product->logo ? 'Change Logo' : 'Upload Logo' }}
                        </label>
                        <input type="file" name="logo" id="logoInput" class="hidden" accept="image/png,image/jpeg,image/gif,image/svg+xml,image/webp,image/avif" @change="uploadLogo">
                        <input type="hidden" name="selected_logo_url" x-model="selectedLogoUrl">
                    </div>
                </div>

                @if(isset($product) && $product->approved && $product->has_pending_edits && $product->proposed_logo_path)
                <div class="mb-4">
                    <label class="block mb-1 text-xs font-medium text-yellow-700">Currently Proposed Logo (Pending Review)</label>
                    <img src="{{ asset('storage/' . $product->proposed_logo_path) }}" alt="Proposed Logo" class="w-20 h-20 object-cover rounded-md mb-2 border border-yellow-400">
                </div>
                @endif
                
                <div x-show="selectedLogoUrl && !logoPreviewUrl" class="mt-2">
                    <label class="block mb-1 text-xs font-medium text-gray-600">Selected Logo:</label>
                    <div class="relative inline-block group">
                        <img :src="selectedLogoUrl" alt="Selected Logo" class="w-24 h-24 object-contain rounded-md border border-gray-300">
                        <button @click="selectedLogoUrl = ''" type="button" class="absolute top-0 right-0 -mt-2 -mr-2 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-opacity focus:outline-none">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        </button>
                    </div>
                </div>

                <template x-if="fetchedLogos.length > 0 && !logoFileSelected">
                    <div class="mt-4">
                        <label class="block text-xs font-semibold mb-2">Or select a fetched logo:</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="logo in fetchedLogos" :key="logo">
                                <img :src="logo" @click="selectLogo(logo)" :class="{'border-sky-500 ring-2 ring-sky-500': selectedLogoUrl === logo, 'border-gray-300': selectedLogoUrl !== logo}" class="w-24 h-24 object-contain rounded-md border-2 cursor-pointer hover:border-sky-400">
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="logoPreviewUrl">
                    <div class="mt-2">
                        <label class="block mb-1 text-xs font-medium text-gray-600">New Logo Preview:</label>
                        <div class="relative inline-block group">
                            <img :src="logoPreviewUrl" alt="New Logo Preview" class="w-24 h-24 object-contain rounded-md border border-gray-300">
                            <button @click="removePreviewLogo()" type="button" class="absolute top-0 right-0 -mt-2 -mr-2 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 hover:bg-red-600 transition-opacity focus:outline-none">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="logoUploadError">
                    <div class="text-red-600 text-sm mt-1" x-text="logoUploadError"></div>
                </template>

                @if(isset($product))
                <div class="mt-4">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="remove_logo" value="1" class="mr-2 rounded border-gray-300 text-primary-600 shadow-sm">
                        Propose to remove logo
                    </label>
                </div>
                @endif
            </div>

            <!-- Media Upload Section -->
            <div class="mt-4">
                <label class="block text-xs font-semibold mb-1">Product Image (Optional)</label>
                <p class="text-xs text-gray-500 mb-2">Add a product image. Recommended: 16:9 aspect ratio (e.g., 800x450px).</p>
                <input type="file" name="media[]" id="mediaInput" class="hidden" accept="image/png,image/jpeg,image/gif,image/webp,image/avif" @change="showMediaPreview(event)" multiple>
                <button type="button" @click="document.getElementById('mediaInput').click()" class="inline-flex items-center px-4 py-1 border border-sky-500 text-xs font-medium rounded-md text-sky-600 bg-white hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                    <svg class="w-5 h-5 mr-2 -ml-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 3H7.8C6.11984 3 5.27976 3 4.63803 3.32698C4.07354 3.6146 3.6146 4.07354 3.32698 4.63803C3 5.27976 3 6.11984 3 7.8V16.2C3 17.8802 3 18.7202 3.32698 19.362C3.6146 19.9265 4.07354 20.3854 4.63803 20.673C5.27976 21 6.11984 21 7.8 21H17C17.93 21 18.395 21 18.7765 20.8978C19.8117 20.6204 20.6204 19.8117 20.8978 18.7765C21 18.395 21 17.93 21 17M19 8V2M16 5H22M10.5 8.5C10.5 9.60457 9.60457 10.5 8.5 10.5C7.39543 10.5 6.5 9.60457 6.5 8.5C6.5 7.39543 7.39543 6.5 8.5 6.5C9.60457 6.5 10.5 7.39543 10.5 8.5ZM14.99 11.9181L6.53115 19.608C6.05536 20.0406 5.81747 20.2568 5.79643 20.4442C5.77819 20.6066 5.84045 20.7676 5.96319 20.8755C6.10478 21 6.42628 21 7.06929 21H16.456C17.8951 21 18.6147 21 19.1799 20.7582C19.8894 20.4547 20.4547 19.8894 20.7582 19.1799C21 18.6147 21 17.8951 21 16.456C21 15.9717 21 15.7296 20.9471 15.5042C20.8805 15.2208 20.753 14.9554 20.5733 14.7264C20.4303 14.5442 20.2412 14.3929 19.8631 14.0905L17.0658 11.8527C16.6874 11.5499 16.4982 11.3985 16.2898 11.3451C16.1061 11.298 15.9129 11.3041 15.7325 11.3627C15.5279 11.4291 15.3486 11.5921 14.99 11.9181Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    Add Product Images
                </button>

                <div x-show="mediaPreviewUrls.length > 0" class="mt-4">
                    <h3 class="text-xs font-semibold mb-2">Image Previews</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <template x-for="(url, index) in mediaPreviewUrls" :key="index">
                            <img :src="url" alt="Image Preview" class="rounded-md border w-full object-cover">
                        </template>
                    </div>
                </div>

                @if(isset($product) && $product->media->isNotEmpty())
                <div class="mt-4">
                    <h3 class="text-xs font-semibold mb-2">Current Image</h3>
                    <img src="{{ $product->media->first()->url }}" alt="Current Image" class="rounded-md border w-full object-cover">
                </div>
                @endif

                <div x-show="fetchedOgImages.length > 0 && !mediaPreviewUrl" class="mt-4">
                    <h3 class="text-xs font-semibold mb-2">Fetched Images (Select up to 2)</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(image, index) in fetchedOgImages.slice(0, 3)" :key="index">
                            <div class="relative group cursor-pointer" @click="toggleOgImage(image)">
                                <img :src="image" alt="Fetched Image" class="w-32 h-32 object-cover rounded-md border-2" :class="{'border-sky-500 ring-2 ring-sky-500': selectedOgImages.includes(image), 'border-gray-300': !selectedOgImages.includes(image)}">
                                <div x-show="selectedOgImages.includes(image)" class="absolute top-0 right-0 -mt-2 -mr-2 p-1 bg-green-500 text-white rounded-full">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <template x-for="image in selectedOgImages">
                    <input type="hidden" name="selected_og_images[]" :value="image">
                </template>

                <input type="hidden" name="video_url" :value="selectedVideo ? JSON.stringify(selectedVideo) : ''">
            </div>

            <!-- Video URL -->
            <div>
                <label class="block text-xs font-semibold mb-1" for="video_url">Video URL</label>
                <div class="flex items-start space-x-2">
                    <input type="url" id="video_url" x-model="video_url" x-ref="videoUrlInput" class="flex-grow w-full text-sm border border-gray-300 rounded-md px-3 py-2" placeholder="Paste a video link from YouTube, Vimeo, etc.">
                    <button @click.prevent="fetchVideos" type="button" class="inline-flex items-center justify-center px-4 py-2 border border-sky-500 text-xs font-medium rounded-md text-sky-600 bg-white hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 w-32" :disabled="fetchingVideos">
                        <span x-show="!fetchingVideos">Fetch Video</span>
                        <span x-show="fetchingVideos">
                            <svg class="animate-spin h-5 w-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Enter a YouTube, Vimeo, TikTok, Facebook, or X (Twitter) URL to embed a video on the product page.</p>
                
                <!-- Fetched Videos Preview -->
                <div x-show="fetchedVideo" class="mt-4">
                    <h3 class="text-xs font-semibold mb-2">Video Preview</h3>
                    <div class="relative group w-1/3">
                        <img :src="fetchedVideo.thumbnail_url" alt="Video Thumbnail" class="w-full h-auto object-cover rounded-md border-2" :class="{'border-sky-500 ring-2 ring-sky-500': selectedVideo, 'border-gray-300': !selectedVideo}">
                        <div x-show="selectedVideo" class="absolute top-0 right-0 -mt-2 -mr-2">
                            <button @click.prevent.stop="deselectVideo" type="button" class="p-1 bg-red-500 text-white rounded-full hover:bg-red-600 focus:outline-none">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Detailed Description -->
    <div class="p-4 border rounded-lg">
        <h2 class="text-sm font-semibold mb-4 text-gray-600 flex items-center">
            <span class="bg-gray-100 text-gray-600 rounded-md h-5 px-2 flex items-center justify-center text-xs mr-2">5 of 5</span> Detailed Description <span class="text-red-500 ml-1">*</span>
            <template x-if="isDescriptionComplete">
                <svg class="w-5 h-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </template>
        </h2>
        <!-- Description -->
        <div class="grid md:grid-cols-1 gap-4 items-start">
            <div class="md:col-span-1">
                <div id="quill-editor" style="height: 300px;" class="mt-1 bg-white text-gray-900 border border-gray-300 rounded-md"></div>
                <input type="hidden" name="description" id="description" x-model="description">
            </div>
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
                    <span id="button-content" class="flex items-center">{{ isset($product) ? 'Update Product' : 'Submit Product' }}</span>
                    <span id="loader-container"></span>
                </button>
            </div>
        </div>
    </div>
</div>