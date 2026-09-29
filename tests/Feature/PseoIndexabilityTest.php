<?php

use App\Models\Product;

it('renders an indexable curated comparison with one h1 and a canonical', function () {
    $productA = Product::factory()->create([
        'name' => 'Alpha',
        'slug' => 'alpha',
    ]);
    $productB = Product::factory()->create([
        'name' => 'Beta',
        'slug' => 'beta',
    ]);

    $productA->update(['comparison_product_ids' => [$productB->id]]);

    $url = route('pseo.compare', ['params' => 'alpha-vs-beta']);

    $response = $this->get($url);

    $response
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
        ->assertSee('<link rel="canonical" href="'.$url.'" />', false);

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

it('keeps an unrelated comparison out of the index while retaining its canonical', function () {
    Product::factory()->create([
        'name' => 'Alpha',
        'slug' => 'alpha',
        'tagline' => 'Accounting invoices and payroll',
    ]);
    Product::factory()->create([
        'name' => 'Beta',
        'slug' => 'beta',
        'tagline' => 'Photo filters and image editing',
    ]);

    $url = route('pseo.compare', ['params' => 'alpha-vs-beta']);

    $this->get($url)
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="'.$url.'" />', false);
});
