<?php

use App\Models\Product;

it('redirects reversed comparisons and rejects self comparisons', function () {
    Product::factory()->create(['slug' => 'alpha']);
    Product::factory()->create(['slug' => 'beta']);

    $this->get('/compare/beta-vs-alpha')->assertStatus(301)->assertRedirect('/compare/alpha-vs-beta');
    $this->get('/compare/alpha-vs-alpha')->assertNotFound();
    $this->get('/compare/missing-vs-alpha')->assertNotFound();
});

it('renders an indexable curated comparison with one h1 and a canonical', function () {
    $productA = Product::factory()->create([
        'name' => 'Alpha',
        'slug' => 'alpha',
        'features' => ['Offline rendering', 'Batch exports'],
        'best_for_text' => 'Video editors',
    ]);
    $productB = Product::factory()->create([
        'name' => 'Beta',
        'slug' => 'beta',
        'features' => ['Cloud rendering', 'Shared projects'],
        'best_for_text' => 'Editing teams',
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

it('keeps a curated but thin comparison noindex until decision details exist', function () {
    $a = Product::factory()->create(['slug' => 'alpha', 'features' => [], 'description' => '<p>Brief summary.</p>']);
    $b = Product::factory()->create(['slug' => 'beta', 'features' => [], 'description' => '<p>Brief summary.</p>']);
    $a->update(['comparison_product_ids' => [$b->id]]);

    $this->get('/compare/alpha-vs-beta')->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('Listed features')->assertSee('Not listed')
        ->assertDontSee('Which is Better?');
});

it('renders decision facts and escapes stored feature text', function () {
    $a = Product::factory()->create([
        'slug' => 'alpha', 'features' => ['Offline exports', '<script>alert(1)</script>'],
        'best_for_text' => 'Solo editors', 'not_for_text' => 'No shared projects',
        'price' => 12, 'currency' => 'usd',
    ]);
    $b = Product::factory()->create([
        'slug' => 'beta', 'features' => ['Shared projects', 'Cloud exports'], 'best_for_text' => 'Teams',
    ]);
    $a->update(['comparison_product_ids' => [$b->id]]);

    $this->get('/compare/alpha-vs-beta')->assertOk()
        ->assertSee('Offline exports')->assertSee('Cloud exports')
        ->assertSee('Solo editors')->assertSee('No shared projects')->assertSee('USD 12.00')
        ->assertDontSee('<script>alert(1)</script>', false);
});
