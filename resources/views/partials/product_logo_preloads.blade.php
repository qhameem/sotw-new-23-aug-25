@php
    use App\Support\ProductLogo;

    $productLogoPreloadUrls = ProductLogo::preloadUrls($products ?? [], $limit ?? ProductLogo::PRELOAD_LIMIT);
@endphp

@foreach($productLogoPreloadUrls as $productLogoPreloadUrl)
    <link rel="preload" as="image" href="{{ $productLogoPreloadUrl }}" fetchpriority="high">
@endforeach
