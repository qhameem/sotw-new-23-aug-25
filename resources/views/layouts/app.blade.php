<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
        searchModalOpen: false,
        searchModalLoaded: true,
        searchModalLoading: false,
        searchModalError: '',
        open: false, // For mobile navigation
        initialPath: window.location.pathname, // Store the initial path
        intendedUrl: '',
        popularSearchContent: { products: [], categories: [] },
        popularSearchLoaded: false,
        popularSearchLoading: false,
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
        isTypingTarget(target) {
            if (!target) {
                return false;
            }

            const tagName = target.tagName ? target.tagName.toLowerCase() : '';
            const canCheckClosest = typeof target.closest === 'function';

            return target.isContentEditable
                || ['input', 'textarea', 'select'].includes(tagName)
                || (canCheckClosest && target.closest('[contenteditable]'));
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
        loadSearchModalContent() {
            this.searchModalError = '';
            this.searchModalLoaded = true;

            this.$nextTick(() => {
                document.getElementById('globalSearchInput')?.focus();
            });
        },
        openSearchModal() {
            this.resetSearchState();
            this.searchModalOpen = true;
            this.loadSearchModalContent();
            this.loadPopularSearchContent();
        },
        closeSearchModal() {
            this.searchModalOpen = false;
            this.resetSearchState();
            this.searchModalError = '';
        },
        loadPopularSearchContent: async function() {
            if (this.popularSearchLoaded || this.popularSearchLoading) {
                return;
            }

            this.popularSearchLoading = true;

            try {
                const response = await fetch('/api/search/defaults');
                if (!response.ok) {
                    throw new Error('Failed to load search defaults.');
                }

                const data = await response.json();
                this.popularSearchContent = {
                    products: Array.isArray(data.products) ? data.products : [],
                    categories: Array.isArray(data.categories) ? data.categories : [],
                };
                this.popularSearchLoaded = true;
            } catch (error) {
                console.error('Popular search defaults error:', error);
                this.popularSearchContent = { products: [], categories: [] };
            } finally {
                this.popularSearchLoading = false;
            }
        },
        handleSearchShortcut(event) {
            const key = event.key ? event.key.toLowerCase() : '';

            if (key !== 'k' || (!event.metaKey && !event.ctrlKey) || event.altKey || event.shiftKey) {
                return;
            }

            if (this.isTypingTarget(event.target) && (!event.target || event.target.id !== 'globalSearchInput')) {
                return;
            }

            event.preventDefault();

            if (this.searchModalOpen) {
                const searchInput = document.getElementById('globalSearchInput');

                if (searchInput) {
                    searchInput.focus();
                }
                return;
            }

            this.openSearchModal();
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
    @keydown.window="handleSearchShortcut($event)"
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
    ">

<head>
    <script>
        function handlePopState(event) {
            // This is a placeholder function to prevent the "handlePopState is not defined" error.
            // You can add your own logic here to handle popstate events if needed.
            console.log('popstate event:', event);
        }
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        window.appendDelayedHtml = function (container, html) {
            if (!container || !html) {
                return;
            }

            const fragment = document.createDocumentFragment();
            const div = document.createElement('div');
            div.innerHTML = html;

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
        };

        window.processDelayedTemplate = function (template) {
            if (!template || template.dataset.processed === 'true') {
                return;
            }

            template.dataset.processed = 'true';
            window.appendDelayedHtml(template.parentElement, template.innerHTML);
        };

        window.fetchDelayedAssets = async function () {
            if (window.delayedAssetsLoaded || window.delayedAssetsLoading) return;

            window.delayedAssetsLoading = true;

            try {
                const routeName = document.body?.dataset?.routeName || '';
                const path = window.location.pathname || '/';
                const response = await fetch(`/api/deferred-assets?route_name=${encodeURIComponent(routeName)}&path=${encodeURIComponent(path)}`);

                if (!response.ok) {
                    throw new Error('Failed to load deferred assets.');
                }

                const data = await response.json();

                if (data.ga_code) {
                    window.appendDelayedHtml(document.head, data.ga_code);
                }

                (data.head_snippets || []).forEach((html) => window.appendDelayedHtml(document.head, html));
                (data.body_snippets || []).forEach((html) => window.appendDelayedHtml(document.body, html));

                const sidebarContainer = document.querySelector('.sidebar-snippets-container');
                if (sidebarContainer) {
                    (data.sidebar_snippets || []).forEach((html) => window.appendDelayedHtml(sidebarContainer, html));
                }

                window.delayedAssetsLoaded = true;
            } catch (error) {
                console.error('Deferred asset loading error:', error);
            } finally {
                window.delayedAssetsLoading = false;
            }
        };

        window.loadDelayedScripts = function () {
            if (window.delayedScriptsLoaded) return;
            window.delayedScriptsLoaded = true;

            window.fetchDelayedAssets();
            document.querySelectorAll('template.delayed-head-snippet').forEach(window.processDelayedTemplate);
            document.querySelectorAll('template.delayed-body-snippet').forEach(window.processDelayedTemplate);

        };

        ['mouseover', 'keydown', 'touchmove', 'touchstart', 'wheel', 'scroll'].forEach(event => {
            window.addEventListener(event, window.loadDelayedScripts, { once: true, passive: true });
        });
    </script>

    <title>@yield('title', $meta_title ?? 'Software on the Web')</title>
    <meta name="description" content="@yield('meta_description', $meta_description ?? '')">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">

    @php
        $canonicalProduct = request()->route('product');
        $canonicalProductSlug = is_object($canonicalProduct) ? ($canonicalProduct->slug ?? null) : $canonicalProduct;
        $canonicalCategory = request()->route('category');
        $canonicalCategorySlug = is_object($canonicalCategory) ? ($canonicalCategory->slug ?? null) : $canonicalCategory;
    @endphp

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
    @elseif(request()->routeIs('products.show') && filled($canonicalProductSlug))
        <link rel="canonical" href="{{ route('products.show', ['product' => $canonicalProductSlug]) }}" />
    @elseif(request()->routeIs('categories.show') && filled($canonicalCategorySlug))
        <link rel="canonical" href="{{ route('categories.show', ['category' => $canonicalCategorySlug]) }}" />
    @endif

    @yield('preloads')

    <meta name="application-name" content="{{ config('app.name', 'Software on the Web') }}">
    <meta property="og:site_name" content="{{ config('app.name', 'Software on the Web') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', $meta_title ?? 'Software on the Web')">
    <meta property="og:description" content="@yield('meta_description', $meta_description ?? '')">
    @php
        $resolvedSocialImage = filled($meta_og_image ?? null) ? $meta_og_image : ($globalDefaultOgImageUrl ?? null);
    @endphp
    <meta name="twitter:card" content="{{ $resolvedSocialImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="@yield('title', $meta_title ?? 'Software on the Web')">
    <meta name="twitter:description" content="@yield('meta_description', $meta_description ?? '')">
    @if($resolvedSocialImage)
        <meta property="og:image" content="{{ $resolvedSocialImage }}">
        <meta name="twitter:image" content="{{ $resolvedSocialImage }}">
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
    @elseif(config('theme.font_family', 'Inter') === 'Inter')
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
        $fontCssStack = config('theme.font_css_stack', "'Inter', sans-serif");
        $siteFontColor = config('theme.font_color', '#111827');
        $siteBodyTextColor = config('theme.body_text_color', '#4b5563');
        $primaryHexColor = config('theme.primary_color', '#3b82f6');

        if (!preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $primaryHexColor)) {
            $primaryHexColor = '#3b82f6';
        }

        if (strlen($primaryHexColor) === 4) {
            $primaryHexColor = '#' . $primaryHexColor[1] . $primaryHexColor[1]
                . $primaryHexColor[2] . $primaryHexColor[2]
                . $primaryHexColor[3] . $primaryHexColor[3];
        }

        $darkenHex = static function (string $hexColor, float $amount): string {
            $channels = [
                hexdec(substr($hexColor, 1, 2)),
                hexdec(substr($hexColor, 3, 2)),
                hexdec(substr($hexColor, 5, 2)),
            ];

            $darkenedChannels = array_map(
                static fn (int $channel): int => max(0, min(255, (int) round($channel * (1 - $amount)))),
                $channels
            );

            return sprintf('#%02X%02X%02X', $darkenedChannels[0], $darkenedChannels[1], $darkenedChannels[2]);
        };

        $primaryHexColor600 = $darkenHex($primaryHexColor, 0.08);
        $primaryHexColor700 = $darkenHex($primaryHexColor, 0.16);

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
            --font-family-sans: {!! $fontCssStack !!};
            --color-site-text: {{ $siteFontColor }};
            --color-site-body-text: {{ $siteBodyTextColor }};
            --color-primary-500: {{ $primaryHexColor }};
            --color-primary-600: {{ $primaryHexColor600 }};
            --color-primary-700: {{ $primaryHexColor700 }};
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

    @include('partials.theme.favicon-links')

    @vite(['resources/css/public.css', 'resources/js/app.js'])
    @livewireStyles

    @stack('styles')
    <!-- Schema markup -->
    @php
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name', 'Software on the Web'),
            'alternateName' => ['softwareontheweb.com', 'SOTW'],
            'url' => rtrim(config('app.url', 'https://softwareontheweb.com'), '/'),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

</head>

<body class="font-sans antialiased" style="background-color: var(--color-body-bg);" x-data="{}" data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
    data-login-url="{{ route('login') }}" data-csrf-token="{{ csrf_token() }}" data-auth-sync-event="{{ session('auth_sync_event', '') }}"
    data-auth-session-state="{{ Auth::check() ? 'authenticated' : 'guest' }}"
    data-route-name="{{ Route::currentRouteName() }}">

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
        <x-slot:before_title>
            @hasSection('before_header_title')
                @yield('before_header_title')
            @endif
        </x-slot:before_title>
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
                <div class="space-y-6">
                    <div class="sidebar-snippets-container w-full overflow-x-auto">
                    </div>
                    @include('partials._right-sidebar')
                    {{ $right_sidebar_content ?? '' }}
                    @if(request()->routeIs('todolists.*'))
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

    <div x-show="searchModalOpen && searchModalLoading && !searchModalLoaded" x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto bg-slate-950/45 px-4 py-6 sm:px-6 md:py-16"
        @click.self="closeSearchModal()" @keydown.escape.window="closeSearchModal()">
        <div class="mx-auto w-full max-w-lg">
            <div class="rounded-3xl border border-gray-200 bg-white px-6 py-10 text-center shadow-2xl">
                <p class="text-sm font-medium text-gray-900">Loading search...</p>
                <p class="mt-2 text-sm text-gray-500">Preparing products and categories.</p>
            </div>
        </div>
    </div>
    <div x-show="searchModalOpen && searchModalError" x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto bg-slate-950/45 px-4 py-6 sm:px-6 md:py-16"
        @click.self="closeSearchModal()" @keydown.escape.window="closeSearchModal()">
        <div class="mx-auto w-full max-w-lg">
            <div class="rounded-3xl border border-gray-200 bg-white px-6 py-10 text-center shadow-2xl">
                <p class="text-sm font-medium text-gray-900" x-text="searchModalError"></p>
                <button type="button" @click="closeSearchModal()"
                    class="mt-4 inline-flex items-center justify-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900">
                    Close
                </button>
            </div>
        </div>
    </div>
    @include('partials._global-search-modal')

    @livewireScripts
    @stack('scripts')
    @stack('form-scripts')

    @php
        $shouldEagerLoadLoginModal = session('status') === 'otp-sent' || $errors->has('email') || $errors->has('otp');
    @endphp
    <x-modal name="login-required-modal" :show="$shouldEagerLoadLoginModal" maxWidth="lg" :scrollable="false" :hideScrollbar="true" viewportPadding="compact" focusable>
        @if($shouldEagerLoadLoginModal)
            @include('auth.partials.login-modal-content')
        @else
            <div
                x-data="{
                    loaded: false,
                    loading: false,
                    async load() {
                        if (this.loaded || this.loading) {
                            return;
                        }

                        this.loading = true;

                        try {
                            const response = await fetch('{{ route('auth.login-modal-content', absolute: false) }}', {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            });

                            if (!response.ok) {
                                throw new Error('Failed to load login modal.');
                            }

                            this.$refs.content.innerHTML = await response.text();
                            window.Alpine?.initTree(this.$refs.content);
                            this.loaded = true;

                            this.$nextTick(() => {
                                this.$refs.content.querySelector('a, button, input:not([type=&quot;hidden&quot;]), textarea, select, [tabindex]:not([tabindex=&quot;-1&quot;])')?.focus();
                            });
                        } catch (error) {
                            console.error('Login modal loading error:', error);
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
                @open-modal.window="if ($event.detail.name === 'login-required-modal') load()"
                class="px-8 py-6 sm:px-10"
            >
                <div x-show="loading" class="py-12 text-center text-sm text-gray-500">Loading sign-in options...</div>
                <div x-ref="content"></div>
            </div>
        @endif
    </x-modal>

    <script>
        window.isAuthenticated = document.body.dataset.isAuthenticated === '1';
        window.loginUrl = document.body.dataset.loginUrl;
        window.csrfToken = document.body.dataset.csrfToken;
        window.primaryColorCssVar = 'var(--color-primary-500)';
    </script>
</body>

</html>
