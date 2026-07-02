<?php

use App\Jobs\SubmitUrlNotifications;
use App\Models\Article;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('services.indexnow.enabled', true);
    config()->set('services.indexnow.key', 'test-indexnow-key');
    config()->set('services.google_indexing.enabled', false);
    config()->set('app.url', 'https://softwareontheweb.com');
});

it('serves the IndexNow key file from a public route', function () {
    $response = $this->get(route('indexnow.key', ['key' => 'test-indexnow-key']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe("test-indexnow-key\n");
});

it('queues a URL notification when a product becomes live', function () {
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

    Queue::assertPushed(SubmitUrlNotifications::class, function (SubmitUrlNotifications $job) use ($product) {
        return $job->updatedUrls === [route('products.show', $product->slug)]
            && $job->deletedUrls === [];
    });
});

it('queues URL notifications for live slug changes and deletions', function () {
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

    Queue::assertPushed(SubmitUrlNotifications::class, function (SubmitUrlNotifications $job) {
        return $job->updatedUrls === [
            route('products.show', 'new-slug'),
        ] && $job->deletedUrls === [
            route('products.show', 'old-slug'),
        ];
    });

    Queue::fake();

    $product->delete();

    Queue::assertPushed(SubmitUrlNotifications::class, function (SubmitUrlNotifications $job) {
        return $job->updatedUrls === [] && $job->deletedUrls === [
            route('products.show', 'new-slug'),
        ];
    });
});

it('queues URL notifications for published article updates and deletions', function () {
    Queue::fake();

    $user = User::factory()->create();

    $article = Article::create([
        'user_id' => $user->id,
        'title' => 'Initial Title',
        'slug' => 'initial-title',
        'content' => '<p>Content</p>',
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);

    Queue::fake();

    $article->update([
        'slug' => 'updated-title',
    ]);

    Queue::assertPushed(SubmitUrlNotifications::class, function (SubmitUrlNotifications $job) {
        return $job->updatedUrls === [
            route('articles.show', 'updated-title'),
        ] && $job->deletedUrls === [
            route('articles.show', 'initial-title'),
        ];
    });

    Queue::fake();

    $article->delete();

    Queue::assertPushed(SubmitUrlNotifications::class, function (SubmitUrlNotifications $job) {
        return $job->updatedUrls === [] && $job->deletedUrls === [
            route('articles.show', 'updated-title'),
        ];
    });
});

it('queues notifications when only Google indexing is enabled', function () {
    Queue::fake();

    config()->set('services.indexnow.enabled', false);
    config()->set('services.google_indexing.enabled', true);
    config()->set('services.google_indexing.service_account_json', json_encode([
        'type' => 'service_account',
        'project_id' => 'test-project',
        'private_key_id' => 'test-key-id',
        'private_key' => "-----BEGIN PRIVATE KEY-----\nTEST\n-----END PRIVATE KEY-----\n",
        'client_email' => 'test@test-project.iam.gserviceaccount.com',
        'client_id' => '1234567890',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/test',
    ]));

    Product::factory()->create([
        'approved' => true,
        'is_published' => true,
        'slug' => 'google-only-indexing',
    ]);

    Queue::assertPushed(SubmitUrlNotifications::class, function (SubmitUrlNotifications $job) {
        return $job->updatedUrls === [
            route('products.show', 'google-only-indexing'),
        ] && $job->deletedUrls === [];
    });
});
