<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function taxonomyCategory(string $name, string $slug, array $types, int $publishedProducts = 3): Category
{
    $category = Category::factory()->create(compact('name', 'slug'));

    foreach ($types as $typeName) {
        $type = Type::firstOrCreate(['name' => $typeName]);
        $category->types()->attach($type);
    }

    Product::factory()->count($publishedProducts)->create([
        'approved' => true,
        'is_published' => true,
    ])->each(fn (Product $product) => $product->categories()->attach($category));

    return $category->fresh('types');
}

it('redirects legacy taxonomy URLs directly to their final typed URLs', function (string $type, string $routeName) {
    $category = taxonomyCategory('Focused Work', 'focused-work', [$type]);

    $this->get('/category/focused-work?limit=100')
        ->assertRedirect(route($routeName, ['category' => $category->slug, 'limit' => 100]))
        ->assertStatus(301);
})->with([
    ['Use Case', 'use-cases.show'],
    ['Best for', 'best-for.show'],
    ['Platform', 'platforms.show'],
]);

it('keeps category URLs when a slug is also assigned to another type', function () {
    $category = taxonomyCategory('Video Editing', 'video-editing', ['Software', 'Use Case']);

    $this->get('/category/video-editing')
        ->assertOk()
        ->assertSee('Video Editing Software');

    $this->get('/use-case/video-editing')->assertNotFound();

    expect($category->publicUrl())->toBe(route('categories.show', $category->slug));
});

it('renders type-specific copy, canonical, breadcrumbs, and structured data', function (
    string $type,
    string $routeName,
    string $heading,
    string $section
) {
    $category = taxonomyCategory('Remote Teams', 'remote-teams', [$type]);
    $url = route($routeName, $category->slug);

    $this->get($url)
        ->assertOk()
        ->assertSee($heading)
        ->assertSee($section)
        ->assertSee('<link rel="canonical" href="'.$url.'"', false)
        ->assertSee('BreadcrumbList');
})->with([
    ['Software', 'categories.show', 'Remote Teams Software', 'Categories'],
    ['Use Case', 'use-cases.show', 'Tools for Remote Teams', 'Use cases'],
    ['Best for', 'best-for.show', 'Software for Remote Teams', 'Best for'],
    ['Platform', 'platforms.show', 'Remote Teams Apps and Software', 'Platforms'],
]);

it('noindexes taxonomy pages with fewer than three published products', function () {
    $category = taxonomyCategory('Small Audience', 'small-audience', ['Best for'], 2);

    $this->get(route('best-for.show', $category->slug))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

it('removes noindex when a taxonomy page reaches three published products', function () {
    $category = taxonomyCategory('Large Audience', 'large-audience', ['Best for'], 3);

    $this->get(route('best-for.show', $category->slug))
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false);
});

it('renders crawlable taxonomy pagination with page-specific metadata and products', function () {
    $category = taxonomyCategory('Productivity', 'productivity', ['Software'], 51);
    $orderedProducts = $category->products()
        ->orderByRaw('COALESCE(published_at, created_at) DESC')
        ->orderByDesc('products.id')
        ->get();
    $firstPageProduct = $orderedProducts->first();
    $secondPageProduct = $orderedProducts->last();
    $pageTwoUrl = route('categories.show.page', ['category' => $category->slug, 'page' => 2]);

    $this->get(route('categories.show', $category->slug))
        ->assertOk()
        ->assertSee($firstPageProduct->name)
        ->assertDontSee($secondPageProduct->name)
        ->assertSee('href="'.$pageTwoUrl.'"', false)
        ->assertSee('rel="next" href="'.$pageTwoUrl.'"', false);

    $this->get($pageTwoUrl)
        ->assertOk()
        ->assertSee($secondPageProduct->name)
        ->assertDontSee($firstPageProduct->name)
        ->assertSee('Productivity Software, Page 2 | Software on the Web')
        ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
        ->assertSee('<link rel="canonical" href="'.$pageTwoUrl.'"', false)
        ->assertSee('rel="prev" href="'.route('categories.show', $category->slug).'"', false)
        ->assertSee('"position": 51', false)
        ->assertSee('Page 2 of 2');
});

it('redirects legacy query pagination and rejects invalid taxonomy pages', function () {
    $category = taxonomyCategory('Productivity', 'productivity', ['Software'], 51);
    $pageTwoUrl = route('categories.show.page', ['category' => $category->slug, 'page' => 2]);

    $this->get(route('categories.show', ['category' => $category->slug, 'page' => 2]))
        ->assertRedirect($pageTwoUrl)
        ->assertStatus(301);

    $this->get(route('categories.show.page', ['category' => $category->slug, 'page' => 1]))
        ->assertRedirect(route('categories.show', $category->slug))
        ->assertStatus(301);

    $this->get(route('categories.show.page', ['category' => $category->slug, 'page' => 3]))
        ->assertNotFound();
});
