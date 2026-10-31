@php
    $adminVariant = trim($__env->yieldContent('admin-layout-variant')) ?: 'standard';
    $activeAdminRoute = null;
    $adminPageTitle = 'Administration';
    foreach (config('admin-navigation.primary') as $item) {
        if (request()->routeIs(...$item['patterns'])) {
            $activeAdminRoute = $item['route'];
            $adminPageTitle = $item['label'];
            break;
        }
    }
    if ($activeAdminRoute === null) {
        foreach (config('admin-navigation.additional') as $item) {
            if (request()->routeIs(preg_replace('/\.index$/', '.*', $item['route']))) {
                $adminPageTitle = $item['label'];
                break;
            }
        }
    }
    $adminHeading = trim(strip_tags($__env->yieldContent('header-title')));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $adminHeading ?: $adminPageTitle) · {{ config('app.name') }}</title>
    @include('partials.theme.boot')
    @include('partials.theme.favicon-links')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="approval-workspace admin-workspace admin-variant-{{ $adminVariant }} {{ $adminVariant === 'approvals' && ($status ?? null) === 'pending' ? 'is-pending-view' : '' }}"
    data-is-authenticated="{{ auth()->check() ? '1' : '0' }}" data-login-url="{{ route('login') }}" data-csrf-token="{{ csrf_token() }}">
    <div class="approval-shell">
        @include('admin.partials.navigation')
        <main class="approval-main">
            <header class="approval-page-header">
                <div class="admin-page-heading">
                    @if($adminHeading !== '' && str_contains($__env->yieldContent('header-title'), '<'))
                        @yield('header-title')
                    @else
                        <h1>{{ $adminHeading ?: $adminPageTitle }}</h1>
                    @endif
                </div>
                <a href="{{ route('profile.edit') }}" class="approval-account"><span class="approval-account-avatar">{{ mb_substr(auth()->user()?->name ?: 'Admin', 0, 1) }}</span><span><strong>{{ auth()->user()?->name ?: 'Admin' }}</strong><small>{{ auth()->user()?->email }}</small></span></a>
            </header>
            @hasSection('actions')
                <div class="admin-page-actions">@yield('actions')</div>
            @endif
            @yield('below_header')
            <div class="admin-content">@yield('content')</div>
        </main>
    </div>
    @livewireScripts
    @stack('scripts')
    @stack('form-scripts')
</body>
</html>
