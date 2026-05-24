<?php

use App\Services\ProductLogoStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('optimizes uploaded raster logos into compact webp files', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('logo.png', 512, 512);
    $path = app(ProductLogoStorageService::class)->storeUploadedFile($file);

    expect($path)->toStartWith('logos/')->toEndWith('.webp');

    Storage::disk('public')->assertExists($path);

    [$width, $height] = getimagesize(Storage::disk('public')->path($path));

    expect($width)->toBe(100)
        ->and($height)->toBe(100);
});
