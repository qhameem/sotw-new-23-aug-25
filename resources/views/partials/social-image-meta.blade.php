@php
    $socialImageUrl = $resolvedSocialImage ?? null;
    $socialImagePath = $socialImageUrl ? (parse_url($socialImageUrl, PHP_URL_PATH) ?: '') : '';
    $socialImageExtension = strtolower(pathinfo($socialImagePath, PATHINFO_EXTENSION));
    $socialImageType = $meta_og_image_type ?? match ($socialImageExtension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => null,
    };
    $usesGeneratedOgImage = str_contains($socialImagePath, '/og_images/');
    $socialImageWidth = $meta_og_image_width ?? ($usesGeneratedOgImage ? 1200 : null);
    $socialImageHeight = $meta_og_image_height ?? ($usesGeneratedOgImage ? 630 : null);
    $socialImageAlt = trim((string) ($meta_og_image_alt ?? config('app.name', 'Software on the Web') . ' social preview'));
@endphp

@if($socialImageUrl)
    <meta property="og:image" content="{{ $socialImageUrl }}">
    @if(str_starts_with($socialImageUrl, 'https://'))
        <meta property="og:image:secure_url" content="{{ $socialImageUrl }}">
    @endif
    @if($socialImageType)
        <meta property="og:image:type" content="{{ $socialImageType }}">
    @endif
    @if($socialImageWidth && $socialImageHeight)
        <meta property="og:image:width" content="{{ $socialImageWidth }}">
        <meta property="og:image:height" content="{{ $socialImageHeight }}">
    @endif
    <meta property="og:image:alt" content="{{ $socialImageAlt }}">
    <meta name="twitter:image" content="{{ $socialImageUrl }}">
    <meta name="twitter:image:alt" content="{{ $socialImageAlt }}">
@endif
