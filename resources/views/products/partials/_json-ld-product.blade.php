@php
    $faqEntities = [];

    $descriptionHtml = trim((string) ($product->description ?? ''));
    if ($descriptionHtml !== '' && str_contains($descriptionHtml, '<dl')) {
        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $faqDocument = new DOMDocument('1.0', 'UTF-8');

        $loaded = $faqDocument->loadHTML(
            '<?xml encoding="utf-8" ?><div>' . $descriptionHtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if ($loaded) {
            $faqXPath = new DOMXPath($faqDocument);
            $questionNodes = $faqXPath->query('//dt');

            foreach ($questionNodes as $questionNode) {
                $answerNode = $questionNode->nextSibling;

                while ($answerNode && $answerNode->nodeType !== XML_ELEMENT_NODE) {
                    $answerNode = $answerNode->nextSibling;
                }

                if (!$answerNode || strtolower($answerNode->nodeName) !== 'dd') {
                    continue;
                }

                $questionText = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($questionNode->textContent ?? ''))));
                $answerText = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($answerNode->textContent ?? ''))));

                if ($questionText === '' || $answerText === '') {
                    continue;
                }

                $faqEntities[] = [
                    '@type' => 'Question',
                    'name' => $questionText,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answerText,
                    ],
                ];
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);
    }

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
        "image" => $product->seoImageUrls(),
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

    if ($product->published_at) {
        $softwareApplicationSchema['datePublished'] = $product->published_at->toAtomString();
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
