<?php

namespace App\Services;

use App\Models\Product;

class PublicProductDocument
{
    public function data(Product $product): array
    {
        $canonical = route('products.show', $product->slug);
        $description = $product->usesProductFacts()
            ? implode("\n\n", array_filter($product->product_facts, 'is_string'))
            : ($product->description ?? '');

        return [
            'schema_version' => 1,
            'name' => $this->plain($product->name),
            'slug' => $product->slug,
            'url' => $canonical,
            'website' => $this->webUrl($product->link),
            'tagline' => $this->plain($product->product_page_tagline ?: $product->tagline),
            'description_markdown' => app(ProductPageMarkdown::class)->convert($description, $canonical),
            'categories' => $product->categories->map(fn ($category) => $this->plain($category->name))->values()->all(),
            'published_at' => $product->published_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }

    public function markdown(array $data): string
    {
        $lines = ['# '.$this->escape($data['name']), '', $this->escape($data['tagline']), '', 'Canonical: '.$data['url']];
        if ($data['website']) {
            $lines[] = 'Website: '.$data['website'];
        }
        if ($data['categories']) {
            $lines[] = 'Categories: '.$this->escape(implode(', ', $data['categories']));
        }
        $lines[] = 'Updated: '.($data['updated_at'] ?? 'Unknown');
        $lines[] = '';
        $lines[] = $data['description_markdown'];

        return implode("\n", $lines)."\n";
    }

    private function plain(?string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    }

    private function escape(string $text): string
    {
        return preg_replace('/([\\\\`*_{}\[\]<>#!|])/', '\\\\$1', $text) ?? $text;
    }

    private function webUrl(?string $url): ?string
    {
        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $url : null;
    }
}
