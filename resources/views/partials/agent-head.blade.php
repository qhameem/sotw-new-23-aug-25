@php
    $agentRegistry = app(\App\Services\Markdown\Registry::class);
    $agentPath = request()->getPathInfo();
    $agentRobots = strtolower(trim($__env->yieldContent('robots', 'index, follow')));
    $agentPublic = $agentRegistry->supports($agentPath)
        && !str_contains($agentRobots, 'noindex')
        && (!isset($product) || app(\App\Services\Markdown\Renderers\Content::class)->visible($product))
        && (!isset($collection) || $collection->isPublic());
    $agentSummary = trim($__env->yieldContent('meta_description', $metaDescription ?? $meta_description ?? $pageDescription ?? ''));
    if ($agentSummary === '') {
        $agentSummary = $agentRegistry->definitions()[request()->route()?->getName()][2] ?? app(\App\Support\SiteIdentity::class)->description();
    }
@endphp
@if($agentPublic)
    <meta name="ai-summary" content="{{ app(\App\Services\Markdown\Format::class)->text($agentSummary) }}">
    <link rel="alternate" type="text/markdown" href="{{ $agentRegistry->markdownUrl($agentPath.(request()->getQueryString() ? '?'.request()->getQueryString() : '')) }}">
@endif
@if(request()->routeIs('home'))
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', ...app(\App\Support\SiteIdentity::class)->organization()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
