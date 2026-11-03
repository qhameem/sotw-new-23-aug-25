<?php

namespace Tests\Feature;

use App\Mail\BadgeWarningMail;
use App\Models\BadgeVerificationAttempt;
use App\Models\Product;
use App\Models\User;
use App\Notifications\BadgeVerificationFailed;
use App\Services\BadgeService;
use App\Services\BadgeVerificationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BadgePublicationProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_pre_publish_check_blocks_publication_and_emails_owner(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response('<html>No badge</html>')]);

        $product = Product::factory()->create([
            'link' => 'https://93.184.216.34/product',
            'badge_placement_url' => 'https://93.184.216.34/product',
            'submission_type' => 'badge',
            'badge_verified' => true,
            'approved' => true,
            'is_published' => false,
            'published_at' => now()->subMinute(),
        ]);

        $this->artisan('products:publish-scheduled')->assertSuccessful();

        $product->refresh();
        $this->assertFalse($product->is_published);
        $this->assertFalse($product->badge_verified);
        $this->assertDatabaseHas('badge_verification_attempts', [
            'product_id' => $product->id,
            'trigger' => 'pre_publish',
            'verified' => false,
        ]);
        Mail::assertSent(BadgeWarningMail::class, fn (BadgeWarningMail $mail) => $mail->product->is($product) && ! $mail->wasPublished);
    }

    public function test_manual_failed_check_keeps_published_badge_product_live(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response('<html>No badge</html>')]);

        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::factory()->create([
            'link' => 'https://93.184.216.34/product',
            'badge_placement_url' => 'https://93.184.216.34/product',
            'submission_type' => 'badge',
            'badge_verified' => true,
            'is_published' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.products.verify-badge', $product))->assertRedirect();

        $product->refresh();
        $this->assertTrue($product->is_published);
        $this->assertFalse($product->badge_verified);
        Mail::assertNothingSent();
    }

    public function test_admin_can_publish_despite_failed_badge_and_regular_checks_alert_admins(): void
    {
        Mail::fake();
        Notification::fake();
        Http::fake(['*' => Http::response('Forbidden', 403)]);
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');
        $product = Product::factory()->create([
            'link' => 'https://93.184.216.34/product',
            'submission_type' => 'badge',
            'approved' => true,
            'is_published' => false,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->post(route('admin.product-approvals.publish-scheduled-now'), [
            'products' => [$product->id],
        ])->assertSessionHas('success');
        $this->assertTrue($product->refresh()->is_published);
        $this->assertFalse($product->badge_verified);
        Mail::assertNothingSent();

        (new \App\Jobs\VerifyBadgePlacement($product))->handle(app(BadgeVerificationManager::class));

        $this->assertTrue($product->refresh()->is_published);
        foreach ([$admin, $otherAdmin] as $recipient) {
            Notification::assertSentTo($recipient, BadgeVerificationFailed::class, function ($notification, $channels) use ($recipient, $product) {
                $this->assertSame(['database'], $channels);
                $this->assertTrue($notification->shouldSend($recipient, 'database'));
                $this->assertFalse($notification->shouldSend($recipient, 'mail'));
                $this->assertSame($product->id, $notification->productId);
                $this->assertSame('http_error', $notification->reason);
                $this->assertStringContainsString('HTTP 403', $notification->failureMessage);
                $this->assertStringContainsString('HTTP 403', $notification->toArray($recipient)['message']);

                return true;
            });
        }
        Notification::assertNotSentTo($product->user, BadgeVerificationFailed::class);
    }

    public function test_admin_can_publish_failed_badge_by_changing_publish_date(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response('<html>No badge</html>')]);
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::factory()->create([
            'link' => 'https://93.184.216.34/product',
            'submission_type' => 'badge',
            'approved' => true,
            'is_published' => false,
        ]);

        $this->actingAs($admin)->patch(route('admin.product-approvals.publish-date.update', $product), [
            'published_at' => now()->subDay()->format('Y-m-d'),
        ])->assertSessionHas('success');

        $this->assertTrue($product->refresh()->is_published);
        $this->assertFalse($product->badge_verified);
    }

    public function test_successful_check_records_evidence(): void
    {
        $destination = app(BadgeService::class)->getBadgeDestinationUrl();
        $html = '<a href="'.$destination.'"><img src="https://softwareontheweb.com/images/badge.svg" alt="Featured on Software on the Web"></a>';
        Http::fake(['*' => Http::response($html)]);
        $product = Product::factory()->create([
            'link' => 'https://93.184.216.34/product',
            'badge_placement_url' => 'https://93.184.216.34/product',
            'submission_type' => 'badge',
            'badge_verified' => false,
        ]);

        $result = app(BadgeVerificationManager::class)->verify($product, 'test');

        $this->assertTrue($result['verified'], json_encode($result));
        $attempt = BadgeVerificationAttempt::where('product_id', $product->id)->firstOrFail();
        $this->assertSame(hash('sha256', $html), $attempt->response_hash);
        $this->assertNotNull($attempt->matched_element);
    }

    public function test_failure_counter_can_exceed_tiny_integer_limit(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response('<html>No badge</html>')]);

        $product = Product::factory()->create([
            'link' => 'https://93.184.216.34/product',
            'badge_placement_url' => 'https://93.184.216.34/product',
            'submission_type' => 'badge',
            'badge_consecutive_failures' => 255,
        ]);

        app(BadgeVerificationManager::class)->verify($product, 'test');

        $this->assertSame(256, $product->refresh()->badge_consecutive_failures);
    }

    public function test_each_failure_emails_the_specific_reason(): void
    {
        $destination = app(BadgeService::class)->getBadgeDestinationUrl();
        $image = '<img src="https://softwareontheweb.com/images/badge.png" alt="Featured on Software on the Web">';
        $cases = [
            ['<a href="'.$destination.'" rel="nofollow noopener">'.$image.'</a>', 200, 'nofollow', 'Remove only nofollow'],
            ['<a href="https://wrong.example">'.$image.'</a>', 200, 'incorrect_backlink', 'Please wrap the badge image'],
            [$image, 200, 'incorrect_backlink', 'Please wrap the badge image'],
            ['<html>No badge</html>', 200, 'badge_missing', 'initial HTML'],
            ['<a href="https://other.example"><img src="https://other.example/other-badge.svg"></a>', 200, 'badge_missing', 'initial HTML'],
            ['Forbidden', 403, 'http_error', 'HTTP 403'],
            [null, null, 'connection_failed', 'We could not connect'],
        ];

        foreach ($cases as [$html, $status, $reason, $wording]) {
            Mail::fake();
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake(['*' => $html === null ? Http::failedConnection() : Http::response($html, $status)]);
            $product = Product::factory()->create([
                'link' => 'https://93.184.216.34/product',
                'submission_type' => 'badge',
                'is_published' => false,
                'badge_verified' => true,
            ]);

            $result = app(BadgeVerificationManager::class)->verify($product, 'test');

            $this->assertSame($reason, $result['reason']);
            $this->assertFalse($product->refresh()->is_published);
            Mail::assertSent(BadgeWarningMail::class, function (BadgeWarningMail $mail) use ($wording) {
                $this->assertStringContainsString($wording, $mail->render());
                $this->assertStringNotContainsString('badge is missing from your website', $mail->render());

                return ! $mail->wasPublished;
            });
            $this->assertDatabaseHas('badge_verification_attempts', [
                'product_id' => $product->id,
                'message' => $result['message'],
            ]);
        }
    }

    public function test_invalid_url_emails_actionable_reason_without_fetching(): void
    {
        Mail::fake();
        Http::fake();
        $product = Product::factory()->create(['link' => 'http://localhost', 'submission_type' => 'badge', 'is_published' => false]);

        $result = app(BadgeVerificationManager::class)->verify($product, 'test');

        $this->assertSame('invalid_url', $result['reason']);
        Http::assertNothingSent();
        Mail::assertSent(BadgeWarningMail::class, function (BadgeWarningMail $mail) {
            $this->assertStringContainsString('Please update the badge placement URL', $mail->render());

            return true;
        });
    }

    public function test_warning_resets_after_success_and_valid_badge_overrides_nofollow_badge(): void
    {
        Mail::fake();
        $service = app(BadgeService::class);
        $manager = app(BadgeVerificationManager::class);
        $product = Product::factory()->create(['link' => 'https://93.184.216.34/product', 'submission_type' => 'badge', 'is_published' => false]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response('<html>No badge</html>')]);
        $manager->verify($product, 'test');
        $manager->verify($product, 'test');
        Mail::assertSent(BadgeWarningMail::class, 1);

        $image = '<img src="https://softwareontheweb.com/images/badge.png">';
        foreach (['', 'noopener noreferrer', 'dofollow'] as $rel) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake(['*' => Http::response('<a href="'.$service->getBadgeDestinationUrl().'" rel="nofollow">'.$image.'</a><a href="'.$service->getBadgeDestinationUrl().'" rel="'.$rel.'">'.$image.'</a>')]);
            $this->assertTrue($manager->verify($product, 'test')['verified']);
            $this->assertNull($product->refresh()->badge_warning_sent_at);
        }
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response('<html>No badge</html>')]);
        $manager->verify($product, 'test');
        Mail::assertSent(BadgeWarningMail::class, 2);
    }
}
