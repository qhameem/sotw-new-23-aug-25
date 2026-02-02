@php
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
        "publisher" => ["@id" => url('/') . "/#organization"]
    ];

    $softwareApplicationSchema = [
        "@type" => "SoftwareApplication",
        "name" => $product->name,
        "description" => strip_tags(html_entity_decode($product->description ?? $product->tagline)),
        "applicationCategory" => $product->application_category ?? 'BusinessApplication',
        "operatingSystem" => $product->operating_system ?? 'Web',
        "offers" => [
            "@type" => "AggregateOffer",
            "lowPrice" => (string) ($product->price ?? 0),
            "priceCurrency" => $product->currency ?? 'USD'
        ],
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => (string) ($product->average_rating ?? 5), // Default to 5 if no rating
            "ratingCount" => (string) ($product->votes_count > 0 ? $product->votes_count : 1) // Default to 1 to valid schema
        ]
    ];

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
    $primaryCategory = $product->categories->first();
    if ($primaryCategory) {
        $breadcrumbs[] = [
            "@type" => "ListItem",
            "position" => 2,
            "name" => $primaryCategory->name,
            "item" => route('categories.show', $primaryCategory->slug)
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

    $schema = [
        "@context" => "https://schema.org",
        "@graph" => [
            $organizationSchema,
            $webPageSchema,
            $softwareApplicationSchema,
            $breadcrumbListSchema
        ]
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
</script>