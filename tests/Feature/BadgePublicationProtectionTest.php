<?php

namespace Tests\Feature;

use App\Mail\BadgeWarningMail;
use App\Models\BadgeVerificationAttempt;
use App\Models\Product;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\BadgeVerificationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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

    public function test_manual_failed_check_unpublishes_badge_product(): void
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
        $this->assertFalse($product->is_published);
        $this->assertFalse($product->badge_verified);
        Mail::assertSent(BadgeWarningMail::class, fn (BadgeWarningMail $mail) => $mail->wasPublished);
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
}
