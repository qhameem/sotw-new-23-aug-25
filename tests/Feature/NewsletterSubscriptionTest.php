<?php

use App\Jobs\SyncNewsletterSubscriberToEmailOctopus;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('services.emailoctopus.api_key', 'test-api-key');
    config()->set('services.emailoctopus.list_id', 'list-456');
    config()->set('services.emailoctopus.base_url', 'https://emailoctopus.test/api/1.6');
});

it('shows the public newsletter page', function () {
    $this->get(route('newsletter.index'))
        ->assertOk()
        ->assertSee('The best software, delivered.');
});

it('records consent and queues an EmailOctopus sync', function () {
    Queue::fake();

    $this->post(route('newsletter.store'), [
        'email' => 'Reader@Example.com',
        'first_name' => ' Reader ',
        'consent' => '1',
        'company' => '',
    ])->assertRedirect()->assertSessionHas('newsletter_success');

    $subscriber = NewsletterSubscriber::firstOrFail();

    expect($subscriber->email)->toBe('reader@example.com')
        ->and($subscriber->first_name)->toBe('Reader')
        ->and($subscriber->status)->toBe('pending')
        ->and($subscriber->consented_at)->not->toBeNull();

    Queue::assertPushed(SyncNewsletterSubscriberToEmailOctopus::class, fn ($job) => $job->newsletterSubscriberId === $subscriber->id);
});

it('requires valid consent and rejects the honeypot', function () {
    Queue::fake();

    $this->post(route('newsletter.store'), [
        'email' => 'reader@example.com',
        'company' => 'Spam Ltd',
    ])->assertSessionHasErrors(['company', 'consent']);

    expect(NewsletterSubscriber::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('syncs a subscriber to EmailOctopus', function () {
    Http::fake([
        'https://emailoctopus.test/api/1.6/lists/list-456/contacts' => Http::response([
            'id' => 'contact-123',
            'status' => 'PENDING',
        ], 201),
    ]);

    $subscriber = NewsletterSubscriber::create([
        'email' => 'reader@example.com',
        'first_name' => 'Reader',
        'status' => 'pending',
        'source' => 'newsletter_page',
        'consented_at' => now(),
    ]);

    (new SyncNewsletterSubscriberToEmailOctopus($subscriber->id))->handle(app(\App\Services\EmailOctopusNewsletterService::class));

    expect($subscriber->fresh()->status)->toBe('synced')
        ->and($subscriber->fresh()->provider_contact_id)->toBe('contact-123');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://emailoctopus.test/api/1.6/lists/list-456/contacts'
        && $request['api_key'] === 'test-api-key'
        && $request['email_address'] === 'reader@example.com'
        && $request['fields']['FirstName'] === 'Reader'
        && ! isset($request['status']));
});
