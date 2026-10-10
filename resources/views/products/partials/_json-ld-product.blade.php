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
        "applicationCategory" => $product->facts_json['product_type'] ?? $product->application_category ?? 'BusinessApplication',
        "operatingSystem" => implode(', ', $product->facts_json['platforms'] ?? []) ?: ($product->operating_system ?? 'Web'),
        "image" => $product->seoImageUrls(),
        "url" => route('products.show', $product->slug),
    ];

    if (filled($product->facts_json['version'] ?? null)) {
        $softwareApplicationSchema['softwareVersion'] = $product->facts_json['version'];
    }
    if (filled($product->facts_json['last_updated'] ?? null)) {
        $softwareApplicationSchema['dateModified'] = $product->facts_json['last_updated'];
    }

    if (filled($product->link)) {
        $softwareApplicationSchema['sameAs'] = [$product->link];
    }

    $schemaPrice = $product->facts_json['price_amount'] ?? $product->price;
    $schemaCurrency = $product->facts_json['currency'] ?? $product->currency;
    if (is_numeric($schemaPrice) && (float) $schemaPrice > 0 && filled($schemaCurrency)) {
        $softwareApplicationSchema['offers'] = array_filter([
            '@type' => 'Offer',
            'price' => number_format((float) $schemaPrice, 2, '.', ''),
            'priceCurrency' => strtoupper((string) $schemaCurrency),
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
            "item" => $primaryBreadcrumbCategory->publicUrl()
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
