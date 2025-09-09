<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Software on the Web')</title>

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
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-2xl mx-auto p-4">
            @yield('content')
    
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

    @livewireScripts
    @stack('scripts')
</body>
</html>