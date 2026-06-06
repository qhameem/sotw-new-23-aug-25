<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = trim((string) $__env->yieldContent('title', 'Launch Readiness Workspace'));
        $pageDescription = trim((string) $__env->yieldContent('meta_description', 'Manage launch-readiness scans, profile preferences, and account settings.'));
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="application-name" content="{{ $toolBrandingSiteName ?? config('app.name', 'Software on the Web') }}">
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
    class="min-h-screen"
    style="background: var(--lr-bg); color: var(--lr-text);"
    data-auth-session-state="authenticated"
    @if(session('auth_sync_event')) data-auth-sync-event="{{ session('auth_sync_event') }}" @endif
>
    <div
        class="min-h-screen lg:grid lg:grid-cols-[200px_minmax(0,1fr)]"
        x-data="launchReadinessWorkspace({ workspaceRoot: @js($toolPath) })"
        @click.capture="handleLinkClick"
    >
        <aside class="border-b px-6 py-6 lg:min-h-screen lg:border-b-0 lg:border-r" style="border-color: var(--lr-sidebar-border); background: color-mix(in srgb, var(--lr-bg) 88%, black 12%);">
            <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}" class="inline-flex rounded-2xl outline-none transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-[var(--lr-accent)] focus-visible:ring-offset-2" style="ring-offset-color: var(--lr-bg);">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/5 ring-1 ring-white/10">
                    <img src="{{ $toolBrandingLogoUrl }}" alt="{{ $toolBrandingSiteName }} logo" class="h-9 w-9 rounded-xl object-contain">
                </div>
            </a>

            <div class="mt-12">
                <nav class="space-y-2" x-ref="sidebarNav" data-workspace-sidebar-nav>
                    <a
                        href="{{ route('launch-readiness.dashboard', ['toolSlug' => $toolSlug]) }}"
                        class="flex items-center gap-3 rounded-[22px] px-4 py-4 transition"
                        style="{{ request()->routeIs('launch-readiness.dashboard*') ? 'background: var(--lr-panel-strong); color: var(--lr-text);' : 'color: var(--lr-muted);' }}"
                    >
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 1920 1920" fill="currentColor" aria-hidden="true">
                            <path d="M833.935 1063.327c28.913 170.315 64.038 348.198 83.464 384.79 27.557 51.84 92.047 71.944 144 44.387 51.84-27.558 71.717-92.273 44.16-144.113-19.426-36.593-146.937-165.46-271.624-285.064Zm-43.821-196.405c61.553 56.923 370.899 344.81 415.285 428.612 56.696 106.842 15.811 239.887-91.144 296.697-32.64 17.28-67.765 25.411-102.325 25.411-78.72 0-154.955-42.353-194.371-116.555-44.386-83.802-109.102-501.346-121.638-584.245-3.501-23.717 8.245-47.21 29.365-58.277 21.346-11.294 47.096-8.02 64.828 8.357ZM960.045 281.99c529.355 0 960 430.757 960 960 0 77.139-8.922 153.148-26.654 225.882l-10.39 43.144h-524.386v-112.942h434.258c9.487-50.71 14.231-103.115 14.231-156.084 0-467.125-380.047-847.06-847.059-847.06-467.125 0-847.059 379.935-847.059 847.06 0 52.97 4.744 105.374 14.118 156.084h487.454v112.942H36.977l-10.39-43.144C8.966 1395.137.044 1319.128.044 1241.99c0-529.243 430.645-960 960-960Zm542.547 390.686 79.85 79.85-112.716 112.715-79.85-79.85 112.716-112.715Zm-1085.184 0L530.123 785.39l-79.85 79.85L337.56 752.524l79.849-79.85Zm599.063-201.363v159.473H903.529V471.312h112.942Z" fill-rule="evenodd" />
                        </svg>
                        <span style="font-size: 14px; font-weight: 400; line-height: 1.1;">Dashboard</span>
                    </a>
                    <a
                        href="{{ route('launch-readiness.settings', ['toolSlug' => $toolSlug]) }}"
                        class="flex items-center gap-3 rounded-[22px] px-4 py-4 transition"
                        style="{{ request()->routeIs('launch-readiness.settings*') ? 'background: var(--lr-panel-strong); color: var(--lr-text);' : 'color: var(--lr-muted);' }}"
                    >
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                            <path d="M13.7654 2.15224C13.3978 2 12.9319 2 12 2C11.0681 2 10.6022 2 10.2346 2.15224C9.74457 2.35523 9.35522 2.74458 9.15223 3.23463C9.05957 3.45834 9.0233 3.7185 9.00911 4.09799C8.98826 4.65568 8.70226 5.17189 8.21894 5.45093C7.73564 5.72996 7.14559 5.71954 6.65219 5.45876C6.31645 5.2813 6.07301 5.18262 5.83294 5.15102C5.30704 5.08178 4.77518 5.22429 4.35436 5.5472C4.03874 5.78938 3.80577 6.1929 3.33983 6.99993C2.87389 7.80697 2.64092 8.21048 2.58899 8.60491C2.51976 9.1308 2.66227 9.66266 2.98518 10.0835C3.13256 10.2756 3.3397 10.437 3.66119 10.639C4.1338 10.936 4.43789 11.4419 4.43786 12C4.43783 12.5581 4.13375 13.0639 3.66118 13.3608C3.33965 13.5629 3.13248 13.7244 2.98508 13.9165C2.66217 14.3373 2.51966 14.8691 2.5889 15.395C2.64082 15.7894 2.87379 16.193 3.33973 17C3.80568 17.807 4.03865 18.2106 4.35426 18.4527C4.77508 18.7756 5.30694 18.9181 5.83284 18.8489C6.07289 18.8173 6.31632 18.7186 6.65204 18.5412C7.14547 18.2804 7.73556 18.27 8.2189 18.549C8.70224 18.8281 8.98826 19.3443 9.00911 19.9021C9.02331 20.2815 9.05957 20.5417 9.15223 20.7654C9.35522 21.2554 9.74457 21.6448 10.2346 21.8478C10.6022 22 11.0681 22 12 22C12.9319 22 13.3978 22 13.7654 21.8478C14.2554 21.6448 14.6448 21.2554 14.8477 20.7654C14.9404 20.5417 14.9767 20.2815 14.9909 19.902C15.0117 19.3443 15.2977 18.8281 15.781 18.549C16.2643 18.2699 16.8544 18.2804 17.3479 18.5412C17.6836 18.7186 17.927 18.8172 18.167 18.8488C18.6929 18.9181 19.2248 18.7756 19.6456 18.4527C19.9612 18.2105 20.1942 17.807 20.6601 16.9999C21.1261 16.1929 21.3591 15.7894 21.411 15.395C21.4802 14.8691 21.3377 14.3372 21.0148 13.9164C20.8674 13.7243 20.6602 13.5628 20.3387 13.3608C19.8662 13.0639 19.5621 12.558 19.5621 11.9999C19.5621 11.4418 19.8662 10.9361 20.3387 10.6392C20.6603 10.4371 20.8675 10.2757 21.0149 10.0835C21.3378 9.66273 21.4803 9.13087 21.4111 8.60497C21.3592 8.21055 21.1262 7.80703 20.6602 7C20.1943 6.19297 19.9613 5.78945 19.6457 5.54727C19.2249 5.22436 18.693 5.08185 18.1671 5.15109C17.9271 5.18269 17.6837 5.28136 17.3479 5.4588C16.8545 5.71959 16.2644 5.73002 15.7811 5.45096C15.2977 5.17191 15.0117 4.65566 14.9909 4.09794C14.9767 3.71848 14.9404 3.45833 14.8477 3.23463C14.6448 2.74458 14.2554 2.35523 13.7654 2.15224Z" stroke="currentColor" stroke-width="1.5"></path>
                        </svg>
                        <span style="font-size: 14px; font-weight: 400; line-height: 1.1;">Settings</span>
                    </a>
                </nav>
            </div>
        </aside>

        <div class="min-h-screen">
            <header class="px-6 py-4">
                <div class="flex items-center justify-end">
                    @include('tools.launch-readiness.partials.user-menu')
                </div>
            </header>

            <main class="px-6 py-10 transition-opacity duration-150" x-ref="workspaceMain" data-workspace-main :class="navigating ? 'opacity-70 pointer-events-none' : 'opacity-100'">
                @if(session('status'))
                    <div class="mb-6 rounded-2xl border px-4 py-3 text-sm text-[var(--lr-text)]" style="border-color: color-mix(in srgb, var(--lr-success) 35%, transparent); background: color-mix(in srgb, var(--lr-success) 14%, transparent);">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 rounded-2xl border px-4 py-3 text-sm" style="border-color: color-mix(in srgb, var(--lr-danger) 35%, transparent); background: color-mix(in srgb, var(--lr-danger) 14%, transparent); color: var(--lr-text);">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
