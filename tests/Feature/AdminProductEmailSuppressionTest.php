<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductEmailSuppressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_submitting_a_product_does_not_send_email(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        Mail::fake();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Admin Submitted Product',
            'slug' => 'admin-submitted-product',
            'tagline' => 'Fast admin-created listing',
            'product_page_tagline' => 'Fast admin-created listing for the product page',
            'description' => 'Created directly from the admin area.',
            'link' => 'https://admin-submitted.example.com',
        ]);

        $response->assertRedirect(route('admin.products.index'));
        Mail::assertNothingSent();

        $this->assertDatabaseHas('products', [
            'name' => 'Admin Submitted Product',
            'slug' => 'admin-submitted-product',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_approving_a_product_does_not_send_email(): void
    {
        Config::set('queue.default', 'sync');

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $owner = User::factory()->create();
        $product = Product::factory()->for($owner)->create([
            'approved' => false,
            'is_published' => false,
            'published_at' => null,
        ]);

        Mail::fake();

        $response = $this->actingAs($admin)->post(route('admin.product-approvals.approve', $product), [
            'publish_option' => 'now',
        ]);

        $response->assertRedirect();
        Mail::assertNothingSent();

        $product->refresh();
        $owner->refresh();

        $this->assertTrue($product->approved);
        $this->assertTrue($product->is_published);
        $this->assertCount(1, $owner->notifications);
        $this->assertDatabaseHas('email_logs', [
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'status' => 'skipped',
            'message' => 'Approval email suppressed for admin action.',
        ]);
    }
}
