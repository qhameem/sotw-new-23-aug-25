@props([
    'padding' => 'px-4',
    'hideDesktop' => false,
])

@php
    $titleText = trim(strip_tags((string) $title));
    $hasTitle = $titleText !== '';
    $hasRichTitle = $hasTitle && str_contains((string) $title, '<');
    $hasBeforeTitle = isset($before_title) && trim(strip_tags((string) $before_title)) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
    $customFaviconPath = config('theme.favicon_url');
    $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
    $hasCustomFavicon = $customFaviconPath && $publicDisk->exists($customFaviconPath);
    $customFaviconDirectory = $hasCustomFavicon ? dirname($customFaviconPath) : null;

    $versionedStorageUrl = function (string $path) use ($publicDisk) {
        return \Illuminate\Support\Facades\Storage::url($path) . '?v=' . $publicDisk->lastModified($path);
    };

    $generatedMobileFaviconPath = $customFaviconDirectory ? $customFaviconDirectory . '/favicon-32x32.png' : null;
    $mobileFaviconUrl = $generatedMobileFaviconPath && $publicDisk->exists($generatedMobileFaviconPath)
        ? $versionedStorageUrl($generatedMobileFaviconPath)
        : ($hasCustomFavicon ? $versionedStorageUrl($customFaviconPath) : asset('favicon/favicon-32x32.png'));
@endphp

<div
    @class([
        'fixed top-0 left-0 right-0 w-full z-50 border-b border-gray-200 shadow-[0_1px_1px_rgba(0,0,0,0.03)]',
        'md:w-auto md:static md:sticky md:top-0 md:shadow-none md:border-none' => !$hideDesktop,
        'md:hidden' => $hideDesktop,
    ])
    style="background-color: var(--color-body-bg, #ffffff);">
    <div class="flex min-h-[76px] items-center justify-between gap-3 {{ $padding }} py-4 md:min-h-0 md:py-[0.78rem]">
        <div class="min-w-0 flex-1">
            @if($hasBeforeTitle)
                <div class="mb-3 hidden md:block">
                    {{ $before_title }}
                </div>
            @endif
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('home') }}" wire:navigate.hover>
                    <img src="{{ $mobileFaviconUrl }}" alt="{{ config('theme.logo_alt_text', config('app.name', 'Logo')) }}"
                        class="mobile-favicon h-10 w-10 shrink-0 object-contain md:hidden">
                </a>
                @if($hasTitle)
                    @if($hasRichTitle)
                        <div class="mobile-page-header-rich-title min-w-0 flex-1 text-gray-900">
                            {!! $title !!}
                        </div>
                    @else
                        <h1 class="site-heading-text min-w-0 flex-1 truncate text-base font-semibold leading-tight text-gray-700 md:text-xl md:text-gray-600" title="{{ $titleText }}">{{ $titleText }}</h1>
                    @endif
                @endif
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-2 md:gap-4">
            @unless($hasActions)
                <div class="md:hidden">
                    <x-add-product-button compact />
                </div>
            @endunless
            @if (isset($actions))
                {!! $actions !!}
            @endif
            <div class="md:hidden">
                <x-user-dropdown />
            </div>
            @push('styles')
                <style>
                    .mobile-favicon {
                        width: 40px;
                        height: 40px;
                        vertical-align: middle;
                    }

                    .mobile-page-header-rich-title>* {
                        margin: 0;
                    }

                    @media (max-width: 767px) {
                        .mobile-page-header-rich-title {
                            min-width: 0;
                        }

                        .mobile-page-header-rich-title h1,
                        .mobile-page-header-rich-title h2,
                        .mobile-page-header-rich-title h3,
                        .mobile-page-header-rich-title h4,
                        .mobile-page-header-rich-title h5,
                        .mobile-page-header-rich-title h6 {
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                            font-size: 1.125rem;
                            line-height: 1.4;
                            font-weight: 600;
                            color: rgb(17 24 39);
                        }

                        .mobile-page-header-rich-title p,
                        .mobile-page-header-rich-title .mobile-header-supporting-copy {
                            display: none;
                        }
                    }
                </style>
            @endpush
        </div>
    </div>
    @if (isset($below_header))
        {{ $below_header }}
    @endif
    <hr class="md:hidden">
</div>
