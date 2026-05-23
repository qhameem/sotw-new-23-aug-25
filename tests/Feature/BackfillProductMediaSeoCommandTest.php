<?php

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

it('backfills product media filenames and alt text for existing records', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'name' => 'AgentPhone',
        'slug' => 'agentphone',
        'product_page_tagline' => 'Enable your AI agents to make calls and send messages with ease',
    ]);

    Storage::disk('public')->put('product_media/legacy-upload.png', 'image-content');
    Storage::disk('public')->put('product_media/thumb_legacy-upload.png', 'thumb-content');
    Storage::disk('public')->put('product_media/medium_legacy-upload.png', 'medium-content');

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'path' => 'product_media/legacy-upload.png',
        'path_thumb' => 'product_media/thumb_legacy-upload.png',
        'path_medium' => 'product_media/medium_legacy-upload.png',
        'alt_text' => 'AgentPhone product image 1',
        'type' => 'image',
    ]);

    $this->artisan('product-media:backfill-seo')
        ->expectsOutputToContain('Processed: 1')
        ->expectsOutputToContain('Renamed: 1')
        ->expectsOutputToContain('Alt text updated: 1')
        ->assertExitCode(0);

    $media->refresh();

    expect($media->path)->toBe('product_media/agentphone-homepage-screenshot.png');
    expect($media->path_thumb)->toBe('product_media/thumb_agentphone-homepage-screenshot.png');
    expect($media->path_medium)->toBe('product_media/medium_agentphone-homepage-screenshot.png');
    expect($media->alt_text)->toBe('AgentPhone homepage showing your AI agents to make calls and send messages with ease');

    Storage::disk('public')->assertMissing('product_media/legacy-upload.png');
    Storage::disk('public')->assertMissing('product_media/thumb_legacy-upload.png');
    Storage::disk('public')->assertMissing('product_media/medium_legacy-upload.png');
    Storage::disk('public')->assertExists('product_media/agentphone-homepage-screenshot.png');
    Storage::disk('public')->assertExists('product_media/thumb_agentphone-homepage-screenshot.png');
    Storage::disk('public')->assertExists('product_media/medium_agentphone-homepage-screenshot.png');
});

it('supports dry run mode without changing stored media', function () {
    Storage::fake('public');

    $product = Product::factory()->create([
        'name' => 'OpenWork',
        'slug' => 'openwork',
        'product_page_tagline' => 'Open source Claude cowork alternative for teams',
    ]);

    Storage::disk('public')->put('product_media/legacy-image.webp', 'image-content');

    $media = ProductMedia::create([
        'product_id' => $product->id,
        'path' => 'product_media/legacy-image.webp',
        'alt_text' => 'OpenWork product image 1',
        'type' => 'image',
    ]);

    $this->artisan('product-media:backfill-seo', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run complete.')
        ->assertExitCode(0);

    $media->refresh();

    expect($media->path)->toBe('product_media/legacy-image.webp');
    expect($media->alt_text)->toBe('OpenWork product image 1');

    Storage::disk('public')->assertExists('product_media/legacy-image.webp');
    Storage::disk('public')->assertMissing('product_media/openwork-homepage-screenshot.webp');
});
