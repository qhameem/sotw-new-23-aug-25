<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SocialLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSocialLinksSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_x_profile_without_overwriting_other_settings(): void
    {
        Storage::fake('local');
        $this->assertSame('https://x.com/qHameem', SocialLinks::xProfileUrl());
        Storage::disk('local')->put('settings.json', json_encode(['premium_product_spots' => 8]));
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.settings.storeSocialLinks'), [
            'x_profile_url' => 'https://x.com/updated_account',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('https://x.com/updated_account', SocialLinks::xProfileUrl());
        $this->assertSame(8, json_decode(Storage::disk('local')->get('settings.json'), true)['premium_product_spots']);

        $this->post(route('admin.settings.storeSocialLinks'), [
            'x_profile_url' => 'https://example.com/account',
        ])->assertSessionHasErrors('x_profile_url');
        $this->assertSame('https://x.com/updated_account', SocialLinks::xProfileUrl());
    }

    public function test_non_admin_cannot_update_x_profile(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create())->post(route('admin.settings.storeSocialLinks'), [
            'x_profile_url' => 'https://x.com/other_account',
        ])->assertForbidden();
        Storage::disk('local')->assertMissing('settings.json');
    }
}
