<?php

use App\Models\PageMetaTag;
use App\Models\Product;

test('home route uses configured seo meta title', function () {
    Product::factory()->create([
        'published_at' => now()->subDay(),
        'votes_count' => 5,
        'impressions' => 10,
    ]);

    PageMetaTag::create([
        'page_id' => 'home',
        'meta_title' => 'Software on the Web | Discover and Launch SaaS Tools',
        'meta_description' => 'Discover new AI, productivity, and tech tools daily.',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('<title>Software on the Web | Discover and Launch SaaS Tools</title>', false);
});

test('social image partial renders complete metadata for a generated image', function () {
    $imageUrl = 'https://softwareontheweb.com/storage/og_images/social-preview.jpg';
    $html = view('partials.social-image-meta', [
        'resolvedSocialImage' => $imageUrl,
        'meta_og_image_alt' => 'Software on the Web social preview',
    ])->render();

    expect($html)
        ->toContain('<meta property="og:image" content="'.$imageUrl.'">')
        ->toContain('<meta property="og:image:secure_url" content="'.$imageUrl.'">')
        ->toContain('<meta property="og:image:type" content="image/jpeg">')
        ->toContain('<meta property="og:image:width" content="1200">')
        ->toContain('<meta property="og:image:height" content="630">')
        ->toContain('<meta property="og:image:alt" content="Software on the Web social preview">')
        ->toContain('<meta name="twitter:image:alt" content="Software on the Web social preview">');
});
