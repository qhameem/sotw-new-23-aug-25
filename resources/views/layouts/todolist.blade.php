<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        open: false,
    }"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Software on the Web')</title>
    <meta name="description" content="@yield('meta_description', '')">

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

    @if(config('theme.font_url'))
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ config('theme.font_url') }}" rel="stylesheet">
    @endif

    @php
        $fontFamily = config('theme.font_family', 'Roboto');
    @endphp

    <style>
        :root {
            --font-family-sans: '{{ $fontFamily }}', sans-serif;
        }
        html, body {
            font-family: var(--font-family-sans);
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 pt-16"
    data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
    data-login-url="{{ route('login') }}"
    data-csrf-token="{{ csrf_token() }}">
    <div class="fixed top-0 right-0 p-4">
        @include('partials._right-sidebar-usermenu')
    </div>
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-2xl mx-auto p-4">
            @guest
            <div class="text-center p-8 bg-white border rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Please log in to use the to-do list</h2>
                <p class="text-gray-600 mb-4 text-sm tracking-tight">Log in to save and access your to-do lists from anywhere.</p>
                <button @click.prevent="$dispatch('open-modal', { name: 'login-required-modal' })" class="bg-primary-500 text-white font-semibold text-sm hover:bg-primary-600 transition-colors duration-200 py-1 px-4 rounded-md hover:opacity-90">
                    Log in or Sign up &rarr;
                </button>
            </div>
            @else
                @yield('content')
            @endguest
    
            <footer class="text-center mt-8">
                <p class="text-xs text-gray-400">
                    A free Todo list tool by
                    <a href="{{ route('home') }}" class="underline hover:text-gray-600">
                        Software on the Web
                    </a>
                </p>
            </footer>
        </div>
    </div>

    <x-modal name="login-required-modal" :show="false" maxWidth="md" focusable>
        @include('auth.partials.login-modal-content')
    </x-modal>

    @livewireScripts
    @stack('scripts')
    <script>
        const bodyData = document.body.dataset;
        window.isAuthenticated = bodyData.isAuthenticated === '1';
        window.loginUrl = bodyData.loginUrl;
        window.csrfToken = bodyData.csrfToken;
    </script>
</body>
</html>