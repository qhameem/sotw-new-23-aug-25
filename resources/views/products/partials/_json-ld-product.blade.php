@php
    $faqEntities = collect($productFaqItems ?? [])
        ->map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['answer'],
            ],
        ])
        ->values()
        ->all();

    $organizationSchema = [
        "@type" => "Organization",
        "@id" => url('/') . "/#organization",
        "name" => "Software on the Web",
        "url" => url('/'),
        "logo" => asset('images/logo.png') // Adjust path as needed
    ];

    $webPageSchema = [
        "@type" => "WebPage",
        "@id" => route('products.show', $product->slug) . "#webpage",
        "url" => route('products.show', $product->slug),
        "name" => $product->name . " on Software on the Web",
        "description" => strip_tags((string) ($product->product_page_tagline ?: $product->tagline ?: $product->description)),
        "publisher" => ["@id" => url('/') . "/#organization"],
        "mainEntity" => ["@id" => route('products.show', $product->slug) . "#software-application"],
    ];

    $softwareApplicationSchema = [
        "@type" => "SoftwareApplication",
        "@id" => route('products.show', $product->slug) . "#software-application",
        "name" => $product->name,
        "description" => $product->usesProductFacts()
            ? implode(' ', $product->product_facts)
            : strip_tags(html_entity_decode($product->description ?? $product->tagline)),
        "applicationCategory" => $product->application_category ?? 'BusinessApplication',
        "operatingSystem" => $product->operating_system ?? 'Web',
        "image" => $product->seoImageUrls(),
        "url" => route('products.show', $product->slug),
    ];

    if (filled($product->link)) {
        $softwareApplicationSchema['sameAs'] = [$product->link];
    }

    if (is_numeric($product->price) && (float) $product->price > 0 && filled($product->currency)) {
        $softwareApplicationSchema['offers'] = array_filter([
            '@type' => 'Offer',
            'price' => number_format((float) $product->price, 2, '.', ''),
            'priceCurrency' => strtoupper((string) $product->currency),
            'url' => $product->pricing_page_url ?: $product->link,
        ], fn ($value) => filled($value));
    }

    if ($product->published_at) {
        $softwareApplicationSchema['datePublished'] = $product->published_at->toAtomString();
    }

    if ($product->updated_at) {
        $webPageSchema['dateModified'] = $product->updated_at->toAtomString();
    }

    // Breadcrumbs
    $breadcrumbs = [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Home",
            "item" => url('/')
        ]
    ];

    // Add primary category to breadcrumb if available
    $primaryBreadcrumbCategory = $primaryBreadcrumbCategory ?? $product->primaryBreadcrumbCategory();
    if ($primaryBreadcrumbCategory) {
        $breadcrumbs[] = [
            "@type" => "ListItem",
            "position" => 2,
            "name" => $primaryBreadcrumbCategory->name,
            "item" => route('categories.show', $primaryBreadcrumbCategory->slug)
        ];
        $productPosition = 3;
    } else {
        $productPosition = 2;
    }

    $breadcrumbs[] = [
        "@type" => "ListItem",
        "position" => $productPosition,
        "name" => $product->name,
        "item" => route('products.show', $product->slug)
    ];

    $breadcrumbListSchema = [
        "@type" => "BreadcrumbList",
        "itemListElement" => $breadcrumbs
    ];

    $graph = [
        $organizationSchema,
        $webPageSchema,
        $softwareApplicationSchema,
        $breadcrumbListSchema
    ];

    if (!empty($faqEntities)) {
        $graph[] = [
            '@type' => 'FAQPage',
            'mainEntity' => $faqEntities,
        ];
    }

    $schema = [
        "@context" => "https://schema.org",
        "@graph" => $graph
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
</script>
