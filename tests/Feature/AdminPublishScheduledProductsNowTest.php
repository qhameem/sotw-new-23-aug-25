<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPublishScheduledProductsNowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_selected_scheduled_products_immediately(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 05:30:00', 'UTC'));

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $selected = Product::factory()->count(2)->create([
            'approved' => true,
            'is_published' => false,
            'published_at' => Carbon::parse('2026-05-12 04:00:00', 'UTC'),
        ]);

        $untouched = Product::factory()->create([
            'approved' => true,
            'is_published' => false,
            'published_at' => Carbon::parse('2026-05-13 04:00:00', 'UTC'),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.product-approvals.publish-scheduled-now'), [
            'publish_scope' => 'selected',
            'products' => $selected->pluck('id')->all(),
        ]);

        $response->assertRedirect();

        foreach ($selected as $product) {
            $product->refresh();

            $this->assertTrue($product->is_published);
            $this->assertSame('2026-05-11 05:30:00', $product->published_at?->copy()->timezone('UTC')->format('Y-m-d H:i:s'));
        }

        $untouched->refresh();
        $this->assertFalse($untouched->is_published);
        $this->assertSame('2026-05-13 04:00:00', $untouched->published_at?->copy()->timezone('UTC')->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_admin_can_publish_all_scheduled_products_immediately(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 05:45:00', 'UTC'));

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $scheduledProducts = Product::factory()->count(3)->create([
            'approved' => true,
            'is_published' => false,
            'published_at' => Carbon::parse('2026-05-14 04:00:00', 'UTC'),
        ]);

        Product::factory()->create([
            'approved' => true,
            'is_published' => true,
            'published_at' => Carbon::parse('2026-05-10 04:00:00', 'UTC'),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.product-approvals.publish-scheduled-now'), [
            'publish_scope' => 'all',
        ]);

        $response->assertRedirect();

        foreach ($scheduledProducts as $product) {
            $product->refresh();

            $this->assertTrue($product->is_published);
            $this->assertSame('2026-05-11 05:45:00', $product->published_at?->copy()->timezone('UTC')->format('Y-m-d H:i:s'));
        }

        Carbon::setTestNow();
    }
}
