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
