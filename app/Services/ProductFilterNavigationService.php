<?php

namespace App\Services;

use App\Models\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductFilterNavigationService
{
    public const CACHE_KEY = 'navigation.product_filter_types:v2';

    public function getTypes(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return Type::query()
                ->with(['categories' => function ($query) {
                    $query->with('types:id,name')
                        ->withCount(['products' => fn ($productQuery) => $productQuery
                            ->where('approved', true)
                            ->where('is_published', true)])
                        ->orderByDesc('products_count')
                        ->orderBy('name');
                }])
                ->orderByRaw("CASE name WHEN 'Software Categories' THEN 1 WHEN 'Use Case' THEN 2 WHEN 'Use Cases' THEN 2 WHEN 'Best for' THEN 3 WHEN 'Platform' THEN 4 WHEN 'Pricing' THEN 5 ELSE 6 END")
                ->orderBy('name')
                ->get();
        });
    }
}
