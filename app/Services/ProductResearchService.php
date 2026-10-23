<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Product;
use DOMDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductResearchService
{
    public function __construct(private readonly RelatedProductService $relatedProducts) {}

    public function directoryQuery(): Builder
    {
        return $this->publicProducts()
            ->where(function (Builder $query) {
                $query->whereJsonLength('alternative_product_ids', '>=', 2)
                    ->orWhereJsonLength('comparison_product_ids', '>=', 1)
                    ->orWhereHas('categories', fn (Builder $categories) => $categories
                        ->whereHas('types', fn (Builder $types) => $types->whereIn('name', ['Software Categories', 'Software', 'Category']))
                        ->whereHas('products', fn (Builder $products) => $products->approvedAndPublished(), '>=', 3));
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
                    'url' => route('pseo.compare', ['params' => $product->slug.'-vs-'.$comparison->slug]),
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
