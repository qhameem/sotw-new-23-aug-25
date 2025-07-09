    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        searchModalOpen: false,
        open: false, // For mobile navigation
        isProductModalOpen: false,
        selectedProduct: null,
        initialPath: window.location.pathname, // Store the initial path
        intendedUrl: '',

        openProductModal(product) {
            console.log('openProductModal called with:', product);
            if (!product || !product.slug) {
                console.error('Product data or slug is missing for modal.', product);
                return;
            }
            this.selectedProduct = product;
            this.isProductModalOpen = true;
            document.body.style.overflow = 'hidden';
            const productUrl = `/product/${product.slug}`;
            history.pushState({ productSlug: product.slug }, product.name, productUrl);
            this.$nextTick(() => { // Ensure title is updated after state change
                document.title = `${product.name} | Software on the web`;
            });
        },

        closeProductModal() {
            this.isProductModalOpen = false;
            this.selectedProduct = null;
            document.body.style.overflow = '';
            history.pushState({}, 'Software on the web', this.initialPath); // Revert to initial path
            document.title = 'Software on the web'; // Revert title
        },
        
        handlePopState(event) {
            const path = window.location.pathname;
            const productSlugMatch = path.match(/^\/product\/([a-zA-Z0-9-]+)$/);
            if (productSlugMatch && productSlugMatch[1]) {
                const slug = productSlugMatch[1];
                if (!this.isProductModalOpen || (this.selectedProduct && this.selectedProduct.slug !== slug)) {
                    console.log('Popstate to product URL, would need to fetch product data for slug:', slug);
                    if (this.isProductModalOpen && this.selectedProduct && this.selectedProduct.slug !== slug) {
                        this.closeProductModal();
                    } else if (!this.isProductModalOpen) {
                        // Let dedicated page render
                    }
                }
            } else {
                if (this.isProductModalOpen) {
                    this.closeProductModal();
                }
                document.title = 'Software on the web';
            }
        }
    }"
    x-init="() => {
        window.addEventListener('popstate', handlePopState.bind($data));
        initialPath = window.location.pathname;
        
        const initialProductSlugMatch = window.location.pathname.match(/^\/product\/([a-zA-Z0-9-]+)$/);
        if (initialProductSlugMatch && initialProductSlugMatch[1]) {
            console.log('Initial load on product page:', initialProductSlugMatch[1]);
        }
    }"
    @open-product-modal.window="openProductModal($event.detail)"
    @open-search-modal.window="searchModalOpen = true; $nextTick(() => document.getElementById('globalSearchInput')?.focus())"
    @close-search-modal-from-js.window="searchModalOpen = false"
    @open-login-modal.window="
        fetch('/set-intended-url', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ url: window.location.href })
        }).then(() => {
            $dispatch('open-modal', { name: 'login-required-modal' });
        });
    "
    x-on:livewire:navigating.window="searchModalOpen = false; isProductModalOpen = false; if(typeof closeProductModal === 'function') closeProductModal();">

<head>
    <script>
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $currentPath = '/' . ltrim(request()->path(), '/');
        if ($currentPath === '/index.php') { // Handle cases where index.php might be in the path
            $currentPath = '/';
        }
        $metaTag = \App\Models\PageMetaTag::where('path', $currentPath)->first();
        $metaDescription = $metaTag ? $metaTag->description : config('app.description', 'Default meta description for Software on the Web.'); // Fallback to a default

        // Dynamic Title Logic
        $siteNameBase = "Software on the web";
        $finalTitle = '';

        // Priority 1: A specific $title variable passed from the controller.
        if (isset($title) && !empty(trim($title))) {
            $finalTitle = trim($title);
        }
        // Priority 2: An alternative $pageTitle variable.
        elseif (isset($pageTitle) && !empty(trim($pageTitle))) {
            $finalTitle = trim($pageTitle);
        }
        // Priority 3: Meta tag from the database for the current path.
        elseif ($metaTag && !empty(trim($metaTag->title))) {
            $contentTitle = trim($metaTag->title);
            // Apply suffix rule
            if ($contentTitle === $siteNameBase) {
                $finalTitle = $siteNameBase;
            } else {
                $finalTitle = $contentTitle . " | " . $siteNameBase;
            }
        }
        // Priority 4: Route-specific titles.
        elseif (request()->is('/')) { // Home page
            $finalTitle = "Top Software Tools, Updated Daily | " . $siteNameBase;
        }
        elseif (request()->segment(1) === 'categories' && request()->segment(2) && !request()->segment(3)) {
            $categorySlug = request()->segment(2);
            $categorySlug = preg_replace('/[^a-zA-Z0-9-]/', '', $categorySlug);
            $formattedCategoryName = ucwords(str_replace('-', ' ', $categorySlug));
            $finalTitle = $formattedCategoryName . " | " . $siteNameBase;
        }
        // Fallback: Use the application name.
        else {
            $contentTitle = config('app.name', $siteNameBase);
            if ($contentTitle === $siteNameBase) {
                $finalTitle = $siteNameBase;
            } else {
                $finalTitle = $contentTitle . " | " . $siteNameBase;
            }
        }

        // Final clean-up
        if (empty(trim($finalTitle)) || str_starts_with(trim($finalTitle), "| " . $siteNameBase)) {
            $finalTitle = $siteNameBase;
        }
    @endphp
    <meta name="description" content="{{ $metaDescription }}">

    <title>{{ $finalTitle }}</title>

    @if(config('theme.font_url'))
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ config('theme.font_url') }}" rel="stylesheet">
    @endif

    @php
        $fontFamily = config('theme.font_family', 'Roboto'); 
        $primaryHexColor = config('theme.primary_color', '#3b82f6');

        // Determine intelligent default for button text color
        $primaryButtonTextColor = config('theme.primary_button_text_color');
        if (!$primaryButtonTextColor) {
            $r = hexdec(substr($primaryHexColor, 1, 2));
            $g = hexdec(substr($primaryHexColor, 3, 2));
            $b = hexdec(substr($primaryHexColor, 5, 2));
            $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
            $primaryButtonTextColor = ($yiq >= 128) ? '#000000' : '#ffffff';
        }
    @endphp

    <style>
        :root {
            --font-family-sans: '{{ $fontFamily }}', sans-serif;
            --color-primary-500: {{ $primaryHexColor }};
            --color-primary-600: {{ $primaryHexColor }};
            --color-primary-700: {{ $primaryHexColor }};
            --color-primary-button-text: {{ $primaryButtonTextColor }};
        }
        html, body {
            font-family: var(--font-family-sans);
        }
    </style>

    @php
        $customFaviconPath = config('theme.favicon_url');
        $faviconBasePath = $customFaviconPath ? Illuminate\Support\Facades\Storage::url(dirname($customFaviconPath)) : asset('favicon');
        $mainFaviconUrl = $customFaviconPath ? Illuminate\Support\Facades\Storage::url($customFaviconPath) : asset('favicon/favicon.ico');
        
        // Determine original extension for conditional linking of generated PNGs
        $originalFaviconExtension = $customFaviconPath ? pathinfo($customFaviconPath, PATHINFO_EXTENSION) : 'ico';
        $canUseGeneratedPngVersions = $customFaviconPath && in_array(strtolower($originalFaviconExtension), ['png', 'jpg', 'jpeg', 'gif']); // Assuming generation happens for these
    @endphp

    @if ($customFaviconPath)
        <link rel="icon" href="{{ $mainFaviconUrl }}">
        @if ($canUseGeneratedPngVersions)
            <link rel="apple-touch-icon" sizes="180x180" href="{{ $faviconBasePath . '/apple-touch-icon.png' }}">
            <link rel="icon" type="image/png" sizes="32x32" href="{{ $faviconBasePath . '/favicon-32x32.png' }}">
            <link rel="icon" type="image/png" sizes="16x16" href="{{ $faviconBasePath . '/favicon-16x16.png' }}">
        @else
            {{-- Fallback for non-PNG custom favicons (e.g. SVG, or direct ICO) - browser might use the main one for other sizes --}}
            <link rel="apple-touch-icon" sizes="180x180" href="{{ $mainFaviconUrl }}">
        @endif
    @else
        {{-- Default static favicons --}}
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
        <link rel="icon" href="{{ asset('favicon/favicon.ico') }}">
    @endif
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">


    {{-- Google Analytics Code Injection --}}
    @php
        $gaCode = '';
        if (Illuminate\Support\Facades\Storage::disk('local')->exists('settings.json')) {
            $settings = json_decode(Illuminate\Support\Facades\Storage::disk('local')->get('settings.json'), true);
            $gaCode = $settings['google_analytics_code'] ?? '';
        }
    @endphp
    @if(!empty($gaCode) && !Auth::check()) {{-- Only inject if code exists and user is not authenticated (optional: or not admin) --}}
        {!! $gaCode !!}
    @endif
    {{-- End Google Analytics Code Injection --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @stack('styles')
</head>

<body class="font-sans antialiased bg-white"
      data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
      data-login-url="{{ route('login') }}"
      data-csrf-token="{{ csrf_token() }}">

    <x-main-content-layout :hide-sidebar="$hideSidebar ?? false" :full-width="$fullWidth ?? false">
        <x-slot:title>
            @yield('title')
        </x-slot:title>
        <x-slot:actions>
            @yield('actions')
        </x-slot:actions>

        @if (isset($slot) && trim($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </x-main-content-layout>

    <div x-show="searchModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-start justify-center pt-16 sm:pt-24 bg-gray-900 bg-opacity-75"
        @keydown.escape.window="searchModalOpen = false" x-cloak
        x-init="$watch('searchModalOpen', open => { if (open) { $nextTick(() => document.getElementById('globalSearchInput')?.focus()); } else { document.getElementById('globalSearchInput')?.blur(); } })">
        <div class="bg-white  rounded-lg shadow-xl w-full max-w-2xl p-6" @click.outside="searchModalOpen = false">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 ">Search Products</h2>
                <button @click="searchModalOpen = false" class="text-gray-400 hover:text-gray-600  ">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="relative mb-4" x-data="{ searchTerm: '' }" @click.stop>
                <input type="text" x-model="searchTerm" @input.debounce.300ms="performSearch(searchTerm)"
                    x-ref="searchInput" id="globalSearchInput"
                    placeholder="Search by name, tagline, or description..."
                    class="w-full px-4 py-2 border border-gray-300  rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500  focus:border-transparent   placeholder-gray-400  placeholder:text-sm">
                <button x-show="searchTerm.length > 0" @click="searchTerm = ''; performSearch('')"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600  ">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="searchResultsContainer" class="max-h-[60vh] overflow-y-auto space-y-3">
                <p x-show="!searchResults || searchResults.length === 0" class="text-gray-500 text-center py-4">Start
                    typing to search for products.</p>
            </div>
        </div>
    </div>
    <script>
        let searchResults = [];
        let searchTimeout = null;

        function performSearch(term) {
            const container = document.getElementById('searchResultsContainer');
            const searchTerm = term.trim();

            if (!searchTerm) {
                searchResults = [];
                container.innerHTML = '<p class="text-gray-500 text-center py-4">Start typing to search for products.</p>';
                return;
            }
            container.innerHTML = '<p class="text-gray-500 text-center py-4">Loading results...</p>';
            clearTimeout(searchTimeout); 
            searchTimeout = setTimeout(() => {
                fetch(`/products/search?term=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults = data; // Keep the raw data accessible
                        if (searchResults.length === 0) {
                            container.innerHTML =
                                '<p class="text-gray-500 text-center py-4">No products found matching your search.</p>';
                        } else {
                            container.innerHTML = ''; // Clear previous results
                            searchResults.forEach((product, index) => {
                                const article = document.createElement('article');
                                article.className = 'p-4 flex gap-3 transition border-b border-gray-200 last:border-b-0 hover:bg-gray-50 cursor-pointer';
                                article.setAttribute('itemscope', '');
                                article.setAttribute('itemtype', 'https://schema.org/Product');
                                article.onclick = (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    console.log('Search result clicked:', product);
                                    const fullProductData = searchResults.find(p => p.id === product.id);
                                    console.log('Found product data:', fullProductData);
                                    if(fullProductData && fullProductData.slug) {
                                        window.dispatchEvent(new CustomEvent('open-product-modal', { detail: fullProductData }));
                                        window.dispatchEvent(new CustomEvent('close-search-modal-from-js'));
                                    } else {
                                        console.error('Could not find full product data or slug for:', product, fullProductData);
                                    }
                                };

                                const categoriesHtml = product.categories.map(cat => `<span class="inline-block bg-gray-100 px-2 py-0.5 text-gray-600 rounded text-xs">${cat.name}</span>`).join('');

                                article.innerHTML = `
                                    <img src="${product.logo || product.favicon}" alt="${product.name} logo" class="size-9 rounded object-cover flex-shrink-0" loading="lazy" itemprop="image" />
                                    <div class="flex-1">
                                        <h2 class="text-lg font-bold leading-tight mb-0.5" itemprop="name">
                                            ${product.name}
                                        </h2>
                                        <p class="text-gray-700 text-sm mt-0.5 mb-1 line-clamp-2" itemprop="description">${product.tagline}</p>
                                        <div class="flex flex-wrap gap-2 mt-1">
                                            ${categoriesHtml}
                                        </div>
                                    </div>
                                `;
                                container.appendChild(article);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        container.innerHTML =
                            '<p class="text-red-500 text-center py-4">An error occurred while searching. Please try again.</p>';
                    });
            }, 300); 
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @stack('scripts')

    <!-- Product Details Modal -->
    <div x-show="isProductModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900 bg-opacity-75"
         @keydown.escape.window="closeProductModal()"
         x-cloak>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 md:p-8"
             @click.outside="closeProductModal()">
            <template x-if="selectedProduct">
                <div>
                    <div class="flex items-start mb-6">
                        {{-- Logo on the left --}}
                        <div class="mr-4 flex-shrink-0">
                            <template x-if="selectedProduct.logo">
                                <img :src="selectedProduct.logo.startsWith('http') ? selectedProduct.logo : `/storage/${selectedProduct.logo}`"
                                     :alt="selectedProduct.name + ' logo'"
                                     class="w-16 h-16 md:w-16 md:h-16 object-contain rounded-lg">
                            </template>
                            <template x-if="!selectedProduct.logo && selectedProduct.link">
                                <img :src="`https://www.google.com/s2/favicons?sz=64&domain_url=${encodeURIComponent(selectedProduct.link)}`"
                                     :alt="selectedProduct.name + ' favicon'"
                                     class="w-16 h-16 md:w-20 md:h-20 object-contain rounded-lg">
                            </template>
                            <template x-if="!selectedProduct.logo && !selectedProduct.link">
                                <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-200  rounded-lg flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-400 " fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            </template>
                        </div>
                        {{-- Name and Tagline --}}
                        <div class="flex-grow">
                            <h2 class="text-lg md:text-lg font-semibold text-gray-900 " x-text="selectedProduct.name"></h2>
                            <p class="text-gray-800 text-base" x-text="selectedProduct.tagline"></p>
                        </div>
                        {{-- Close Button --}}
                        <button @click="closeProductModal()" class="text-gray-400  hover:text-gray-600  ml-4 flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="prose max-w-none text-sm mb-6">
                        <p x-html="selectedProduct.description || 'No description available.'"></p>
                    </div>
                    
                    {{-- Software Categories (excluding Pricing) --}}
                    <div class="mb-6" x-show="selectedProduct.categories && selectedProduct.categories.filter(cat => !cat.types || !cat.types.some(t => t.name === 'Pricing')).length > 0">
                        <h3 class="text-sm font-medium text-gray-800 mb-2">Categories</h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="category in selectedProduct.categories.filter(cat => !cat.types || !cat.types.some(t => t.name === 'Pricing'))" :key="category.id">
                                <span class="text-gray-700 text-xs" x-text="category.name"></span>
                            </template>
                        </div>
                    </div>

                    {{-- Pricing Categories --}}
                    <div class="mb-6" x-show="selectedProduct.categories && selectedProduct.categories.some(cat => cat.types && cat.types.some(t => t.name === 'Pricing'))">
                        <h3 class="text-sm font-medium text-gray-800  mb-2">Pricing model</h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="category in selectedProduct.categories.filter(cat => cat.types && cat.types.some(t => t.name === 'Pricing'))" :key="category.id">
                                <span class="bg-gray-100  text-gray-700  px-3 py-1 text-xs rounded-sm" x-text="category.name"></span>
                            </template>
                        </div>
                    </div>

                    {{-- Pricing Information (direct fields) --}}
                    <div class="mb-6" x-show="selectedProduct.pricing_type || (selectedProduct.price && Number(selectedProduct.price) > 0)">
                        <h3 class="text-md font-semibold text-gray-800  mb-2">Pricing Information:</h3>
                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="text-gray-700  text-sm">
                                <template x-if="selectedProduct.pricing_type">
                                    <span x-text="selectedProduct.pricing_type"></span>
                                </template>
                                <template x-if="selectedProduct.pricing_type && selectedProduct.price && Number(selectedProduct.price) > 0">
                                    <span> - </span>
                                </template>
                                <template x-if="selectedProduct.price && Number(selectedProduct.price) > 0">
                                    <span x-text="`$${Number(selectedProduct.price).toFixed(2)}`"></span>
                                </template>
                            </span>
                        </div>
                    </div>
                    
                    {{-- Fallback if no specific information is available --}}
                    <template x-if="
                        !(selectedProduct.categories && selectedProduct.categories.filter(cat => !cat.types || !cat.types.some(t => t.name === 'Pricing')).length > 0) &&
                        !(selectedProduct.categories && selectedProduct.categories.some(cat => cat.types && cat.types.some(t => t.name === 'Pricing'))) &&
                        !(selectedProduct.pricing_type || (selectedProduct.price && Number(selectedProduct.price) > 0))
                    ">
                         <p class="text-gray-500  italic mb-6">No category or pricing information available.</p>
                    </template>
                    
                    <div class="mt-8 flex justify-end space-x-3">
                         <a :href="selectedProduct.link + (new URL(selectedProduct.link).search ? '&' : '?') + 'utm_source=softwareontheweb.com'"
                           target="_blank" rel="noopener nofollow"
                           class="inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-600 active:bg-primary-700 focus:outline-none focus:border-primary-700 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Visit Product Page
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <button @click="closeProductModal()" type="button" class="px-4 py-2 text-sm font-medium text-gray-700  bg-white  border border-gray-300  rounded-md shadow-sm hover:bg-gray-50  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500  ">
                            Close
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <x-modal name="login-required-modal" :show="false" maxWidth="md" focusable>
        @include('auth.partials.login-modal-content')
        </x-modal>
    
    @livewireScripts
    <script>
        const bodyData = document.body.dataset;
        window.isAuthenticated = bodyData.isAuthenticated === '1';
        window.loginUrl = bodyData.loginUrl;
        window.csrfToken = bodyData.csrfToken;
        window.primaryColorCssVar = 'var(--color-primary-500)';
        window.upvoteActiveClass = 'text-[var(--color-primary-500)]';
        window.upvoteInactiveClass = 'text-gray-400 hover:text-gray-600  ';
    </script>
    </body>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('upvote', (initialUpvoted, initialVotesCount, productId, productSlug, isAuthenticated, csrfToken) => ({
            isUpvoted: initialUpvoted,
            votesCount: initialVotesCount,
            isLoading: false,
            errorMessage: '',
            isAuthenticated: isAuthenticated,
            csrfToken: csrfToken,

            async toggleUpvote() {
                if (this.isLoading) return;

                if (!this.isAuthenticated) {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'login-required-modal' }));
                    return;
                }

                this.isLoading = true;
                this.errorMessage = '';

                const method = this.isUpvoted ? 'DELETE' : 'POST';
                const url = `/api/products/${productSlug}/upvote`;

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        credentials: 'include',
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.isUpvoted = !this.isUpvoted;
                        this.votesCount = data.votes_count;
                    } else {
                        this.errorMessage = data.message || 'An error occurred.';
                        // Sync state with server if there's a conflict
                        if (response.status === 409 || response.status === 404) {
                            this.votesCount = data.votes_count;
                            this.isUpvoted = (response.status === 409);
                        }
                    }
                } catch (error) {
                    this.errorMessage = 'A network error occurred. Please try again.';
                } finally {
                    this.isLoading = false;
                    if (this.errorMessage) {
                        setTimeout(() => this.errorMessage = '', 3000);
                    }
                }
            }
        }));
    });
</script>
</html>