<?php

namespace Tests\Feature;

use App\Mail\PaidSubmissionPaidAdminNotification;
use App\Mail\PaymentConfirmation;
use App\Models\PaidSubmissionCheckout;
use App\Models\PaymentEventLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaidSubmissionWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_submission_webhook_creates_product_sends_buyer_receipt_and_admin_email_and_is_idempotent(): void
    {
        Mail::fake();
        Queue::fake();

        config(['stripe.webhook_secret' => 'whsec_test_secret']);

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole($adminRole);

        $user = User::factory()->create();

        $checkout = PaidSubmissionCheckout::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'status' => 'checkout_created',
            'product_name' => 'Paid Launch Tool',
            'product_link' => 'https://paid-launch-tool.test',
            'schedule_date' => now()->addDays(3)->toDateString(),
            'amount_cents' => 999,
            'currency' => 'usd',
            'submission_payload' => [
                'name' => 'Paid Launch Tool',
                'tagline' => 'Launch with control',
                'product_page_tagline' => 'Launch with control',
                'description' => '<p>Paid submission description</p>',
                'link' => 'https://paid-launch-tool.test',
                'categories' => [],
                'custom_categories' => [],
                'tech_stacks' => [],
                'custom_tech_stacks' => [],
                'maker_links' => [],
                'sell_product' => false,
                'asking_price' => null,
                'pricing_page_url' => null,
                'x_account' => null,
                'additional_resources' => null,
                'video_url' => null,
                'paid_schedule_date' => now()->addDays(3)->toDateString(),
                'stored_logo_path' => null,
                'logo_url' => null,
                'staged_media_paths' => [],
                'media_urls' => [],
            ],
            'stripe_checkout_session_id' => 'cs_test_paid_submission_123',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $payload = [
            'id' => 'evt_paid_submission_123',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_paid_submission_123',
                    'object' => 'checkout.session',
                    'payment_intent' => 'pi_test_paid_submission_123',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'checkout_id' => (string) $checkout->id,
                        'checkout_uuid' => $checkout->uuid,
                        'user_id' => (string) $user->id,
                        'type' => 'paid-submission',
                    ],
                ],
            ],
        ];

        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $encodedPayload, config('stripe.webhook_secret'));

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Stripe-Signature' => 't=' . $timestamp . ',v1=' . $signature,
            ],
            $encodedPayload
        );

        $response->assertOk()
            ->assertJson([
                'received' => true,
            ]);

        $this->assertSame(1, Product::count());

        $product = Product::first();

        $this->assertNotNull($product);
        $this->assertSame('Paid Launch Tool', $product->name);
        $this->assertSame('paid', $product->submission_type);
        $this->assertTrue((bool) $product->approved);
        $this->assertSame($user->id, $product->user_id);

        $checkout->refresh();

        $this->assertSame('completed', $checkout->status);
        $this->assertSame($product->id, $checkout->product_id);
        $this->assertSame('pi_test_paid_submission_123', $checkout->stripe_payment_intent_id);
        $this->assertNotNull($checkout->processed_at);
        $this->assertNotNull($checkout->paid_at);
        $this->assertNotNull($checkout->receipt_sent_at);

        $this->assertDatabaseHas('payment_event_logs', [
            'provider' => 'stripe',
            'provider_event_id' => 'evt_paid_submission_123',
            'event_type' => 'checkout.session.completed',
        ]);

        $this->assertNotNull(PaymentEventLog::first()?->processed_at);

        Mail::assertQueued(PaidSubmissionPaidAdminNotification::class, function (PaidSubmissionPaidAdminNotification $mail) use ($checkout, $product) {
            return $mail->product->is($product) && $mail->checkout->id === $checkout->id;
        });

        Mail::assertQueued(PaymentConfirmation::class, function (PaymentConfirmation $mail) use ($checkout, $product, $user) {
            return $mail->product->is($product)
                && $mail->user->is($user)
                && $mail->checkout->id === $checkout->id
                && $mail->hasTo($user->email);
        });

        $secondResponse = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Stripe-Signature' => 't=' . $timestamp . ',v1=' . $signature,
            ],
            $encodedPayload
        );

        $secondResponse->assertOk();

        $this->assertSame(1, Product::count());
        $this->assertSame(1, PaymentEventLog::count());
    }
}
