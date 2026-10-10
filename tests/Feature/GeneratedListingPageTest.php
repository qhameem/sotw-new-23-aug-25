<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders generated SEO and facts before category navigation', function () {
    $product = Product::factory()->create([
        'name' => 'NotchOwl',
        'seo_title' => 'NotchOwl: Mac notch app for personal task planning',
        'meta_description' => 'NotchOwl keeps tasks and notes in the Mac notch. It is a one-time purchase for people who want a small desktop planning app.',
        'summary' => 'NotchOwl is a Mac notch app for personal task planning. It keeps notes and tasks near the top of the screen.',
        'facts_json' => ['platforms' => ['MacOS'], 'pricing_model' => 'One-time purchase', 'version' => '1.0'],
        'features' => ['Task notes near the notch'],
        'best_for_text' => 'Individual Mac users',
        'not_for_text' => 'Windows users',
        'faq' => [['question' => 'Does NotchOwl work on Mac?', 'answer' => 'Yes, it runs on MacOS.']],
    ]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('<title>NotchOwl: Mac notch app for personal task planning</title>', false);
    $response->assertSee('Task notes near the notch');
    $response->assertSee('Does NotchOwl work on Mac?');
});
