<?php

use App\Models\Product;
use App\Services\ProductLogoStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('optimizes existing stored product logos', function () {
    Storage::fake('public');

    $sourceLogo = UploadedFile::fake()->image('legacy-logo.png', 512, 512);
    Storage::disk('public')->put('logos/legacy-logo.png', file_get_contents($sourceLogo->getRealPath()));

    $product = Product::factory()->create([
        'name' => 'Linear',
        'logo' => 'logos/legacy-logo.png',
    ]);

    $this->artisan('products:optimize-logos')
        ->expectsOutputToContain('Processed: 1')
        ->expectsOutputToContain('Optimized: 1')
        ->expectsOutputToContain('Skipped: 0')
        ->expectsOutputToContain('Failed: 0')
        ->assertExitCode(0);

    $product->refresh();

    expect($product->logo)->toStartWith('logos/')
        ->toEndWith('.webp')
        ->not->toBe('logos/legacy-logo.png');

    Storage::disk('public')->assertMissing('logos/legacy-logo.png');
    Storage::disk('public')->assertExists($product->logo);

    [$width, $height] = getimagesize(Storage::disk('public')->path($product->logo));

    expect($width)->toBe(ProductLogoStorageService::TARGET_WIDTH)
        ->and($height)->toBe(ProductLogoStorageService::TARGET_HEIGHT);
});

it('supports dry run mode for existing product logo optimization', function () {
    Storage::fake('public');

    $sourceLogo = UploadedFile::fake()->image('legacy-logo.png', 512, 512);
    Storage::disk('public')->put('logos/legacy-logo.png', file_get_contents($sourceLogo->getRealPath()));

    $product = Product::factory()->create([
        'name' => 'Linear',
        'logo' => 'logos/legacy-logo.png',
    ]);

    $this->artisan('products:optimize-logos', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run complete.')
        ->assertExitCode(0);

    $product->refresh();

    expect($product->logo)->toBe('logos/legacy-logo.png');

    Storage::disk('public')->assertExists('logos/legacy-logo.png');
});

it('localizes external product logos when optimizing existing records', function () {
    Storage::fake('public');

    $remoteLogo = UploadedFile::fake()->image('remote-logo.png', 512, 512);

    Http::fake([
        'https://8.8.8.8/logo.png' => Http::response(
            file_get_contents($remoteLogo->getRealPath()),
            200,
            ['Content-Type' => 'image/png']
        ),
    ]);

    $product = Product::factory()->create([
        'name' => 'Remote Product',
        'logo' => 'https://8.8.8.8/logo.png',
    ]);

    $this->artisan('products:optimize-logos')
        ->expectsOutputToContain('Optimized: 1')
        ->assertExitCode(0);

    $product->refresh();

    expect($product->logo)->toStartWith('logos/')
        ->toEndWith('.webp');

    Storage::disk('public')->assertExists($product->logo);
});
