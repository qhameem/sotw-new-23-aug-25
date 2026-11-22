<?php

use App\Models\Category;
use App\Models\Product;

test('catalog filters public products and sorts by effective publication date', function () {
    $category = Category::factory()->create();
    $older = Product::factory()->create([
        'name' => 'Legacy catalog launch',
        'published_at' => null,
        'created_at' => '2025-01-01 12:00:00',
    ]);
    $newer = Product::factory()->create([
        'name' => 'New catalog launch',
        'published_at' => '2025-02-01 12:00:00',
    ]);
    $other = Product::factory()->create(['name' => 'Uncategorized catalog launch']);
    $hidden = Product::factory()->create(['is_published' => false]);
    $unapproved = Product::factory()->create(['approved' => false]);
    $category->products()->attach([$older->id, $newer->id, $hidden->id, $unapproved->id]);

    $this->get('/products')
        ->assertOk()
        ->assertSeeInOrder([$newer->name, $older->name])
        ->assertSee($other->name)
        ->assertSee('Browse software')
        ->assertSee('Top software categories')
        ->assertDontSee($hidden->name)
        ->assertDontSee($unapproved->name);

    $this->get(route('products.catalog', ['category' => $category->slug, 'sort' => 'oldest']))
        ->assertOk()
        ->assertSeeInOrder([$older->name, $newer->name])
        ->assertDontSee($other->name)
        ->assertViewHas('products', fn ($products) => $products->total() === 2
            && str_contains($products->url(2), 'category='.$category->slug)
            && str_contains($products->url(2), 'sort=oldest'));
});

test('catalog renders one pagination control when results span multiple pages', function () {
    Product::factory()->count(25)->create();

    $response = $this->get('/products')
        ->assertOk()
        ->assertSee('Page 1 of 2')
        ->assertSee('aria-current="page" aria-label="Page 1"', false)
        ->assertSee('rel="next"', false);

    $this->get('/products?page=2')
        ->assertOk()
        ->assertSee('Page 2 of 2')
        ->assertSee('rel="prev"', false);

    expect(substr_count($response->getContent(), 'aria-label="Pagination Navigation"'))->toBe(1);
});

test('catalog combines platform, pricing and use case filters', function () {
    $matching = Product::factory()->create(['name' => 'Matches all catalog filters']);
    $partial = Product::factory()->create(['name' => 'Matches only platform']);
    $filters = [];

    foreach (['platform' => 'Platform', 'pricing' => 'Pricing', 'use_case' => 'Use Case'] as $key => $typeName) {
        $type = \App\Models\Type::firstOrCreate(['name' => $typeName]);
        $category = Category::factory()->create();
        $category->types()->attach($type);
        $category->products()->attach($matching);
        if ($key === 'platform') {
            $category->products()->attach($partial);
        }
        $filters[$key] = $category->slug;
    }

    $response = $this->get(route('products.catalog', $filters + ['sort' => 'oldest']))
        ->assertOk()
        ->assertSee($matching->name)
        ->assertDontSee($partial->name)
        ->assertViewHas('filters', $filters);

    parse_str(parse_url($response->viewData('products')->url(2), PHP_URL_QUERY), $query);
    expect($query)->toMatchArray($filters + ['sort' => 'oldest', 'page' => '2']);
});
