@php
    $schema = app(\App\Services\ProductStructuredData::class)->graph(
        $product,
        $productFaqItems ?? [],
        $primaryBreadcrumbCategory ?? null,
    );
@endphp
<script type="application/ld+json">
    {!! json_encode($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) !!}
</script>
