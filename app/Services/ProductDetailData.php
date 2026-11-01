<?php

namespace App\Services;

use App\Models\Product;

class ProductDetailData
{
    public function rows(Product $product, $bestForCategories, $platformCategories, $useCaseCategories): array
    {
        $priceLabel = app(ProductOffer::class)->label($product);
        $pricingCategories = $product->categories->filter(fn ($category) => $category->types->contains('name', 'Pricing'));
        $rows = [
            'Pricing' => $pricingCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()])->values()
                ->when($priceLabel, fn ($items) => $items->push(['label' => $priceLabel, 'url' => null])),
            'Built for' => $bestForCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()]),
            'Platforms' => $platformCategories->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()]),
            'Categories' => $product->categories->diff($bestForCategories)->diff($platformCategories)->diff($useCaseCategories)->diff($pricingCategories)
                ->map(fn ($category) => ['label' => $category->name, 'url' => $category->publicUrl()]),
            'Built with' => $product->techStacks->map(fn ($tech) => ['label' => $tech->name, 'url' => route('pseo.builtWith', $tech->slug)]),
        ];
        if ($rows['Platforms']->isEmpty()) {
            $rows['Platforms'] = collect($product->facts_json['platforms'] ?? [])
                ->filter(fn ($platform) => is_string($platform) && filled($platform))
                ->map(fn ($platform) => ['label' => $platform, 'url' => null]);
            if ($rows['Platforms']->isEmpty() && filled($product->operating_system)) {
                $rows['Platforms']->push(['label' => $product->operating_system, 'url' => null]);
            }
        }
        $type = $product->facts_json['product_type'] ?? $product->application_category;
        if ($rows['Categories']->isEmpty() && filled($type)) {
            $rows['Categories']->push(['label' => $type, 'url' => null]);
        }

        return $rows;
    }
}
