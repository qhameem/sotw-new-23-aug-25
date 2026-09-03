<?php

namespace App\Services;

use App\Models\Type;
use Illuminate\Support\Collection;

class ProductFilterNavigationService
{
    public function getTypes(): Collection
    {
        return Type::query()
            ->with(['categories' => function ($query) {
                $query->withCount(['products' => fn ($productQuery) => $productQuery
                    ->where('approved', true)
                    ->where('is_published', true)])
                    ->orderByDesc('products_count')
                    ->orderBy('name');
            }])
            ->orderByRaw("CASE name WHEN 'Software Categories' THEN 1 WHEN 'Use Case' THEN 2 WHEN 'Use Cases' THEN 2 WHEN 'Best for' THEN 3 WHEN 'Platform' THEN 4 WHEN 'Pricing' THEN 5 ELSE 6 END")
            ->orderBy('name')
            ->get();
    }
}
