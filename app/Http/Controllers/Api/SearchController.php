<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        return response()->json([
            'query' => $this->extractQuery($request),
            ...$this->buildResults($request),
        ]);
    }

    public function sidebarSearch(Request $request)
    {
        return $this->search($request);
    }

    private function buildResults(Request $request): array
    {
        $query = $this->extractQuery($request);

        if (Str::length($query) < 2) {
            return [
                'products' => [],
                'categories' => [],
            ];
        }

        return [
            'products' => $this->searchProducts($query),
            'categories' => $this->searchCategories($query),
        ];
    }

    private function extractQuery(Request $request): string
    {
        return trim((string) ($request->input('query')
            ?? $request->input('q')
            ?? $request->input('term')
            ?? ''));
    }

    private function searchProducts(string $query)
    {
        $loweredQuery = Str::lower($query);
        $startsWithQuery = $loweredQuery . '%';
        $containsQuery = '%' . $loweredQuery . '%';
        $likeQuery = '%' . $query . '%';
        $fullTextQuery = $this->toBooleanFullTextQuery($query);
        $driver = DB::connection()->getDriverName();

        $products = Product::query()
            ->select(['id', 'name', 'slug', 'tagline', 'logo', 'link', 'votes_count'])
            ->selectRaw(
                'CASE
                    WHEN LOWER(name) = ? THEN 300
                    WHEN LOWER(name) LIKE ? THEN 220
                    WHEN LOWER(tagline) LIKE ? THEN 140
                    WHEN LOWER(description) LIKE ? THEN 80
                    ELSE 0
                END AS relevance_score',
                [$loweredQuery, $startsWithQuery, $containsQuery, $containsQuery]
            )
            ->when(
                $fullTextQuery !== null && in_array($driver, ['mysql', 'mariadb'], true),
                fn ($builder) => $builder->selectRaw(
                    'MATCH(name, tagline, description) AGAINST (? IN BOOLEAN MODE) AS fulltext_score',
                    [$fullTextQuery]
                ),
                fn ($builder) => $builder->selectRaw('0 AS fulltext_score')
            )
            ->where('approved', true)
            ->where('is_published', true)
            ->where(function ($builder) use ($likeQuery, $fullTextQuery, $driver) {
                $builder->where('name', 'like', $likeQuery)
                    ->orWhere('tagline', 'like', $likeQuery)
                    ->orWhere('description', 'like', $likeQuery);

                if ($fullTextQuery !== null && in_array($driver, ['mysql', 'mariadb'], true)) {
                    $builder->orWhereFullText(['name', 'tagline', 'description'], $fullTextQuery, ['mode' => 'boolean']);
                }
            })
            ->orderByDesc('relevance_score')
            ->orderByDesc('fulltext_score')
            ->orderByDesc('votes_count')
            ->limit(6)
            ->get();

        return $products->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'tagline' => $product->tagline,
            'logo_url' => $product->logo_url,
            'url' => route('products.show', ['product' => $product->slug]),
        ])->values();
    }

    private function searchCategories(string $query)
    {
        $loweredQuery = Str::lower($query);
        $startsWithQuery = $loweredQuery . '%';
        $containsQuery = '%' . $loweredQuery . '%';
        $likeQuery = '%' . $query . '%';

        return Category::query()
            ->select(['id', 'name', 'slug'])
            ->selectRaw(
                'CASE
                    WHEN LOWER(name) = ? THEN 220
                    WHEN LOWER(name) LIKE ? THEN 160
                    WHEN LOWER(description) LIKE ? THEN 60
                    ELSE 0
                END AS relevance_score',
                [$loweredQuery, $startsWithQuery, $containsQuery]
            )
            ->where(function ($builder) use ($likeQuery) {
                $builder->where('name', 'like', $likeQuery)
                    ->orWhere('description', 'like', $likeQuery);
            })
            ->orderByDesc('relevance_score')
            ->limit(4)
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => route('categories.show', ['category' => $category->slug]),
            ])
            ->values();
    }

    private function toBooleanFullTextQuery(string $query): ?string
    {
        $terms = collect(preg_split('/\s+/', trim($query)) ?: [])
            ->map(fn (string $term) => trim($term))
            ->filter(fn (string $term) => Str::length($term) >= 2)
            ->take(5)
            ->map(fn (string $term) => '+' . $term . '*')
            ->values();

        return $terms->isEmpty() ? null : $terms->implode(' ');
    }
}
