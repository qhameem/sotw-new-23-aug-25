<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = trim((string) $__env->yieldContent('title', 'Launch Readiness Checker'));
        $pageDescription = trim((string) $__env->yieldContent('meta_description', 'Free homepage audit for launch readiness, SEO, and AI visibility.'));
        $ogTitle = trim((string) $__env->yieldContent('og_title', $pageTitle));
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="application-name" content="{{ $toolBrandingSiteName ?? config('app.name', 'Software on the Web') }}">
    <meta property="og:site_name" content="{{ $toolBrandingSiteName ?? config('app.name', 'Software on the Web') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $toolOgImage ?? ($globalDefaultOgImageUrl ?? null) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $toolOgImage ?? ($globalDefaultOgImageUrl ?? null) }}">

    @include('tools.launch-readiness.partials.favicon-links')

    @if($toolBrandingFontUrl ?? false)
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ $toolBrandingFontUrl }}" rel="stylesheet">
    @elseif(config('theme.font_family', 'Inter') === 'Inter')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
              rel="stylesheet">
    @endif

    @php
        $fontCssStack = $toolBrandingFontCssStack ?? config('theme.font_css_stack', "'Inter', sans-serif");
    @endphp

    <style>
        :root {
            --font-family-sans: {!! $fontCssStack !!};
            --lr-brand-bg: {{ $toolBrandingBackgroundColor ?? '#f5f5f4' }};
            --lr-brand-text: {{ $toolBrandingFontColor ?? '#161616' }};
            --lr-base-font-size: {{ (int) ($toolBrandingFontSize ?? 16) }}px;
            --lr-bg: var(--lr-brand-bg);
            --lr-panel: #ffffff;
            --lr-panel-soft: #f7f7f6;
            --lr-panel-strong: #f0f0ee;
            --lr-text: var(--lr-brand-text);
            --lr-muted: color-mix(in srgb, var(--lr-text) 62%, white 38%);
            --lr-subtle: color-mix(in srgb, var(--lr-text) 48%, white 52%);
            --lr-border: rgba(15, 23, 42, 0.08);
            --lr-sidebar-border: rgba(15, 23, 42, 0.1);
            --lr-accent: var(--lr-brand-text);
            --lr-success: #0f9f6e;
            --lr-warning: #b86e00;
            --lr-danger: #db2955;
        }

        [x-cloak] {
            display: none !important;
        }

        html,
        body {
            font-family: var(--font-family-sans);
            font-size: var(--lr-base-font-size);
        }
    </style>

    @vite(['resources/css/public.css', 'resources/js/app.js'])
    @yield('schema')
</head>
<body
    class="min-h-screen antialiased"
    style="background: var(--lr-brand-bg); color: var(--lr-brand-text);"
    data-auth-session-state="{{ $toolUser ? 'authenticated' : 'guest' }}"
    @if(session('auth_sync_event')) data-auth-sync-event="{{ session('auth_sync_event') }}" @endif
>
    <div
        class="flex min-h-screen flex-col"
        x-data="launchReadinessAuthModal({
            openOnLoad: @js($errors->has('email') || $errors->has('otp') || session('status') === 'otp-sent'),
            intendedUrl: @js(url()->current()),
        })"
    >
        <header class="sticky top-0 z-20 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-6">
                    <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}" class="flex items-center gap-3 text-sm font-semibold text-slate-900">
                        <img src="{{ $toolBrandingLogoUrl }}" alt="{{ $toolBrandingSiteName }} logo" class="h-9 w-9 object-contain">
                        <span>{{ $toolBrandingSiteName }}</span>
                    </a>
                    <a href="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="text-sm text-slate-600 transition hover:text-slate-900">History</a>
                    <div class="group relative hidden sm:block">
                        <button type="button" class="inline-flex items-center gap-1 text-sm text-slate-600 transition group-hover:text-slate-900">
                            Tools
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.937a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="invisible absolute left-0 top-full mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 opacity-0 shadow-xl shadow-slate-200/60 transition group-hover:visible group-hover:opacity-100">
                            <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}" class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-900">{{ $toolBrandingSiteName }}</a>
                            <a href="{{ app(\App\Support\ToolSettings::class)->url(\App\Support\ToolSettings::TODO_LIST_KEY) }}" class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-900">To-Do List</a>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if($toolUser)
                        @include('tools.launch-readiness.partials.user-menu')
                    @elseif(request()->routeIs('launch-readiness.auth.login'))
                        <a href="{{ $toolPath }}" class="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Back
                        </a>
                    @else
                        <button
                            type="button"
                            @click="openSignInModal()"
                            class="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            Sign in
                        </button>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-10 sm:px-6 sm:py-12">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200/80 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p>&copy; {{ date('Y') }} {{ $toolBrandingSiteName }}</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="transition hover:text-slate-700">History</a>
                    <a href="https://softwareontheweb.com" class="transition hover:text-slate-700">Software on the Web</a>
                </div>
            </div>
        </footer>

        @unless($toolUser || request()->routeIs('launch-readiness.auth.login'))
            <div
                x-cloak
                x-show="signInModalOpen"
                x-transition.opacity
                @keydown.escape.window="closeSignInModal()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6 backdrop-blur-sm"
            >
                <div class="absolute inset-0" @click="closeSignInModal()"></div>

                <div
                    class="relative z-10 w-full max-w-md"
                    @click.stop
                >
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Continue to your tool account</h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    Use Google or email. New tool accounts are created automatically the first time you continue.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="closeSignInModal()"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                                aria-label="Close sign in"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                        </div>

                        @include('tools.launch-readiness.auth.partials.panel', ['intended' => url()->current(), 'embedded' => true])
                    </div>
                </div>
            </div>
        @endunless
    </div>
</body>
</html>
