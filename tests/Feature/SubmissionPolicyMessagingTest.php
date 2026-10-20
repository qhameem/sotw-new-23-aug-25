<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductSubmissionConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionPolicyMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_is_available_before_login_and_uses_current_settings(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('settings.json', json_encode([
            'premium_launch_price_cents' => 1900,
            'product_publish_time' => '09:30',
        ]));

        $this->get(route('products.create'))
            ->assertOk()
            ->assertSeeInOrder(['Before you submit a product', 'Please log in to add your product'])
            ->assertSee('Premium Launch — $19 USD, one-time')
            ->assertSee('09:30 UTC')
            ->assertSee('homepage placement is encouraged', false)
            ->assertSee(route('refund-policy'));
    }

    public function test_refund_terms_and_policy_links_are_public(): void
    {
        $this->get(route('refund-policy'))->assertOk()
            ->assertSee('14 days')
            ->assertSee('before delivering paid benefits')
            ->assertSee('hello@softwareontheweb.com');

        foreach (['about', 'faq', 'legal'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee(route('refund-policy'))
                ->assertSee('manually reviews every tool');
        }
    }

    public function test_badge_guide_accepts_public_pages_and_explains_removal(): void
    {
        $this->get('/get-the-badge')->assertOk()
            ->assertSee('Any publicly accessible page is acceptable')
            ->assertSee('homepage placement is encouraged')
            ->assertSee('automatically removes your listing from public view');
    }

    public function test_retired_fast_track_urls_redirect_and_checkout_cannot_charge(): void
    {
        $this->get('/fast-track')->assertRedirect('/add-product')->assertStatus(301);
        $this->get('/fast-track-approval')->assertRedirect('/add-product')->assertStatus(301);
        $this->actingAs(User::factory()->create())->post(route('stripe.checkout'), [
            'product_ids' => '1',
        ])->assertStatus(410);
    }

    public function test_submission_notifications_distinguish_scheduling_from_manual_review(): void
    {
        foreach ([true, false] as $approved) {
            $product = new Product(['name' => 'Example', 'approved' => $approved]);
            $notification = new ProductSubmissionConfirmation($product);
            $user = new User;
            $database = $notification->toArray($user);
            $broadcast = $notification->toBroadcast($user);

            $this->assertSame($database['message'], $broadcast['message']);
            $this->assertStringContainsString(
                $approved ? 'automatically approved for scheduling' : 'awaiting manual review',
                $database['message']
            );
            if ($approved) {
                $this->assertStringContainsString('may withhold or remove unsuitable listings', $database['message']);
            }
        }
    }
}
