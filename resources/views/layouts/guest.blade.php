<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

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

    <title>{{ config('app.name', 'Software on the Web') }}</title>
    <meta name="description" content="@yield('meta_description', config('app.name'))">
    <meta name="application-name" content="Software on the Web">
    <meta property="og:site_name" content="Software on the Web">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name', 'Software on the Web') }}">
    <meta property="og:description" content="@yield('meta_description', config('app.name'))">
    @php
        $resolvedSocialImage = $globalDefaultOgImageUrl ?? null;
    @endphp
    <meta name="twitter:card" content="{{ $resolvedSocialImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ config('app.name', 'Software on the Web') }}">
    <meta name="twitter:description" content="@yield('meta_description', config('app.name'))">
    @if($resolvedSocialImage)
        <meta property="og:image" content="{{ $resolvedSocialImage }}">
        <meta name="twitter:image" content="{{ $resolvedSocialImage }}">
    @endif
    @php
        $customLogoUrl = config('theme.logo_url');
        $siteLogo = $customLogoUrl ? \Illuminate\Support\Facades\Storage::url($customLogoUrl) : asset('favicon/apple-touch-icon.png');
    @endphp
    <meta property="og:logo" content="{{ $siteLogo }}">

    <!-- Fonts -->
    {{--
    <link rel="preconnect" href="https://fonts.bunny.net"> --}}
    {{--
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" /> --}}

    <!-- Dynamically loaded Google Font -->
    @if(config('theme.font_url'))
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ config('theme.font_url') }}" rel="stylesheet">
    @elseif(config('theme.font_family', 'Figtree') === 'Figtree')
        {{-- Fallback font if not configured --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @endif

    @php
        $fontFamily = config('theme.font_family', 'Figtree'); // Default to Figtree for guest
        $fontCssStack = config('theme.font_css_stack', "'Figtree', sans-serif");
        $siteFontColor = config('theme.font_color', '#111827');
        $siteBodyTextColor = config('theme.body_text_color', '#4b5563');
        $primaryHexColor = config('theme.primary_color', '#3b82f6'); // Default to a blue hex

        // Basic validation for hex color format
        if (!preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $primaryHexColor)) {
            $primaryHexColor = '#3b82f6'; // Fallback to default blue if format is invalid
        }
        if (!preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $siteFontColor)) {
            $siteFontColor = '#111827';
        }
        if (!preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $siteBodyTextColor)) {
            $siteBodyTextColor = '#4b5563';
        }

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
                {{ $primaryHexColor }}
            ;
            /* Or a darkened version */
            --color-primary-700:
                {{ $primaryHexColor }}
            ;
            /* Or a further darkened version */
            --color-primary-button-text:
                {{ $primaryButtonTextColor }}
            ;
        }

        html,
        body {
            font-family: var(--font-family-sans);
            color: var(--color-site-body-text);
        }
    </style>

    @include('partials.theme.favicon-links')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme.text-color-overrides')
    @livewireStyles
</head>

<body class="font-sans text-gray-900 antialiased " data-auth-sync-event="{{ session('auth_sync_event', '') }}"
    data-auth-session-state="{{ Auth::check() ? 'authenticated' : 'guest' }}"> {{-- Added for consistency if dark mode is ever enabled globally
    --}}
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 "> {{-- Added --}}
        <div>
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white  shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
    <template id="delayed-livewire-scripts">@livewireScripts</template>
</body>

</html>
