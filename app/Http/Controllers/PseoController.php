<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\TechStack;
use App\Services\ProductEditorialContentService;
use App\Services\RelatedProductService;

class PseoController extends Controller
{
    public function __construct(
        private readonly RelatedProductService $relatedProductService,
        private readonly ProductEditorialContentService $productEditorialContentService
    ) {}

    /**
     * /best/{category:slug}
     * "Best {Category} Software in {year}"
     */
    public function bestOf(Category $category)
    {
        $category->loadMissing('types');
        abort_unless($this->hasSoftwareType($category), 404);

        $products = $category->products()
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'user', 'techStacks'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        $year = now()->year;
        $title = "Best {$category->name} Software in {$year}";
        $metaDescription = "Discover the top {$category->name} tools ranked by the community in {$year}. "
            . ($category->meta_description ?: "Compare features, pricing, and more.");

        return view('pseo.best-of', compact('category', 'products', 'title', 'metaDescription', 'year'));
    }

    /**
     * /alternatives/{product:slug}
     * "Best {Product} Alternatives in {year}"
     */
    public function alternatives(Product $product)
    {
        abort_unless($product->approved && $product->is_published, 404);
        $product->loadMissing('categories.types', 'user', 'techStacks');
        $productEditorial = $this->productEditorialContentService->extract($product);

        $alternatives = $this->relatedProductService->getAlternatives($product, 15)
            ->map(fn(Product $alternative) => $this->decorateAlternative($product, $productEditorial, $alternative))
            ->values();
        $shouldNoindex = $this->relatedProductService->shouldNoindexAlternatives($product, $alternatives);

        $year = now()->year;
        $title = "Best {$product->name} Alternatives in {$year}";
        $topAlternativeNames = $alternatives->take(3)->pluck('name')->implode(', ');
        $productCategory = $this->softwareCategoryNames($product)[0] ?? 'software';
        $metaDescription = "Looking for {$product->name} alternatives? "
            . ($topAlternativeNames !== ''
                ? "Compare {$topAlternativeNames}, plus other {$productCategory} tools similar to {$product->name} in {$year}."
                : "Browse tools similar to {$product->name} in {$year}.");
        $intro = $this->buildAlternativesIntro($product, $alternatives);
        $faqItems = $this->buildAlternativeFaqItems($product, $alternatives);

        return view('pseo.alternatives', compact(
            'product',
            'alternatives',
            'title',
            'metaDescription',
            'year',
            'shouldNoindex',
            'intro',
            'faqItems',
            'productEditorial'
        ));
    }

    /**
     * /built-with/{techstack:slug}
     * "Software Built with {TechStack}"
     */
    public function builtWith(TechStack $techstack)
    {
        $products = $techstack->products()
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'user'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        $title = "Software Built with {$techstack->name}";
        $metaDescription = "Browse {$products->count()} tools and SaaS products built with {$techstack->name}, ranked by the community.";

        return view('pseo.built-with', compact('techstack', 'products', 'title', 'metaDescription'));
    }

    /**
     * /best/{category:slug}/for/{bestfor:slug}
     * "Best {Category} Software for {BestFor}"
     */
    public function bestFor(Category $category, Category $bestfor)
    {
        $category->loadMissing('types');
        $bestfor->loadMissing('types');

        abort_unless($this->hasSoftwareType($category), 404);
        abort_unless($bestfor->types->contains('name', 'Best for'), 404);

        $products = Product::where('approved', true)
            ->where('is_published', true)
            ->whereHas('categories', fn($q) => $q->where('categories.id', $category->id))
            ->whereHas('categories', fn($q) => $q->where('categories.id', $bestfor->id))
            ->with(['categories.types', 'user'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        abort_if($products->isEmpty(), 404);

        $year = now()->year;
        $title = "Best {$category->name} Software for {$bestfor->name} in {$year}";
        $metaDescription = "Top {$category->name} tools for {$bestfor->name}, ranked by community votes in {$year}.";

        return view('pseo.best-for', compact('category', 'bestfor', 'products', 'title', 'metaDescription', 'year'));
    }

    /**
     * /compare/{slugA}-vs-{slugB}
     * "{ProductA} vs {ProductB}: Which is Better?"
     */
    public function compare($params)
    {
        // Split on the FIRST occurrence of '-vs-' so slugs with hyphens work
        $pos = strpos($params, '-vs-');
        if ($pos === false) {
            abort(404);
        }
        $slugA = substr($params, 0, $pos);
        $slugB = substr($params, $pos + 4); // 4 = strlen('-vs-')


        $productA = Product::where('slug', $slugA)
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'techStacks', 'user'])
            ->firstOrFail();

        $productB = Product::where('slug', $slugB)
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'techStacks', 'user'])
            ->firstOrFail();

        $pairMatch = $this->relatedProductService->scorePair($productA, $productB);
        $isCuratedPair = $this->relatedProductService->isCuratedComparisonPair($productA, $productB);
        $pairMatchSummary = $isCuratedPair
            ? 'This comparison is manually curated by the editorial team.'
            : $pairMatch['summary'];
        $shouldNoindex = !$isCuratedPair && !$pairMatch['qualifiesComparison'];

        $title = "{$productA->name} vs {$productB->name}: Which is Better?";
        $metaDescription = "Compare {$productA->name} and {$productB->name} side-by-side — features, pricing, tech stack, and community votes.";

        return view('pseo.compare', compact('productA', 'productB', 'title', 'metaDescription', 'pairMatchSummary', 'shouldNoindex'));
    }

    /**
     * /software/{pricing:slug}
     * "{Pricing} Software Tools"
     */
    public function pricingModel(Category $pricing)
    {
        $pricing->loadMissing('types');
        abort_unless($pricing->types->contains('name', 'Pricing'), 404);

        $products = $pricing->products()
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'user'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        $title = "{$pricing->name} Software Tools";
        $metaDescription = "Browse {$products->count()} {$pricing->name} software tools, ranked by the community.";

        return view('pseo.pricing-model', compact('pricing', 'products', 'title', 'metaDescription'));
    }

    private function hasSoftwareType(Category $category): bool
    {
        $typeNames = $category->types->pluck('name')->map(fn($name) => strtolower((string) $name));

        return $typeNames->contains('software')
            || $typeNames->contains('software categories')
            || $typeNames->contains('category');
    }

    private function decorateAlternative(Product $source, array $sourceEditorial, Product $alternative): Product
    {
        $softwareCategories = $this->softwareCategoryNames($alternative);
        $bestForCategories = $this->categoryNamesByType($alternative, 'Best for');
        $pricingCategories = $this->categoryNamesByType($alternative, 'Pricing');
        $editorial = $this->productEditorialContentService->extract($alternative);

        $alternative->setAttribute('primary_category_label', $softwareCategories[0] ?? null);
        $alternative->setAttribute('best_for_label', !empty($bestForCategories) ? implode(', ', array_slice($bestForCategories, 0, 2)) : null);
        $alternative->setAttribute('pricing_label', !empty($pricingCategories) ? implode(', ', array_slice($pricingCategories, 0, 2)) : 'Pricing not listed');
        $alternative->setAttribute('editorial_headline', $editorial['headline'] ?? null);
        $alternative->setAttribute('feature_highlights', array_slice($editorial['key_features'] ?? [], 0, 3));
        $alternative->setAttribute('pros_points', array_slice($editorial['pros'] ?? [], 0, 2));
        $alternative->setAttribute('limitations_points', array_slice($editorial['limitations'] ?? [], 0, 2));
        $alternative->setAttribute('ideal_for_points', array_slice($editorial['ideal_for'] ?? [], 0, 2));
        $alternative->setAttribute('top_use_case_points', array_slice($editorial['top_use_cases'] ?? [], 0, 2));
        $alternative->setAttribute('editorial_take', $this->buildEditorialTake($source, $alternative, $editorial));
        $alternative->setAttribute('better_for_text', $this->buildBetterForText($editorial, $softwareCategories, $bestForCategories));
        $alternative->setAttribute('watch_out_text', $this->buildWatchOutText($source, $sourceEditorial, $editorial));
        $alternative->setAttribute('decision_summary', $this->buildDecisionSummary(
            $source,
            $alternative,
            $softwareCategories,
            $bestForCategories,
            $pricingCategories
        ));

        return $alternative;
    }

    private function buildAlternativesIntro(Product $product, $alternatives): string
    {
        $productCategory = $this->softwareCategoryNames($product)[0] ?? 'software';
        $topNames = $alternatives->take(3)->pluck('name')->filter()->implode(', ');

        if ($topNames !== '') {
            return "{$product->name} is a {$productCategory} product, but it will not fit every workflow. "
                . "If you are comparing options, start with {$topNames}. This page focuses on tools that overlap with {$product->name} in category, audience, pricing style, or technical profile.";
        }

        return "This page tracks alternatives to {$product->name} and highlights the closest matches by category, audience, pricing style, and technical profile.";
    }

    private function buildAlternativeFaqItems(Product $product, $alternatives): array
    {
        $topAlternatives = $alternatives->take(3);
        $topNames = $topAlternatives->pluck('name')->filter()->implode(', ');
        $audiences = $topAlternatives->pluck('best_for_label')->filter()->unique()->take(2)->implode(' and ');

        return array_values(array_filter([
            [
                'question' => "What are the best {$product->name} alternatives?",
                'answer' => $topNames !== ''
                    ? "The strongest options on this page are {$topNames}. They rank highly because they overlap with {$product->name} in category fit, audience, pricing signals, technical profile, and editorial product details like features, use cases, and tradeoffs."
                    : "The page updates as new products are added, so the best alternatives list will improve as the directory grows.",
            ],
            [
                'question' => "Why would someone choose an alternative to {$product->name}?",
                'answer' => $audiences !== ''
                    ? "Most readers switch when they need a better fit for {$audiences}, a different pricing model, or a product with a stronger match for their workflow."
                    : "Most readers switch when they need a different pricing model, a different feature focus, or a product that fits their workflow better.",
            ],
            [
                'question' => "How are {$product->name} alternatives ranked on this page?",
                'answer' => "Alternatives are ranked using shared software categories, audience overlap, pricing model overlap, technical overlap, product-positioning similarity, community activity, and structured editorial signals pulled from product descriptions. Some entries may also be manually curated by editors.",
            ],
        ]));
    }

    private function buildEditorialTake(Product $source, Product $alternative, array $editorial): ?string
    {
        $feature = $editorial['key_features'][0] ?? null;
        $useCase = $editorial['top_use_cases'][0] ?? null;
        $headline = $editorial['headline'] ?? null;

        if ($feature && $useCase) {
            return "{$alternative->name} stands out for {$feature}. It looks strongest if you care about {$useCase}.";
        }

        if ($feature) {
            return "{$alternative->name} stands out for {$feature}.";
        }

        if ($headline) {
            return $headline;
        }

        return (string) ($alternative->decision_summary ?: "A relevant option if you are evaluating tools similar to {$source->name}.");
    }

    private function buildBetterForText(array $editorial, array $softwareCategories, array $bestForCategories): ?string
    {
        $idealFor = $editorial['ideal_for'][0] ?? null;

        if ($idealFor) {
            return $idealFor;
        }

        $useCase = $editorial['top_use_cases'][0] ?? null;

        if ($useCase) {
            return $useCase;
        }

        if (!empty($bestForCategories)) {
            return implode(', ', array_slice($bestForCategories, 0, 2));
        }

        return $softwareCategories[0] ?? null;
    }

    private function buildWatchOutText(Product $source, array $sourceEditorial, array $editorial): ?string
    {
        $limitation = $editorial['limitations'][0] ?? null;

        if ($limitation) {
            return $limitation;
        }

        $sourceStrength = $sourceEditorial['key_features'][0] ?? null;

        if ($sourceStrength) {
            return "Compare it carefully against {$source->name} if {$sourceStrength} is the main reason you are considering staying put.";
        }

        return null;
    }

    private function buildDecisionSummary(
        Product $source,
        Product $alternative,
        array $softwareCategories,
        array $bestForCategories,
        array $pricingCategories
    ): string {
        $reasonLabels = collect($alternative->match_reason_labels ?? []);
        $softwareLabel = $softwareCategories[0] ?? 'similar';
        $bestForLabel = implode(', ', array_slice($bestForCategories, 0, 2));
        $pricingLabel = implode(', ', array_slice($pricingCategories, 0, 2));

        if (($alternative->match_source ?? null) === 'manual') {
            return "Editor-picked as one of the closest alternatives to {$source->name}.";
        }

        if ($bestForLabel !== '' && $reasonLabels->contains('same target audience')) {
            return "Consider {$alternative->name} if you need a {$softwareLabel} tool for {$bestForLabel}.";
        }

        if ($pricingLabel !== '' && $pricingLabel !== 'Pricing not listed' && $reasonLabels->contains('similar pricing model')) {
            return "Worth a look if you want a {$softwareLabel} option with {$pricingLabel} pricing.";
        }

        if ($reasonLabels->contains('overlapping tech stack')) {
            return "A sensible pick if technical overlap with {$source->name} matters to your stack.";
        }

        if ($softwareLabel !== 'similar') {
            return "A strong like-for-like choice if you want another {$softwareLabel} product.";
        }

        return (string) ($alternative->match_summary ?: "A relevant option if you are evaluating tools similar to {$source->name}.");
    }

    private function softwareCategoryNames(Product $product): array
    {
        return $product->categories
            ->filter(function ($category) {
                $typeNames = $category->types->pluck('name')->map(fn($name) => strtolower((string) $name));

                if ($typeNames->isEmpty()) {
                    return true;
                }

                if ($typeNames->contains('pricing') || $typeNames->contains('best for')) {
                    return false;
                }

                return $typeNames->contains('software')
                    || $typeNames->contains('software categories')
                    || $typeNames->contains('category');
            })
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    private function categoryNamesByType(Product $product, string $typeName): array
    {
        return $product->categories
            ->filter(fn($category) => $category->types->contains('name', $typeName))
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }
}
