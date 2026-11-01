<?php

namespace App\Services;

use App\Models\Product;

class ProductComparisonData
{
    public function profile(Product $product): array
    {
        $product->loadMissing('categories.types');
        $editorial = app(ProductEditorialContentService::class)->extract($product);
        $byType = fn (string $type) => $product->categories
            ->filter(fn ($category) => $category->types->contains('name', $type))->pluck('name')->implode(', ');

        return [
            'summary' => $this->text($product->summary ?: $editorial['summary'] ?: $product->tagline),
            'features' => $this->strings($product->features ?: $editorial['key_features']),
            'audience' => $this->strings($product->best_for_text ? [$product->best_for_text] : $editorial['ideal_for']),
            'limitations' => $this->strings($product->not_for_text ? [$product->not_for_text] : $editorial['limitations']),
            'pricing_model' => $byType('Pricing'),
            'price' => app(ProductOffer::class)->label($product),
            'pricing_url' => $this->webUrl($product->pricing_page_url),
            'platforms' => $byType('Platform') ?: $this->text($product->operating_system),
        ];
    }

    public function hasDecisionContent(Product $product): bool
    {
        $profile = $this->profile($product);

        return count($profile['features']) >= 2 && ($profile['audience'] !== [] || $profile['limitations'] !== []);
    }

    private function strings(array $items): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($item) => is_string($item) ? $this->text($item) : '', $items))));
    }

    private function text(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function webUrl(?string $value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $value : null;
    }
}
