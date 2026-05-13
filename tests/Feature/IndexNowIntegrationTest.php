<?php

use App\Jobs\SubmitIndexNowUrls;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('services.indexnow.enabled', true);
    config()->set('services.indexnow.key', 'test-indexnow-key');
    config()->set('app.url', 'https://softwareontheweb.com');
});

it('serves the IndexNow key file from a public route', function () {
    $response = $this->get(route('indexnow.key', ['key' => 'test-indexnow-key']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe("test-indexnow-key\n");
});

it('queues an IndexNow submission when a product becomes live', function () {
    Queue::fake();

    $product = Product::factory()->create([
        'approved' => true,
        'is_published' => false,
        'slug' => 'indexnow-launch',
    ]);

    Queue::fake();

    $product->update([
        'is_published' => true,
        'published_at' => now(),
    ]);

    Queue::assertPushed(SubmitIndexNowUrls::class, function (SubmitIndexNowUrls $job) use ($product) {
        return $job->urls === [route('products.show', $product->slug)];
    });
});

it('queues IndexNow submissions for live slug changes and deletions', function () {
    Queue::fake();

    $product = Product::factory()->create([
        'approved' => true,
        'is_published' => true,
        'slug' => 'old-slug',
    ]);

    Queue::fake();

    $product->update([
        'slug' => 'new-slug',
    ]);

    Queue::assertPushed(SubmitIndexNowUrls::class, function (SubmitIndexNowUrls $job) {
        return $job->urls === [
            'https://softwareontheweb.com/product/old-slug',
            'https://softwareontheweb.com/product/new-slug',
        ];
    });

    Queue::fake();

    $product->delete();

    Queue::assertPushed(SubmitIndexNowUrls::class, function (SubmitIndexNowUrls $job) {
        return $job->urls === [
            'https://softwareontheweb.com/product/new-slug',
        ];
    });
});
