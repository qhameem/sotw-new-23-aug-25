<?php

use App\Models\Ad;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product listing card ads render as compact listing-style cards', function () {
    $ad = Ad::factory()->create([
        'internal_name' => 'Product Style Ad',
        'tagline' => 'Logo left, text right',
        'type' => 'product_listing_card',
        'content' => 'ads/product-style.png',
        'target_url' => 'https://example.com/product-style',
        'open_in_new_tab' => true,
    ]);

    $html = view('partials.render_ad_block', [
        'ad' => $ad,
        'zoneSlug' => 'sidebar-top',
    ])->render();

    expect($html)->toContain('Product Style Ad')
        ->and($html)->toContain('Logo left, text right')
        ->and($html)->toContain('sidebar-top')
        ->and($html)->toContain('ad-link-out-icon')
        ->and($html)->not->toContain('Promoted')
        ->and($html)->not->toContain('Price:')
        ->and($html)->not->toContain('product-upvote-button');
});
