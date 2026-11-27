<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSubmissionSuccessPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_both_submission_confirmations_from_settings(): void
    {
        Storage::fake('local');
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $freeUrl = route('admin.settings.submission-success-preview', 'free');
        $badgeUrl = route('admin.settings.submission-success-preview', 'badge');

        $this->get(route('admin.settings.index'))->assertOk()->assertSee($freeUrl)->assertSee($badgeUrl);
        $this->get($freeUrl)->assertOk()
            ->assertSee('Admin preview with sample submission data.')
            ->assertSee('Your product has been submitted for review.')
            ->assertSee('aria-disabled="true"', false);
        $this->get($badgeUrl)->assertOk()
            ->assertSee('Keep This Badge On Your Site');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_non_admin_cannot_preview_submission_confirmation(): void
    {
        $url = route('admin.settings.submission-success-preview', 'free');

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }
}
