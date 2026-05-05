@props([
    'padding' => 'px-4',
    'hideDesktop' => false,
])

@php
    $titleText = trim(strip_tags((string) $title));
    $hasTitle = $titleText !== '';
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
    <div class="flex justify-between items-center {{ $padding }} py-[0.78rem]">
        <div>
            <div class="flex items-center">
                <a href="{{ route('home') }}">
                    <img src="{{ $mobileFaviconUrl }}" alt="{{ config('theme.logo_alt_text', config('app.name', 'Logo')) }}"
                        class="mobile-favicon mr-2 w-10 h-10 md:hidden object-contain">
                </a>
                @if($hasTitle)
                    <h1 class="site-heading-text text-base md:text-xl font-semibold text-gray-600">{{ $title }}</h1>
                @endif
            </div>
        </div>
        <div class="flex items-center space-x-4">
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
                </style>
            @endpush
        </div>
    </div>
    @if (isset($below_header))
        {{ $below_header }}
    @endif
    <hr class="md:hidden">
</div>
