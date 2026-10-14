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

it('persists explicit admin approval of generated drafts', function (bool $bulk, bool $scheduled) {
    \Illuminate\Support\Facades\Event::fake([\App\Events\ProductApproved::class]);
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']));
    $product = Product::factory()->create(['generation_status' => 'draft']);
    $date = now('UTC')->addWeek()->toDateString();

    $response = $this->actingAs($admin)->post(
        route($bulk ? 'admin.product-approvals.bulk-approve' : 'admin.product-approvals.approve', $bulk ? [] : $product),
        $bulk
            ? ['products' => [$product->id], 'bulk_published_at' => $scheduled ? $date : null]
            : ['publish_option' => $scheduled ? 'specific_date' : 'now', 'published_at' => $date]
    );

    $response->assertRedirect()->assertSessionHas('success')->assertSessionMissing('error');
    $product->refresh();
    expect($product->approved)->toBeTrue()
        ->and($product->is_published)->toBe(! $scheduled)
        ->and($product->generation_status)->toBe('ready')
        ->and($product->generation_noindex)->toBeFalse()
        ->and($product->generation_review_required)->toBeFalse();
    if ($scheduled) {
        expect($product->published_at->toDateString())->toBe($date);
    }

    $product->save();
    expect($product->fresh()->approved)->toBeTrue();
    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\ProductApproved::class);
})->with([
    'individual immediate' => [false, false],
    'individual scheduled' => [false, true],
    'bulk immediate' => [true, false],
    'bulk scheduled' => [true, true],
]);
