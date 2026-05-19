<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
        searchModalOpen: false,
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
        openSearchModal() {
            this.resetSearchState();
            this.searchModalOpen = true;
            this.loadPopularSearchContent();
        },
        closeSearchModal() {
            this.searchModalOpen = false;
            this.resetSearchState();
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
    "
    x-on:livewire:navigating.window="closeSearchModal(); if(typeof closeProductModal === 'function') closeProductModal();">

<head>
    <script>
        function handlePopState(event) {
            // This is a placeholder function to prevent the handlePopState is not defined error.
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

            // Load jQuery/Select2/Livewire
            const genericTemplate = document.getElementById('delayed-vendor-scripts');
            if (genericTemplate) {
                const div = document.createElement('div');
                div.innerHTML = genericTemplate.innerHTML;
                Array.from(div.querySelectorAll('script')).forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    if (oldScript.innerHTML) {
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    }
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

    <meta name="application-name" content="{{ config('app.name', 'Software on the Web') }}">
    <meta property="og:site_name" content="{{ config('app.name', 'Software on the Web') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">
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
        <link href="{{ config('theme.font_url') }}" rel="stylesheet">
    @elseif(config('theme.font_family', 'Inter') === 'Inter')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
            rel="stylesheet">
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
            --color-primary-500:
                {{ $primaryHexColor }}
            ;
            --color-primary-600:
                {{ $primaryHexColor600 }}
            ;
            --color-primary-700:
                {{ $primaryHexColor700 }}
            ;
            --color-primary-button-text:
                {{ $primaryButtonTextColor }}
            ;
        }

        html,
        body {
            font-family: var(--font-family-sans);
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme.text-color-overrides')
    @livewireStyles
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

<body class="font-sans antialiased bg-white" x-data="{}" data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
    data-login-url="{{ route('login') }}" data-csrf-token="{{ csrf_token() }}" data-auth-sync-event="{{ session('auth_sync_event', '') }}"
    data-auth-session-state="{{ Auth::check() ? 'authenticated' : 'guest' }}"
    data-route-name="{{ Route::currentRouteName() }}">

    <div class="flex flex-col min-h-screen bg-white">
        <x-top-bar />

        <!-- Mobile Header (visible only on mobile) -->
        <div data-modal-scroll-lock-fixed class="md:hidden fixed top-0 w-full z-50 bg-white h-[75px] border-b border-gray-200 flex-shrink-0">
            <div class="h-full px-4 flex items-center justify-between">
                <a href="{{ route('home') }}">
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                </a>
                <div class="flex items-center space-x-2">
                    @guest
                        <x-add-product-button compact />
                        <button
                            type="button"
                            @click.prevent="$dispatch('open-modal', { name: 'login-required-modal' })"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900"
                            aria-label="Sign in"
                            title="Sign in"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                <path fill-rule="evenodd" d="M17 4.25A2.25 2.25 0 0 0 14.75 2h-5.5A2.25 2.25 0 0 0 7 4.25v2a.75.75 0 0 0 1.5 0v-2a.75.75 0 0 1 .75-.75h5.5a.75.75 0 0 1 .75.75v11.5a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1-.75-.75v-2a.75.75 0 0 0-1.5 0v2A2.25 2.25 0 0 0 9.25 18h5.5A2.25 2.25 0 0 0 17 15.75V4.25Z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M1 10a.75.75 0 0 1 .75-.75h9.546l-1.048-.943a.75.75 0 1 1 1.004-1.114l2.5 2.25a.75.75 0 0 1 0 1.114l-2.5 2.25a.75.75 0 1 1-1.004-1.114l1.048-.943H1.75A.75.75 0 0 1 1 10Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @else
                        <div class="flex items-center space-x-2">
                            <x-add-product-button compact />
                            @auth
                                <div id="mobile-notification-bell-app">
                                    <notification-bell :user-id="{{ Auth::id() }}"></notification-bell>
                                </div>
                            @endauth
                            <div id="mobile-user-dropdown-app" data-user="{{ json_encode(Auth::user()) }}"
                                data-is-admin="{{ Auth::user()->hasRole('admin') ? 'true' : 'false' }}"></div>
                        </div>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Main Content Wrapper -->
        <div class="flex-1 w-full flex flex-col relative pt-[75px] md:pt-0">
            <main class="w-full flex-1 flex flex-col">
                @yield('content')
            </main>
        </div>
        <x-footer />
        <!-- Mobile navigation -->
        @include('partials._mobile-footer-menu')
    </div>

    @include('partials._global-search-modal')
    <template id="delayed-vendor-scripts">
        @livewireScripts
    </template>
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
                            const response = await fetch('{{ route('auth.login-modal-content') }}', {
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
        const bodyData = document.body.dataset;
        window.isAuthenticated = bodyData.isAuthenticated === '1';
        window.loginUrl = bodyData.loginUrl;
        window.csrfToken = bodyData.csrfToken;
        window.primaryColorCssVar = 'var(--color-primary-500)';
    </script>
</body>

</html>
