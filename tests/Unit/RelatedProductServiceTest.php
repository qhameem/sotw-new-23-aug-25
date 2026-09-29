<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\TechStack;
use App\Models\Type;
use App\Services\RelatedProductService;
use Illuminate\Support\Collection;

function relatedProductForTest(array $attributes, Collection $categories, Collection $techStacks): Product
{
    $product = new Product($attributes);
    $product->setRelation('categories', $categories);
    $product->setRelation('techStacks', $techStacks);

    return $product;
}

it('does not use votes or impressions when scoring alternatives', function () {
    $softwareType = new Type(['name' => 'Software']);
    $softwareType->id = 1;

    $category = new Category(['name' => 'Terminal Tools', 'slug' => 'terminal-tools']);
    $category->id = 10;
    $category->setRelation('types', collect([$softwareType]));

    $source = relatedProductForTest([
        'name' => 'Source',
        'tagline' => 'Terminal workflow automation',
        'votes_count' => 0,
        'impressions' => 0,
    ], collect([$category]), collect());

    $quietCandidate = relatedProductForTest([
        'name' => 'Quiet Candidate',
        'tagline' => 'Terminal workflow automation',
        'votes_count' => 0,
        'impressions' => 0,
    ], collect([$category]), collect());

    $popularCandidate = relatedProductForTest([
        'name' => 'Popular Candidate',
        'tagline' => 'Terminal workflow automation',
        'votes_count' => 100000,
        'impressions' => 1000000,
    ], collect([$category]), collect());

    $service = app(RelatedProductService::class);

    expect($service->scorePair($source, $quietCandidate)['score'])
        ->toBe($service->scorePair($source, $popularCandidate)['score']);
});

it('does not qualify a product as an alternative from tech-stack overlap alone', function () {
    $stack = new TechStack(['name' => 'Laravel', 'slug' => 'laravel']);
    $stack->id = 20;

    $source = relatedProductForTest([
        'name' => 'Accounting Product',
        'tagline' => 'Accounting and invoicing',
    ], collect(), collect([$stack]));

    $candidate = relatedProductForTest([
        'name' => 'Photo Editor',
        'tagline' => 'Photo editing and image filters',
    ], collect(), collect([$stack]));

    $match = app(RelatedProductService::class)->scorePair($source, $candidate);

    expect($match['qualifiesAlternative'])->toBeFalse();
});

it('indexes an alternatives page with two strong relevant products', function () {
    $product = new Product(['alternative_product_ids' => []]);
    $alternatives = collect([
        tap(new Product, fn (Product $alternative) => $alternative->setAttribute('match_score', 70)),
        tap(new Product, fn (Product $alternative) => $alternative->setAttribute('match_score', 60)),
    ]);

    expect(app(RelatedProductService::class)->shouldNoindexAlternatives($product, $alternatives))->toBeFalse();
});

it('keeps a thin alternatives page out of the index', function () {
    $product = new Product(['alternative_product_ids' => []]);
    $alternatives = collect([
        tap(new Product, fn (Product $alternative) => $alternative->setAttribute('match_score', 80)),
    ]);

    expect(app(RelatedProductService::class)->shouldNoindexAlternatives($product, $alternatives))->toBeTrue();
});

it('indexes a comparison when the pair qualifies as an alternative', function () {
    $productA = new Product(['comparison_product_ids' => []]);
    $productA->id = 1;
    $productB = new Product(['comparison_product_ids' => []]);
    $productB->id = 2;

    $match = [
        'qualifiesComparison' => false,
        'qualifiesAlternative' => true,
    ];

    expect(app(RelatedProductService::class)->shouldNoindexComparison($productA, $productB, $match))->toBeFalse();
});
