<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelatedProductService
{
    /**
     * Broad categories are useful for discovery but weak for direct comparisons.
     */
    private const BROAD_CATEGORY_NAMES = [
        'ai & machine learning',
        'developer tools',
        'design',
        'marketing',
        'productivity',
        'saas',
    ];

    /**
     * Backward-compatible handling while taxonomy naming is being normalized.
     */
    private const SOFTWARE_TYPE_NAMES = ['software categories', 'software', 'category'];

    public function getComparisons(Product $product, int $limit = 2): Collection
    {
        return $this->getRelatedProducts($product, $limit, 'comparison');
    }

    public function getAlternatives(Product $product, int $limit = 15): Collection
    {
        return $this->getRelatedProducts($product, $limit, 'alternative');
    }

    public function shouldNoindexAlternatives(Product $product, ?Collection $alternatives = null): bool
    {
        $alternatives ??= $this->getAlternatives($product, 15);

        $hasManualAlternatives = !empty($product->alternative_product_ids ?? []);
        $topScore = (int) ($alternatives->max('match_score') ?? 0);

        return $alternatives->count() < 3 || (!$hasManualAlternatives && $topScore < 55);
    }

    public function scorePair(Product $productA, Product $productB): array
    {
        $productA->loadMissing('categories.types', 'techStacks');
        $productB->loadMissing('categories.types', 'techStacks');

        return $this->calculateMatch($productA, $productB);
    }

    public function isCuratedComparisonPair(Product $productA, Product $productB): bool
    {
        $a = collect($productA->comparison_product_ids ?? [])->map(fn($id) => (int) $id);
        $b = collect($productB->comparison_product_ids ?? [])->map(fn($id) => (int) $id);

        return $a->contains((int) $productB->id) || $b->contains((int) $productA->id);
    }

    private function getRelatedProducts(Product $product, int $limit, string $mode): Collection
    {
        $product->loadMissing('categories.types', 'techStacks');

        $manualProducts = $this->getManualMatches($product, $mode);
        $manualIds = $manualProducts->pluck('id');
        $remainingLimit = max(0, $limit - $manualProducts->count());

        if ($remainingLimit === 0) {
            return $manualProducts->take($limit);
        }

        $profile = $this->buildProfile($product);
        $seedCategoryIds = collect()
            ->merge($profile['softwareIds'])
            ->merge($profile['bestForIds'])
            ->merge($profile['pricingIds'])
            ->unique()
            ->values();
        $seedTechIds = collect($profile['techStackIds'])->unique()->values();

        if ($seedCategoryIds->isEmpty() && $seedTechIds->isEmpty()) {
            return $manualProducts->take($limit)->values();
        }

        $candidates = Product::query()
            ->where('id', '!=', $product->id)
            ->where('approved', true)
            ->where('is_published', true)
            ->whereNotIn('id', $manualIds)
            ->where(function ($query) use ($seedCategoryIds, $seedTechIds) {
                if ($seedCategoryIds->isNotEmpty()) {
                    $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $seedCategoryIds));
                }
                if ($seedTechIds->isNotEmpty()) {
                    if ($seedCategoryIds->isNotEmpty()) {
                        $query->orWhereHas('techStacks', fn($q) => $q->whereIn('tech_stacks.id', $seedTechIds));
                    } else {
                        $query->whereHas('techStacks', fn($q) => $q->whereIn('tech_stacks.id', $seedTechIds));
                    }
                }
            })
            ->with(['categories.types', 'techStacks'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->orderByDesc('created_at')
            ->take(250)
            ->get()
            ->map(function (Product $candidate) use ($product) {
                $match = $this->calculateMatch($product, $candidate);
                $candidate->setAttribute('match_score', $match['score']);
                $candidate->setAttribute('match_summary', $match['summary']);
                $candidate->setAttribute('match_reason_labels', $match['reasonLabels']);
                $candidate->setAttribute('match_source', 'algorithmic');

                return ['product' => $candidate, 'match' => $match];
            })
            ->filter(function (array $entry) use ($mode) {
                return $mode === 'comparison'
                    ? $entry['match']['qualifiesComparison']
                    : $entry['match']['qualifiesAlternative'];
            })
            ->sortByDesc(function (array $entry) {
                /** @var Product $product */
                $product = $entry['product'];
                $score = $entry['match']['score'];
                $popularity = (int) ($product->votes_count ?? 0) + (int) ($product->impressions ?? 0);

                return ($score * 100000) + $popularity;
            })
            ->pluck('product')
            ->values()
            ->take($remainingLimit);

        return $manualProducts
            ->concat($candidates)
            ->unique('id')
            ->take($limit)
            ->values();
    }

    private function getManualMatches(Product $product, string $mode): Collection
    {
        $field = $mode === 'comparison' ? 'comparison_product_ids' : 'alternative_product_ids';
        $ids = collect($product->{$field} ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $productsById = Product::query()
            ->where('approved', true)
            ->where('is_published', true)
            ->whereIn('id', $ids)
            ->with(['categories.types', 'techStacks'])
            ->get()
            ->keyBy('id');

        $manualLabel = $mode === 'comparison'
            ? 'Curated comparison selected by editor.'
            : 'Curated alternative selected by editor.';

        return $ids
            ->map(function ($id, $index) use ($productsById, $manualLabel) {
                /** @var Product|null $product */
                $product = $productsById->get($id);
                if (!$product) {
                    return null;
                }

                $product->setAttribute('match_score', 1000 - $index);
                $product->setAttribute('match_summary', $manualLabel);
                $product->setAttribute('match_reason_labels', ['curated']);
                $product->setAttribute('match_source', 'manual');

                return $product;
            })
            ->filter()
            ->values();
    }

    private function calculateMatch(Product $source, Product $candidate): array
    {
        $sourceProfile = $this->buildProfile($source);
        $candidateProfile = $this->buildProfile($candidate);

        $sharedSoftwareIds = array_values(array_intersect($sourceProfile['softwareIds'], $candidateProfile['softwareIds']));
        $sharedBestForIds = array_values(array_intersect($sourceProfile['bestForIds'], $candidateProfile['bestForIds']));
        $sharedPricingIds = array_values(array_intersect($sourceProfile['pricingIds'], $candidateProfile['pricingIds']));
        $sharedTechIds = array_values(array_intersect($sourceProfile['techStackIds'], $candidateProfile['techStackIds']));

        $sharedBroadSoftwareCount = collect($sharedSoftwareIds)
            ->map(fn($id) => strtolower($sourceProfile['softwareNameMap'][$id] ?? ''))
            ->filter(fn($name) => in_array($name, self::BROAD_CATEGORY_NAMES, true))
            ->count();

        $textSimilarity = $this->jaccardSimilarity($sourceProfile['textTokens'], $candidateProfile['textTokens']);

        $score = 0;
        $reasonLabels = [];

        $sharedSoftwareCount = count($sharedSoftwareIds);
        if ($sharedSoftwareCount > 0) {
            $score += min(70, 42 + (($sharedSoftwareCount - 1) * 16));
            $reasonLabels[] = 'shared software category';
        }

        $sharedBestForCount = count($sharedBestForIds);
        if ($sharedBestForCount > 0) {
            $score += min(20, $sharedBestForCount * 8);
            $reasonLabels[] = 'same target audience';
        }

        $sharedPricingCount = count($sharedPricingIds);
        if ($sharedPricingCount > 0) {
            $score += min(10, $sharedPricingCount * 5);
            $reasonLabels[] = 'similar pricing model';
        }

        $sharedTechCount = count($sharedTechIds);
        if ($sharedTechCount > 0) {
            $score += min(24, $sharedTechCount * 12);
            $reasonLabels[] = 'overlapping tech stack';
        }

        $score += (int) round($textSimilarity * 40);
        if ($textSimilarity >= 0.18) {
            $reasonLabels[] = 'similar product positioning';
        }

        if ($sharedBroadSoftwareCount > 0) {
            $score -= min(20, $sharedBroadSoftwareCount * 10);
        }

        $popularity = (int) ($candidate->votes_count ?? 0) + (int) ($candidate->impressions ?? 0);
        if ($popularity > 0) {
            $score += min(6, (int) floor(log10($popularity + 1) * 2));
        }

        $score = max(0, $score);

        $hasOnlyBroadOverlap = $sharedSoftwareCount > 0
            && $sharedSoftwareCount === $sharedBroadSoftwareCount
            && $sharedBestForCount === 0
            && $sharedTechCount === 0
            && $textSimilarity < 0.14;

        $qualifiesComparison = !$hasOnlyBroadOverlap
            && $score >= 60
            && (
                $sharedSoftwareCount >= 2
                || ($sharedSoftwareCount >= 1 && ($textSimilarity >= 0.14 || $sharedTechCount >= 1 || $sharedBestForCount >= 1))
            );

        $qualifiesAlternative = !$hasOnlyBroadOverlap
            && $score >= 42
            && ($sharedSoftwareCount >= 1 || $sharedTechCount >= 1 || $textSimilarity >= 0.16);

        $summary = $this->buildSummary(
            $sourceProfile,
            $sharedSoftwareIds,
            $sharedPricingIds,
            $sharedBestForIds,
            $sharedTechIds,
            $reasonLabels
        );

        return [
            'score' => $score,
            'summary' => $summary,
            'reasonLabels' => $reasonLabels,
            'qualifiesComparison' => $qualifiesComparison,
            'qualifiesAlternative' => $qualifiesAlternative,
        ];
    }

    private function buildSummary(
        array $sourceProfile,
        array $sharedSoftwareIds,
        array $sharedPricingIds,
        array $sharedBestForIds,
        array $sharedTechIds,
        array $reasonLabels
    ): string {
        if (!empty($sharedSoftwareIds)) {
            $names = collect($sharedSoftwareIds)
                ->map(fn($id) => $sourceProfile['softwareNameMap'][$id] ?? null)
                ->filter()
                ->take(2)
                ->values()
                ->implode(', ');

            if ($names !== '') {
                if (!empty($sharedBestForIds)) {
                    return 'A close match in ' . $names . ' for a similar audience.';
                }

                if (!empty($sharedPricingIds)) {
                    return 'A like-for-like option in ' . $names . ' with comparable pricing signals.';
                }

                return 'A strong like-for-like option in ' . $names . '.';
            }
        }

        if (!empty($sharedBestForIds)) {
            return 'Targets a similar audience and use case.';
        }

        if (!empty($sharedTechIds)) {
            return 'Worth considering if technical overlap matters.';
        }

        if (!empty($reasonLabels)) {
            return Str::ucfirst($reasonLabels[0]) . '.';
        }

        return 'Related through overlapping product signals.';
    }

    private function buildProfile(Product $product): array
    {
        $softwareIds = [];
        $softwareNameMap = [];
        $bestForIds = [];
        $pricingIds = [];

        foreach ($product->categories as $category) {
            if ($this->isSoftwareCategory($category)) {
                $softwareIds[] = (int) $category->id;
                $softwareNameMap[(int) $category->id] = $category->name;
            }

            if ($this->isBestForCategory($category)) {
                $bestForIds[] = (int) $category->id;
            }

            if ($this->isPricingCategory($category)) {
                $pricingIds[] = (int) $category->id;
            }
        }

        $text = collect([
            $product->name,
            $product->tagline,
            $product->product_page_tagline,
            strip_tags((string) $product->description),
        ])->filter()->implode(' ');

        return [
            'softwareIds' => array_values(array_unique($softwareIds)),
            'softwareNameMap' => $softwareNameMap,
            'bestForIds' => array_values(array_unique($bestForIds)),
            'pricingIds' => array_values(array_unique($pricingIds)),
            'techStackIds' => $product->techStacks->pluck('id')->map(fn($id) => (int) $id)->unique()->values()->all(),
            'textTokens' => $this->tokenize($text),
        ];
    }

    private function isSoftwareCategory($category): bool
    {
        $typeNames = $category->types
            ->pluck('name')
            ->map(fn($name) => strtolower((string) $name));

        if ($typeNames->isEmpty()) {
            return true;
        }

        if ($typeNames->contains('pricing') || $typeNames->contains('best for')) {
            return false;
        }

        return $typeNames->intersect(self::SOFTWARE_TYPE_NAMES)->isNotEmpty();
    }

    private function isBestForCategory($category): bool
    {
        return $category->types
            ->pluck('name')
            ->map(fn($name) => strtolower((string) $name))
            ->contains('best for');
    }

    private function isPricingCategory($category): bool
    {
        return $category->types
            ->pluck('name')
            ->map(fn($name) => strtolower((string) $name))
            ->contains('pricing');
    }

    private function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]+/', ' ', $text);
        $tokens = preg_split('/\s+/', trim((string) $text)) ?: [];

        $stopwords = [
            'and', 'the', 'for', 'with', 'from', 'that', 'this', 'your', 'you', 'our', 'are', 'app', 'tool',
            'software', 'product', 'platform', 'helps', 'help', 'into', 'than', 'more', 'best', 'top',
        ];

        return collect($tokens)
            ->filter(fn($token) => strlen($token) >= 3)
            ->reject(fn($token) => in_array($token, $stopwords, true))
            ->unique()
            ->values()
            ->all();
    }

    private function jaccardSimilarity(array $tokensA, array $tokensB): float
    {
        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        $intersectCount = count(array_intersect($tokensA, $tokensB));
        $unionCount = count(array_unique(array_merge($tokensA, $tokensB)));

        if ($unionCount === 0) {
            return 0.0;
        }

        return $intersectCount / $unionCount;
    }
}
