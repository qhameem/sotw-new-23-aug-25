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
    <meta name="application-name" content="{{ config('app.name', 'Software on the Web') }}">
    <meta property="og:site_name" content="{{ config('app.name', 'Software on the Web') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $toolOgImage ?? ($globalDefaultOgImageUrl ?? null) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $toolOgImage ?? ($globalDefaultOgImageUrl ?? null) }}">

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
        $fontCssStack = config('theme.font_css_stack', "'Inter', sans-serif");
    @endphp

    <style>
        :root {
            --font-family-sans: {!! $fontCssStack !!};
        }

        html,
        body {
            font-family: var(--font-family-sans);
        }
    </style>

    @vite(['resources/css/public.css', 'resources/js/app.js'])
    @yield('schema')
</head>
<body class="min-h-screen bg-[#fafafa] text-slate-900 antialiased">
    <div class="min-h-screen">
        <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-6">
                    <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}" class="text-sm font-semibold text-slate-900">Site Health</a>
                    <a href="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="text-sm text-slate-600 transition hover:text-slate-900">History</a>
                    <div class="group relative hidden sm:block">
                        <button type="button" class="inline-flex items-center gap-1 text-sm text-slate-600 transition group-hover:text-slate-900">
                            Tools
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.937a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="invisible absolute left-0 top-full mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 opacity-0 shadow-xl shadow-slate-200/60 transition group-hover:visible group-hover:opacity-100">
                            <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}" class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-900">Launch Readiness Checker</a>
                            <a href="{{ app(\App\Support\ToolSettings::class)->url(\App\Support\ToolSettings::TODO_LIST_KEY) }}" class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-900">To-Do List</a>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if($toolUser)
                        <span class="hidden text-sm text-slate-500 sm:inline">{{ $toolUser->name ?? $toolUser->email }}</span>
                        <form method="POST" action="{{ route('launch-readiness.auth.logout', ['toolSlug' => $toolSlug]) }}">
                            @csrf
                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('launch-readiness.auth.login', ['toolSlug' => $toolSlug, 'intended' => url()->current()]) }}"
                           class="inline-flex h-9 items-center justify-center rounded-full border border-slate-200 px-4 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Sign in
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200/80 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Software on the Web') }}</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="transition hover:text-slate-700">History</a>
                    <a href="/robots.txt" class="transition hover:text-slate-700">Robots</a>
                    <a href="/sitemap.xml" class="transition hover:text-slate-700">Sitemap</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
