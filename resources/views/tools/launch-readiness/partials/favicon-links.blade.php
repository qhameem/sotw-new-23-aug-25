@if(!empty($toolBrandingFaviconUrl) || !empty($toolBrandingGeneratedIconUrls) || !empty($toolBrandingManifestUrl))
    @if(!empty($toolBrandingGeneratedIconUrls['favicon_32']))
        <link rel="icon" type="image/png" sizes="32x32" href="{{ $toolBrandingGeneratedIconUrls['favicon_32'] }}">
    @endif
    @if(!empty($toolBrandingGeneratedIconUrls['favicon_16']))
        <link rel="icon" type="image/png" sizes="16x16" href="{{ $toolBrandingGeneratedIconUrls['favicon_16'] }}">
    @endif
    <link rel="icon" href="{{ $toolBrandingFaviconUrl ?: ($toolBrandingGeneratedIconUrls['favicon_32'] ?? null) }}">
    <link rel="shortcut icon" href="{{ $toolBrandingFaviconUrl ?: ($toolBrandingGeneratedIconUrls['favicon_32'] ?? null) }}">
    @if(!empty($toolBrandingGeneratedIconUrls['apple_touch_icon']))
        <link rel="apple-touch-icon" sizes="180x180" href="{{ $toolBrandingGeneratedIconUrls['apple_touch_icon'] }}">
    @elseif(!empty($toolBrandingFaviconUrl))
        <link rel="apple-touch-icon" href="{{ $toolBrandingFaviconUrl }}">
    @endif
    @if(!empty($toolBrandingManifestUrl))
        <link rel="manifest" href="{{ $toolBrandingManifestUrl }}">
    @endif
@else
    @include('partials.theme.favicon-links')
@endif
