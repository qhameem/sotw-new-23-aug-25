<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;

class ProductStructuredData
{
    public function graph(Product $product, iterable $faqItems = [], ?Category $primaryCategory = null): array
    {
        $site = url('/');
        $canonical = route('products.show', $product->slug);
        $images = $product->seoImageUrls();
        $description = $product->usesProductFacts()
            ? implode(' ', array_filter($product->product_facts, 'is_string'))
            : ($product->description ?: $product->tagline);
        $primaryCategory ??= $product->primaryBreadcrumbCategory();
        $breadcrumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $site]];
        if ($primaryCategory) {
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $primaryCategory->name, 'item' => $primaryCategory->publicUrl()];
        }
        $breadcrumbs[] = ['@type' => 'ListItem', 'position' => count($breadcrumbs) + 1, 'name' => $product->name, 'item' => $canonical];

        $page = [
            '@type' => 'WebPage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $product->name.' on Software on the Web',
            'description' => $this->text($product->product_page_tagline ?: $product->tagline ?: $description),
            'isPartOf' => ['@id' => $site.'/#website'],
            'publisher' => ['@id' => $site.'/#organization'],
            'mainEntity' => ['@id' => $canonical.'#software-application'],
            'breadcrumb' => ['@id' => $canonical.'#breadcrumbs'],
        ];
        $application = [
            '@type' => 'SoftwareApplication',
            '@id' => $canonical.'#software-application',
            'name' => $product->name,
            'description' => $this->text($description),
            'applicationCategory' => $product->facts_json['product_type'] ?? $product->application_category ?? 'BusinessApplication',
            'operatingSystem' => implode(', ', array_filter($product->facts_json['platforms'] ?? [], 'is_string')) ?: ($product->operating_system ?: 'Web'),
            'url' => $canonical,
            'mainEntityOfPage' => ['@id' => $canonical.'#webpage'],
        ];
        if ($images !== []) {
            $application['image'] = $images;
            $page['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $images[0]];
        }
        if ($this->webUrl($product->link)) {
            $application['sameAs'] = [$product->link];
        }
        if ($offer = app(ProductOffer::class)->forProduct($product)) {
            $application['offers'] = $offer;
        }
        if ($product->published_at) {
            $page['datePublished'] = $application['datePublished'] = $product->published_at->toAtomString();
        }
        if ($product->updated_at) {
            $page['dateModified'] = $product->updated_at->toAtomString();
        }

        $graph = [
            app(\App\Support\SiteIdentity::class)->organization(),
            [
                '@type' => 'WebSite', '@id' => $site.'/#website',
                'name' => config('app.name', 'Software on the Web'), 'url' => $site,
                'publisher' => ['@id' => $site.'/#organization'],
            ],
            $page,
            $application,
            ['@type' => 'BreadcrumbList', '@id' => $canonical.'#breadcrumbs', 'itemListElement' => $breadcrumbs],
        ];
        $questions = collect($faqItems)->filter(fn ($item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->map(fn ($item) => [
                '@type' => 'Question', 'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ])->values()->all();
        if ($questions !== []) {
            $graph[2]['hasPart'] = ['@id' => $canonical.'#faq'];
            $graph[] = [
                '@type' => 'FAQPage', '@id' => $canonical.'#faq',
                'url' => $canonical.'#faq', 'isPartOf' => ['@id' => $canonical.'#webpage'],
                'mainEntity' => $questions,
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    private function text(?string $value): string
    {
        $value = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $value ?? '') ?? '';

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function webUrl(?string $value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $value : null;
    }
}
