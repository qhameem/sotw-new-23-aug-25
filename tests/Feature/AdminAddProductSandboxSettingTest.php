<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAddProductSandboxSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_admin_can_save_add_product_sandbox_setting(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.settings.storeAdminSandboxMode'), [
            'admin_add_product_sandbox_enabled' => '0',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin sandbox mode saved successfully.');

        $savedSettings = json_decode(Storage::disk('local')->get('settings.json'), true);

        $this->assertFalse($savedSettings['admin_add_product_sandbox_enabled'] ?? true);
    }

    public function test_add_product_page_receives_disabled_admin_sandbox_flag(): void
    {
        $admin = $this->createAdmin();

        Storage::disk('local')->put('settings.json', json_encode([
            'admin_add_product_sandbox_enabled' => false,
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($admin)->get(route('products.create'));

        $response->assertOk();
        $response->assertSee('data-admin-sandbox-enabled="false"', false);
    }

    public function test_admin_sandbox_submission_is_rejected_when_setting_is_disabled(): void
    {
        $admin = $this->createAdmin();

        Storage::disk('local')->put('settings.json', json_encode([
            'admin_add_product_sandbox_enabled' => false,
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($admin)->postJson(route('products.store'), [
            'sandbox_mode' => '1',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Sandbox mode is disabled in admin settings.',
            ]);

        $this->assertSame(0, Product::count());
    }

    private function createAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
