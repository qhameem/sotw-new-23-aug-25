<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
        searchModalOpen: false,
        open: false, // For mobile navigation
        initialPath: window.location.pathname, // Store the initial path
        intendedUrl: '',
        popularSearchContent: {{ Js::from(['products' => $popularSearchProducts ?? [], 'categories' => $popularSearchCategories ?? []]) }},
        searchResults: { products: [], categories: [] },
        searchTerm: '',
        searchLoading: false,
        searchController: null,
        hasSearchResults() {
            return this.searchResults.products.length > 0 || this.searchResults.categories.length > 0;
        },
        showDefaultSearchContent() {
            return this.searchTerm.trim().length < 2;
        },
        resetSearchState() {
            if (this.searchController) {
                this.searchController.abort();
            }

            this.searchController = null;
            this.searchTerm = '';
            this.searchResults = { products: [], categories: [] };
            this.searchLoading = false;
        },
        openSearchModal() {
            this.resetSearchState();
            this.searchModalOpen = true;
        },
        closeSearchModal() {
            this.searchModalOpen = false;
            this.resetSearchState();
        },
        performSearch: async function(term) {
            const query = term.trim();

            if (query.length < 2) {
                this.searchResults = { products: [], categories: [] };
                this.searchLoading = false;
                this.searchController = null;
                return;
            }

            if (this.searchController) {
                this.searchController.abort();
            }

            this.searchController = new AbortController();
            this.searchLoading = true;

            try {
                const response = await fetch(`/api/search?query=${encodeURIComponent(query)}`, {
                    signal: this.searchController.signal
                });

                if (response.ok) {
                    this.searchResults = await response.json();
                } else {
                    this.searchResults = { products: [], categories: [] };
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                    this.searchResults = { products: [], categories: [] };
                }
            } finally {
                this.searchLoading = false;
            }
        }
    }" x-init="() => {
        window.addEventListener('popstate', handlePopState.bind($data));
        initialPath = window.location.pathname;
        
        const initialProductSlugMatch = window.location.pathname.match(/^\/product\/([a-zA-Z0-9-]+)$/);
        if (initialProductSlugMatch && initialProductSlugMatch[1]) {
            console.log('Initial load on product page:', initialProductSlugMatch[1]);
        }
    }"
    @open-search-modal.window="openSearchModal()"
    @close-search-modal-from-js.window="closeSearchModal()" @open-login-modal.window="
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
    x-on:livewire:navigating.window="closeSearchModal(); if(typeof closeProductModal === 'function') closeProductModal();">

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

    <script>
        window.loadDelayedScripts = function () {
            if (window.delayedScriptsLoaded) return;
            window.delayedScriptsLoaded = true;

            // Load Google Analytics/Tag Manager
            const gaTemplate = document.getElementById('delayed-ga-code');
            if (gaTemplate) {
                const div = document.createElement('div');
                div.innerHTML = gaTemplate.innerHTML;
                Array.from(div.querySelectorAll('script')).forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    document.head.appendChild(newScript);
                });
            }

            // Load Head Snippets
            document.querySelectorAll('template.delayed-head-snippet').forEach(template => {
                const container = template.parentElement;
                const fragment = document.createDocumentFragment();
                const div = document.createElement('div');
                div.innerHTML = template.innerHTML;

                Array.from(div.childNodes).forEach(node => {
                    if (node.nodeName === 'SCRIPT') {
                        const newScript = document.createElement('script');
                        Array.from(node.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(node.innerHTML));
                        fragment.appendChild(newScript);
                    } else {
                        fragment.appendChild(node.cloneNode(true));
                    }
                });
                container.appendChild(fragment);
            });

            // Load Body Snippets
            document.querySelectorAll('template.delayed-body-snippet').forEach(template => {
                const container = template.parentElement;
                const fragment = document.createDocumentFragment();
                const div = document.createElement('div');
                div.innerHTML = template.innerHTML;

                Array.from(div.childNodes).forEach(node => {
                    if (node.nodeName === 'SCRIPT') {
                        const newScript = document.createElement('script');
                        Array.from(node.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(node.innerHTML));
                        fragment.appendChild(newScript);
                    } else {
                        fragment.appendChild(node.cloneNode(true));
                    }
                });
                container.appendChild(fragment);
            });

            // Load Livewire Scripts
            const livewireTemplate = document.getElementById('delayed-livewire-scripts');
            if (livewireTemplate && window.Livewire === undefined) {
                const div = document.createElement('div');
                div.innerHTML = livewireTemplate.innerHTML;
                Array.from(div.querySelectorAll('script')).forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    document.body.appendChild(newScript);
                });
            }
        };

        ['mouseover', 'keydown', 'touchmove', 'touchstart', 'wheel', 'scroll'].forEach(event => {
            window.addEventListener(event, window.loadDelayedScripts, { once: true, passive: true });
        });
    </script>

    <title>@yield('title', $meta_title ?? 'Software on the Web')</title>
    <meta name="description" content="@yield('meta_description', $meta_description ?? '')">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">

    @hasSection('canonical')
        @yield('canonical')
    @elseif(request()->routeIs('products.byWeek'))
        <link rel="canonical"
            href="{{ route('products.byWeek', ['year' => request()->route('year'), 'week' => request()->route('week')]) }}" />
    @elseif(request()->routeIs('products.byDate'))
        <link rel="canonical" href="{{ url()->current() }}" />
    @elseif(request()->routeIs('products.byMonth'))
        <link rel="canonical"
            href="{{ route('products.byMonth', ['year' => request()->route('year'), 'month' => request()->route('month')]) }}" />
    @elseif(request()->routeIs('products.byYear'))
        <link rel="canonical" href="{{ route('products.byYear', ['year' => request()->route('year')]) }}" />
    @elseif(request()->routeIs('home'))
        <link rel="canonical" href="{{ route('home') }}" />
    @elseif(request()->routeIs('products.show'))
        <link rel="canonical" href="{{ route('products.show', ['product' => $product->slug]) }}" />
    @elseif(request()->routeIs('categories.show'))
        <link rel="canonical" href="{{ route('categories.show', ['category' => $category->slug]) }}" />
    @endif

    @yield('preloads')

    <meta name="application-name" content="Software on the Web">
    <meta property="og:site_name" content="Software on the Web">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', $meta_title ?? 'Software on the Web')">
    <meta property="og:description" content="@yield('meta_description', $meta_description ?? '')">
    <meta name="twitter:title" content="@yield('title', $meta_title ?? 'Software on the Web')">
    @if(isset($meta_og_image))
        <meta property="og:image" content="{{ $meta_og_image }}">
    @endif
    @php
        $customLogoUrl = config('theme.logo_url');
        $siteLogo = $customLogoUrl ? \Illuminate\Support\Facades\Storage::url($customLogoUrl) : asset('favicon/apple-touch-icon.png');
    @endphp
    <meta property="og:logo" content="{{ $siteLogo }}">


    @if(config('theme.font_url'))
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ config('theme.font_url') }}" rel="stylesheet" media="print" onload="this.media='all'">
        <noscript>
            <link href="{{ config('theme.font_url') }}" rel="stylesheet">
        </noscript>
    @elseif($fontFamily === 'Inter')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
            rel="stylesheet" media="print" onload="this.media='all'">
        <noscript>
            <link
                href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
                rel="stylesheet">
        </noscript>
    @endif


    @php
        $fontFamily = config('theme.font_family', 'Inter');
        $siteFontColor = config('theme.font_color', '#111827');
        $siteBodyTextColor = config('theme.body_text_color', '#4b5563');
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
            --color-site-text: {{ $siteFontColor }};
            --color-site-body-text: {{ $siteBodyTextColor }};
            --color-primary-500: {{ $primaryHexColor }};
            --color-primary-600: {{ $primaryHexColor }};
            --color-primary-700: {{ $primaryHexColor }};
            --color-primary-button-text: {{ $primaryButtonTextColor }};
            --color-navbar-bg: {{ config('theme.navbar_bg_color', '#ffffff') }};
            --color-body-bg: {{ config('theme.body_bg_color', '#ffffff') }};
        }

        html,
        body {
            font-family: var(--font-family-sans);
            background-color: var(--color-body-bg);
            color: var(--color-site-body-text);
        }

        [x-cloak] {
            display: none !important;
        }

        [v-cloak] {
            display: none;
        }

        [data-modal-scroll-lock-fixed] {
            padding-right: var(--modal-scrollbar-compensation, 0px);
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
            {{-- Fallback for non-PNG custom favicons (e.g. SVG, or direct ICO) - browser might use the main one for other sizes
            --}}
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
        <template id="delayed-ga-code">{!! $gaCode !!}</template>
    @endif
    {{-- End Google Analytics Code Injection --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme.text-color-overrides')
    @livewireStyles

    @stack('styles')
    <!-- Schema markup -->
    @verbatim
        <script type="application/ld+json">
                                                                            {
                                                                              "@context": "https://schema.org",
                                                                              "@type": "WebSite",
                                                                              "name": "Software on the Web",
                                                                              "url": "https://softwareontheweb.com"
                                                                            }
                                                                            </script>
    @endverbatim


    @php
        $headSnippets = \App\Models\CodeSnippet::where('location', 'head')->get();
        $page = \Illuminate\Support\Facades\Route::currentRouteName();
    @endphp
    @foreach ($headSnippets as $snippet)
        @if ($snippet->shouldRenderFor(request()))
            <template class="delayed-head-snippet">{!! html_entity_decode($snippet->code) !!}</template>
        @endif
    @endforeach
</head>

<body class="font-sans antialiased" style="background-color: var(--color-body-bg);" x-data="{}" data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
    data-login-url="{{ route('login') }}" data-csrf-token="{{ csrf_token() }}" data-auth-sync-event="{{ session('auth_sync_event', '') }}"
    data-auth-session-state="{{ Auth::check() ? 'authenticated' : 'guest' }}">
    @php
        $bodySnippets = \App\Models\CodeSnippet::where('location', 'body')->get();
        $page = \Illuminate\Support\Facades\Route::currentRouteName();
    @endphp
    @foreach ($bodySnippets as $snippet)
        @if ($snippet->shouldRenderFor(request()))
            <template class="delayed-body-snippet">{!! html_entity_decode($snippet->code) !!}</template>
        @endif
    @endforeach

    @php
        $isArticleEditorRoute = request()->routeIs(
            'articles.create',
            'articles.edit',
            'admin.articles.posts.create',
            'admin.articles.posts.edit'
        );
        $isPseoRoute = request()->routeIs('pseo.*');
        $defaultPseoPagePadding = 'px-4 sm:px-6 lg:px-10 xl:px-12';
    @endphp

    <x-main-content-layout :main-content-max-width="$mainContentMaxWidth ?? ($isArticleEditorRoute ? 'max-w-none' : 'max-w-3xl')"
        :sidebar-sticky="!$isArticleEditorRoute"
        :lock-height="false"
        :container-max-width="$containerMaxWidth ?? ($isArticleEditorRoute ? 'max-w-none' : 'max-w-7xl')"
        :hide-sidebar="$hideSidebar ?? $isArticleEditorRoute"
        :hide-desktop-page-header="filled(trim($__env->yieldContent('hide_desktop_page_header')))"
        :header-padding="$headerPadding ?? ($isPseoRoute ? $defaultPseoPagePadding : 'px-4 sm:px-6 lg:px-8')"
        :main-padding="$mainPadding ?? ($isPseoRoute ? $defaultPseoPagePadding : 'px-4 sm:px-6 lg:px-8')">
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
                @php
                    $sidebarSnippets = \App\Models\CodeSnippet::where('location', 'sidebar')->get();
                    $page = \Illuminate\Support\Facades\Route::currentRouteName();
                @endphp
                <div class="space-y-6">
                    <div class="sidebar-snippets-container w-full overflow-x-auto">
                        @foreach ($sidebarSnippets as $snippet)
                            @if ($snippet->shouldRenderFor(request()))
                                <template class="delayed-body-snippet">{!! html_entity_decode($snippet->code) !!}</template>
                            @endif
                        @endforeach
                    </div>
                    @include('partials._right-sidebar')
                    {{ $right_sidebar_content ?? '' }}
                    @if(Request::is('free-todo-list-tool'))
                        @include('todolists._lists')
                    @endif
                </div>
            @endif
        </x-slot:right_sidebar_content>

        @if (isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </x-main-content-layout>

    <x-mobile-categories-menu />

    @include('partials._global-search-modal')

    @stack('scripts')
    @stack('form-scripts')
    <template id="delayed-livewire-scripts">@livewireScripts</template>

    <x-modal name="login-required-modal" :show="session('status') === 'magic-link-sent' || $errors->has('email')" maxWidth="md" focusable>
        @include('auth.partials.login-modal-content')
    </x-modal>

    <script>
        const bodyData = document.body.dataset;
        window.isAuthenticated = bodyData.isAuthenticated === '1';
        window.loginUrl = bodyData.loginUrl;
        window.csrfToken = bodyData.csrfToken;
        window.primaryColorCssVar = 'var(--color-primary-500)';
    </script>
</body>

</html>
