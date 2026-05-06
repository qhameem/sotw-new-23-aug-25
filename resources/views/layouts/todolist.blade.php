<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
        open: false,
    }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        window.loadDelayedScripts = function () {
            if (window.delayedScriptsLoaded) return;
            window.delayedScriptsLoaded = true;

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

    <title>@yield('title', 'Software on the Web')</title>
    <meta name="description" content="@yield('meta_description', '')">
    <meta name="application-name" content="Software on the Web">
    <meta property="og:site_name" content="Software on the Web">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Software on the Web')">
    <meta property="og:description" content="@yield('meta_description', '')">
    @php
        $resolvedSocialImage = filled($meta_og_image ?? null) ? $meta_og_image : ($globalDefaultOgImageUrl ?? null);
    @endphp
    <meta name="twitter:card" content="{{ $resolvedSocialImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="@yield('title', 'Software on the Web')">
    <meta name="twitter:description" content="@yield('meta_description', '')">
    @if($resolvedSocialImage)
        <meta property="og:image" content="{{ $resolvedSocialImage }}">
        <meta name="twitter:image" content="{{ $resolvedSocialImage }}">
    @endif

    @include('partials.theme.favicon-links')

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
    @endphp

    <style>
        :root {
            --font-family-sans: {!! $fontCssStack !!};
            --color-site-text: {{ $siteFontColor }};
            --color-site-body-text: {{ $siteBodyTextColor }};
        }

        html,
        body {
            font-family: var(--font-family-sans);
            color: var(--color-site-body-text);
        }
    </style>

    @vite(['resources/css/app.css', 'resources/css/todo-vendor.css', 'resources/js/app.js'])
    @include('partials.theme.text-color-overrides')
    @livewireStyles
    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-100 pt-16" data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
    data-login-url="{{ route('login') }}" data-csrf-token="{{ csrf_token() }}" data-auth-sync-event="{{ session('auth_sync_event', '') }}"
    data-auth-session-state="{{ Auth::check() ? 'authenticated' : 'guest' }}">
    <div data-modal-scroll-lock-fixed class="fixed top-5 left-0 right-0 z-10">
        <div
            class="sm:max-w-xl md:max-w-[640px] lg:max-w-[640px] xl:max-w-[640px] mx-auto px-8 border bg-white opacity-90 rounded-full">
            <div class="flex justify-between items-center py-4">
                <h1 class="text-3xl font-medium text-gray-800">
                    Free Todo List App
                </h1>
                @include('partials._right-sidebar-usermenu')
            </div>
        </div>
    </div>
    <div class="min-h-screen flex items-center justify-center pt-16">
        <div class="w-full max-w-2xl mx-auto p-4">
            @yield('content')

        </div>
    </div>

    <x-modal name="login-required-modal" :show="session('status') === 'otp-sent' || $errors->has('email') || $errors->has('otp')" maxWidth="md" focusable>
        @include('auth.partials.login-modal-content')
    </x-modal>

    <template id="delayed-livewire-scripts">@livewireScripts</template>
    @stack('scripts')
    <script>
        const bodyData = document.body.dataset;
        window.isAuthenticated = bodyData.isAuthenticated === '1';
        window.loginUrl = bodyData.loginUrl;
        window.csrfToken = bodyData.csrfToken;
    </script>
</body>

</html>
