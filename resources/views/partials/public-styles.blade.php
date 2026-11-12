@php
    $stylesheets = ['resources/css/public.css'];
    $criticalEntry = null;
    $criticalCss = null;

    if (request()->routeIs('home')) {
        $criticalEntry = 'home-critical';
    } elseif (request()->routeIs('products.show')) {
        $criticalEntry = 'product-critical';
        $stylesheets[] = 'resources/css/rich-content.css';
        $stylesheets[] = 'resources/css/product-page.css';
    }

    if ($criticalEntry && ! Vite::isRunningHot()) {
        try {
            $criticalCss = Vite::content("resources/css/{$criticalEntry}.css");
        } catch (\Illuminate\Foundation\ViteException) {
            // A deployment with older assets can still use the regular stylesheets.
        }
    }
@endphp

@if(filled($criticalCss))
    <style id="{{ $criticalEntry }}-css">{!! $criticalCss !!}</style>
    @foreach($stylesheets as $stylesheet)
        <link rel="stylesheet" href="{{ Vite::asset($stylesheet) }}" media="print" onload="this.media='all'">
    @endforeach
    <noscript>
        @vite($stylesheets)
    </noscript>
    @vite('resources/js/app.js')
@else
    @vite([...$stylesheets, 'resources/js/app.js'])
@endif
