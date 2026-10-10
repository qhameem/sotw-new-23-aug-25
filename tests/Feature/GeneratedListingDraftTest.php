<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('never publishes a generated listing after validation fails', function () {
    $product = Product::factory()->create([
        'generation_status' => 'draft',
        'approved' => true,
        'is_published' => true,
    ]);

    expect($product->approved)->toBeFalse();
    expect($product->is_published)->toBeFalse();
    expect($product->generation_noindex)->toBeTrue();
    expect($product->generation_review_required)->toBeTrue();

    $product->update(['approved' => true, 'is_published' => true]);
    expect($product->fresh()->is_published)->toBeFalse();
});
