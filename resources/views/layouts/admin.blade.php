<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Panel')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100"
      data-is-authenticated="{{ Auth::check() ? '1' : '0' }}"
      data-login-url="{{ route('login') }}"
      data-csrf-token="{{ csrf_token() }}">
    <div class="min-h-screen bg-gray-100" id="app">
        {{-- @include('layouts.navigation') --}}

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>
    </div>
    @stack('scripts')
    <script>
        const bodyData = document.body.dataset;
        window.isAuthenticated = bodyData.isAuthenticated === '1';
        window.loginUrl = bodyData.loginUrl;
        window.csrfToken = bodyData.csrfToken;
    </script>
</body>
</html>