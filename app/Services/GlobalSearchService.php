<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Support\ProductLogo;
use Illuminate\Support\Facades\Cache;

class GlobalSearchService
{
    public function getPopularProducts(): array
    {
        return Cache::remember('global_search.popular_products.v4', now()->addMinutes(30), function () {
            return Product::query()
                ->select(['id', 'name', 'slug', 'tagline', 'logo', 'link', 'votes_count', 'outbound_clicks_count', 'published_at'])
                ->where('approved', true)
                ->where('is_published', true)
                ->orderByDesc('votes_count')
                ->orderByDesc('outbound_clicks_count')
                ->orderByDesc('published_at')
                ->limit(6)
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'tagline' => $product->tagline,
                    'logo_url' => $product->logo_url,
                    'fallback_logo_url' => ProductLogo::fallbackUrl($product),
                    'votes_count' => (int) $product->votes_count,
                    'url' => route('products.show', ['product' => $product->slug]),
                ])
                ->values()
                ->all();
        });
    }

    public function getPopularCategories(): array
    {
        return Cache::remember('global_search.popular_categories', now()->addMinutes(30), function () {
            return Category::query()
                ->select(['id', 'name', 'slug'])
                ->withCount([
                    'products' => fn ($query) => $query
                        ->where('approved', true)
                        ->where('is_published', true),
                ])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products_count' => (int) $category->products_count,
                    'url' => route('categories.show', ['category' => $category->slug]),
                ])
                ->values()
                ->all();
        });
    }
}
