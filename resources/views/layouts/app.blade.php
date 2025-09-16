<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        searchModalOpen: false,
        open: false, // For mobile navigation
        initialPath: window.location.pathname, // Store the initial path
        intendedUrl: '',
    }"
    x-init="() => {
        window.addEventListener('popstate', handlePopState.bind($data));
        initialPath = window.location.pathname;
        
        const initialProductSlugMatch = window.location.pathname.match(/^\/product\/([a-zA-Z0-9-]+)$/);
        if (initialProductSlugMatch && initialProductSlugMatch[1]) {
            console.log('Initial load on product page:', initialProductSlugMatch[1]);
        }
    }"
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
    x-on:livewire:navigating.window="searchModalOpen = false; if(typeof closeProductModal === 'function') closeProductModal();">

<head>
    <script>
    function handlePopState(event) {
        // This is a placeholder function to prevent the "handlePopState is not defined" error.
        // You can add your own logic here to handle popstate events if needed.
        console.log('popstate event:', event);
    }
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $meta_title ?? 'Software on the Web')</title>
    <meta name="description" content="@yield('meta_description', $meta_description ?? '')">

   @yield('canonical')

    <meta name="application-name" content="Software on the Web">
    <meta property="og:site_name" content="Software on the Web">
    <meta property="og:title" content="@yield('title', $meta_title ?? 'Software on the Web')">
    <meta name="twitter:title" content="@yield('title', $meta_title ?? 'Software on the Web')">
    @if(isset($meta_og_image))
    <meta property="og:image" content="{{ $meta_og_image }}">
    @endif


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
    @if(!empty($gaCode) && !Auth::check()) 
        {!! $gaCode !!}
    @endif
    {{-- End Google Analytics Code Injection --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @stack('styles')

    @verbatim
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Software on the Web",
      "url": "https://www.softwareontheweb.com"
    }
    </script>
    @endverbatim
    @php
        $headSnippets = \App\Models\CodeSnippet::where('location', 'head')->get();
        $page = \Illuminate\Support\Facades\Route::currentRouteName();
    @endphp
    @foreach ($headSnippets as $snippet)
        @if ($snippet->page === 'all' || $snippet->page === $page)
            {!! $snippet->code !!}
        @endif
    @endforeach
</head>

<body class="font-sans antialiased bg-white"
      data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
      data-login-url="{{ route('login') }}"
      data-csrf-token="{{ csrf_token() }}">
    @php
        $bodySnippets = \App\Models\CodeSnippet::where('location', 'body')->get();
    @endphp
    @foreach ($bodySnippets as $snippet)
        @if ($snippet->page === 'all' || $snippet->page === $page)
            {!! $snippet->code !!}
        @endif
    @endforeach

    <x-main-content-layout :main-content-max-width="$mainContentMaxWidth ?? 'max-w-3xl'">
        <x-slot:title>
            @hasSection('header-title')
                @yield('header-title')
            @else
                {!! $title ?? '' !!}
            @endif
        </x-slot:title>
        <x-slot:actions>
            @yield('actions')
        </x-slot:actions>

        <x-slot:below_header>
            @hasSection('below_header')
                @yield('below_header')
            @endif
        </x-slot:below_header>


        <x-slot:right_sidebar_content>
            @hasSection('right_sidebar_content')
                @yield('right_sidebar_content')
            @else
                {{ $right_sidebar_content ?? '' }}
                @if(Request::is('free-todo-list-tool'))
                    @include('todolists._lists')
                @endif
            @endif
        </x-slot:right_sidebar_content>

        @if (isset($slot))
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
                                article.setAttribute('wire:navigate', '');
                                article.onclick = (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    console.log('Search result clicked:', product);
                                    const fullProductData = searchResults.find(p => p.id === product.id);
                                    console.log('Found product data:', fullProductData);
                                    if(fullProductData && fullProductData.slug) {
                                        window.location.href = `/product/${fullProductData.slug}`;
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"></script>
    @stack('scripts')

    <x-modal name="login-required-modal" :show="false" maxWidth="md" focusable>
        @include('auth.partials.login-modal-content')
        </x-modal>
    
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
</html>
