@php
    $criticalCss = null;

    if (request()->routeIs('home') && ! Vite::isRunningHot()) {
        try {
            $criticalCss = Vite::content('resources/css/home-critical.css');
        } catch (\Illuminate\Foundation\ViteException) {
            // A deployment with older assets can still use the regular stylesheet.
        }
    }
@endphp

@if(filled($criticalCss))
    <style id="home-critical-css">{!! $criticalCss !!}</style>
    <link rel="stylesheet" href="{{ Vite::asset('resources/css/public.css') }}" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="{{ Vite::asset('resources/css/public.css') }}"></noscript>
    @vite('resources/js/app.js')
@else
    @vite(['resources/css/public.css', 'resources/js/app.js'])
@endif
