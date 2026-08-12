<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders product facts as the stable public description for cohort B', function () {
    $product = Product::factory()->create([
        'name' => 'Cohort Product',
        'slug' => 'cohort-product',
        'description' => '<p>Long editorial description that should not appear in the overview.</p>',
        'description_format' => 'facts',
        'product_facts' => ['Processes local files', 'Exports structured results'],
    ]);

    $response = $this->get(route('products.show', $product));

    $response->assertOk();
    $response->assertSee('Processes local files');
    $response->assertSee('Exports structured results');
    $response->assertDontSee('Read full editorial notes');

    $product->refresh();
    expect($product->content_test_group)->toBe('B')
        ->and($product->content_test_started_at)->not->toBeNull();
});

it('keeps existing products in the full-description cohort by default', function () {
    $product = Product::factory()->create([
        'description' => '<p>Stable full description</p>',
    ]);

    expect($product->description_format)->toBe('full')
        ->and($product->content_test_group)->toBe('A');
});
