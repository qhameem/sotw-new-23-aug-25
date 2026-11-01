<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use DOMDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductResearchService
{
    public function __construct(private readonly RelatedProductService $relatedProducts) {}

    public function preloadResearchMatches(iterable $products): void
    {
        $this->relatedProducts->preloadResearchMatches($products);
    }

    public function directoryQuery(): Builder
    {
        $populatedCategoryIds = DB::table('category_product')
            ->select('category_product.category_id')
            ->join('products as peers', 'peers.id', '=', 'category_product.product_id')
            ->where('peers.approved', true)
            ->where('peers.is_published', true)
            ->groupBy('category_product.category_id')
            ->havingRaw('COUNT(*) >= 3');
        $softwareCategoryIds = Category::query()->select('categories.id')
            ->whereHas('types', fn (Builder $types) => $types->whereIn('name', ['Software Categories', 'Software', 'Category']))
            ->whereIn('categories.id', $populatedCategoryIds);
        $candidateProductIds = DB::table('category_product')->select('product_id')
            ->whereIn('category_id', $softwareCategoryIds);

        return $this->publicProducts()
            ->where(function (Builder $query) use ($candidateProductIds) {
                $query->whereJsonLength('alternative_product_ids', '>=', 2)
                    ->orWhereJsonLength('comparison_product_ids', '>=', 1)
                    ->orWhereIn('products.id', $candidateProductIds);
            })
            ->with(['categories.types', 'techStacks'])
            ->orderBy('name')
            ->orderBy('id');
    }

    public function forProducts(iterable $products, int $limit = 6, bool $includeAlternatives = true): Collection
    {
        $links = collect();

        foreach (collect($products)->take(6) as $product) {
            if (! $product->approved || ! $product->is_published || $product->generation_noindex) {
                continue;
            }

            $alternatives = $includeAlternatives ? $this->relatedProducts->getAlternatives($product, 15) : collect();
            $alternatives = $alternatives->where('id', '!=', $product->id)->values();
            $comparisons = $this->relatedProducts->getComparisons($product, 2)->where('id', '!=', $product->id)->values();
            $publicIds = $this->publicProducts()
                ->whereKey($alternatives->merge($comparisons)->pluck('id')->unique())
                ->pluck('id');
            $alternatives = $alternatives->whereIn('id', $publicIds)->values();

            if ($includeAlternatives && ! $this->relatedProducts->shouldNoindexAlternatives($product, $alternatives)) {
                $links->push([
                    'label' => $product->name.' alternatives',
                    'url' => route('pseo.alternatives', $product->slug),
                ]);
            }

            foreach ($comparisons->whereIn('id', $publicIds) as $comparison) {
                if ($this->relatedProducts->shouldNoindexComparison($product, $comparison)) {
                    continue;
                }

                $links->push([
                    'label' => $product->name.' vs '.$comparison->name,
                    'url' => \App\Support\ComparisonUrl::forSlugs($product->slug, $comparison->slug),
                ]);
            }

            if ($links->count() >= $limit) {
                break;
            }
        }

        return $links->unique('url')->take($limit)->values();
    }

    public function articleProducts(Article $article): Collection
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$article->content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $slugs = collect();

        foreach ($document->getElementsByTagName('a') as $anchor) {
            $url = parse_url($anchor->getAttribute('href'));

            if ($url === false || (isset($url['scheme']) && ! in_array(strtolower($url['scheme']), ['http', 'https'], true))
                || (isset($url['host']) && ! in_array(strtolower($url['host']), [
                    strtolower((string) parse_url(config('app.url'), PHP_URL_HOST)),
                    'softwareontheweb.com',
                    'www.softwareontheweb.com',
                ], true))) {
                continue;
            }

            if (preg_match('~^/(?:product|alternatives)/([^/]+)/?$~', $url['path'] ?? '', $match)) {
                $slugs->push(rawurldecode($match[1]));
            }
        }

        $slugs = $slugs->unique()->take(6)->values();

        return $this->publicProducts()->whereIn('slug', $slugs)->get()
            ->sortBy(fn (Product $product) => $slugs->search($product->slug))->values();
    }

    private function publicProducts(): Builder
    {
        return Product::query()->approvedAndPublished()
            ->where(fn (Builder $query) => $query->where('generation_noindex', false)->orWhereNull('generation_noindex'));
    }
}
