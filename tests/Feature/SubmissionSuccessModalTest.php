<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubmissionSuccessModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_a_card_and_the_submitter_gets_the_saved_modal(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'admin']));
        $submitter = User::factory()->create();
        $product = Product::factory()->for($submitter)->create(['submission_type' => 'free']);
        $settingsUrl = route('admin.settings.index');
        $updateUrl = route('admin.settings.submission-success-modal.update');
        $confirmationUrl = route('products.submission.success', $product);
        $modalSettings = [
            'submission_modal_enabled' => '1',
            'submission_modal_delay' => '4',
            'submission_modal_title' => 'Thanks for submitting!',
            'submission_modal_body' => '<b>We received your listing.</b>',
            'submission_modal_ad_type' => 'card',
            'submission_modal_ad_title' => 'Example sponsor',
            'submission_modal_ad_image' => 'https://example.com/logo.png',
            'submission_modal_ad_body' => 'Build and launch your site.',
            'submission_modal_ad_url' => 'https://example.com/',
            'submission_modal_ad_link_label' => 'Visit sponsor',
            'submission_modal_ad_secondary_url' => 'https://example.com/pricing',
            'submission_modal_ad_secondary_label' => 'See pricing',
        ];

        $this->actingAs($submitter)->get($confirmationUrl)->assertOk()->assertDontSee('<dialog', false);
        $this->post($updateUrl, $modalSettings)->assertForbidden();

        $this->actingAs($admin)->post($updateUrl, $modalSettings)
            ->assertRedirect($settingsUrl.'#settings-submission-success-preview')
            ->assertSessionHasNoErrors();
        $this->get($settingsUrl)->assertOk()->assertSee('Thanks for submitting!');
        $this->get(route('admin.settings.submission-success-preview', 'free'))
            ->assertOk()->assertSee('Example sponsor');

        $this->actingAs($submitter)->get($confirmationUrl)->assertOk()
            ->assertSee('Thanks for submitting!')
            ->assertSee('<b>We received your listing.</b>')
            ->assertDontSee('<b>We received your listing.</b>', false)
            ->assertSee('4000')
            ->assertSee('https://example.com/logo.png')
            ->assertSee('Build and launch your site.')
            ->assertSee('https://example.com/pricing')
            ->assertSee('See pricing');
    }

    public function test_admin_can_save_a_banner_and_turn_off_the_modal(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'admin']));
        $updateUrl = route('admin.settings.submission-success-modal.update');
        $previewUrl = route('admin.settings.submission-success-preview', 'badge');
        $banner = [
            'submission_modal_enabled' => '1',
            'submission_modal_delay' => '0',
            'submission_modal_title' => 'Next steps',
            'submission_modal_ad_type' => 'banner',
            'submission_modal_ad_title' => 'Example banner',
            'submission_modal_ad_image' => 'https://example.com/banner.png',
            'submission_modal_ad_url' => 'https://example.com/',
        ];

        $this->actingAs($admin)->post($updateUrl, $banner)->assertSessionHasNoErrors();
        $this->get($previewUrl)->assertOk()
            ->assertSee('alt="Example banner"', false)
            ->assertSee('https://example.com/banner.png');

        $this->post($updateUrl, array_replace($banner, [
            'submission_modal_delay' => '-1',
            'submission_modal_ad_url' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['submission_modal_delay', 'submission_modal_ad_url']);

        $this->post($updateUrl, array_replace($banner, [
            'submission_modal_enabled' => '0',
            'submission_modal_action' => 'preview',
        ]))->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.settings.submission-success-preview', 'free'));

        $this->get($previewUrl)->assertOk()->assertSee('alt="Example banner"', false);
        $this->get(route('admin.settings.index'))->assertOk()
            ->assertSee('Draft saved. Available in the admin previews.');

        $submitter = User::factory()->create();
        $product = Product::factory()->for($submitter)->create(['submission_type' => 'free']);
        $this->actingAs($submitter)->get(route('products.submission.success', $product))
            ->assertOk()->assertDontSee('<dialog', false);
    }
    public function test_admin_can_save_and_preview_a_sponsored_offer(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'admin']));
        $preview = route('admin.settings.submission-success-preview', 'free');

        $this->actingAs($admin)->post(route('admin.settings.submission-success-modal.update'), [
            'submission_modal_action' => 'preview',
            'submission_modal_enabled' => '0',
            'submission_modal_delay' => '0',
            'submission_modal_title' => 'Get listed on 200+ directories',
            'submission_modal_ad_type' => 'offer',
            'submission_modal_ad_title' => 'Example sponsor',
            'submission_modal_ad_image' => 'https://example.com/logo.png',
            'submission_modal_ad_url' => 'https://example.com/plans',
            'submission_modal_ad_link_label' => 'See plans',
            'submission_modal_offer_highlight' => '200+',
            'submission_modal_offer_benefits' => "Manual submission\nScreenshot proof",
            'submission_modal_offer_bullet' => 'circled-check',
            'submission_modal_offer_promo' => 'Launch offer',
            'submission_modal_offer_code' => 'LAUNCH',
            'submission_modal_offer_expiry' => '2026-11-30',
            'submission_modal_offer_accent' => '#4f46e5',
            'submission_modal_offer_width' => 'wide',
            'submission_modal_offer_button_width' => 'full',
        ])->assertSessionHasNoErrors()->assertRedirect($preview);

        $saved = Storage::disk('local')->json('submission-success-modal.json');
        $this->assertSame('LAUNCH', $saved['offer_code']);
        $this->assertSame('circled-check', $saved['offer_bullet']);
        $this->assertSame('2026-11-30', $saved['offer_expiry']);
        $this->get($preview)->assertOk()
            ->assertSee('max-w-2xl')
            ->assertSee('https://example.com/plans')
            ->assertSee('Screenshot proof')
            ->assertSee('#4f46e5');
    }

}
