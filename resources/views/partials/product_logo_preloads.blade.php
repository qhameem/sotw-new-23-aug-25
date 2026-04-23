@php
    use App\Support\ProductLogo;

    $productLogoPreloadUrls = ProductLogo::preloadUrls($products ?? [], $limit ?? ProductLogo::PRELOAD_LIMIT);
    $shouldPreconnectGoogleFavicons = collect($productLogoPreloadUrls)
        ->contains(fn ($url) => str_starts_with($url, 'https://www.google.com/s2/favicons'));
@endphp

@if($shouldPreconnectGoogleFavicons)
    <link rel="preconnect" href="https://www.google.com">
    <link rel="dns-prefetch" href="//www.google.com">
@endif

@foreach($productLogoPreloadUrls as $productLogoPreloadUrl)
    <link rel="preload" as="image" href="{{ $productLogoPreloadUrl }}" fetchpriority="high">
@endforeach
