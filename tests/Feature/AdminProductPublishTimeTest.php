<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductPublishTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_specific_date_approval_uses_configured_publish_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00', 'UTC'));

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        Storage::disk('local')->put('settings.json', json_encode([
            'product_publish_time' => '04:00',
        ]));

        $product = Product::factory()->create([
            'approved' => false,
            'is_published' => false,
            'published_at' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.product-approvals.approve', $product), [
            'publish_option' => 'specific_date',
            'published_at' => '2026-05-11',
        ]);

        $response->assertRedirect();

        $product->refresh();

        $this->assertTrue($product->approved);
        $this->assertFalse($product->is_published);
        $this->assertSame('2026-05-11 04:00:00', $product->published_at?->copy()->timezone('UTC')->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_bulk_approval_uses_configured_publish_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00', 'UTC'));

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        Storage::disk('local')->put('settings.json', json_encode([
            'product_publish_time' => '04:00',
        ]));

        $products = Product::factory()->count(2)->create([
            'approved' => false,
            'is_published' => false,
            'published_at' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.product-approvals.bulk-approve'), [
            'products' => $products->pluck('id')->all(),
            'bulk_published_at' => '2026-05-11',
        ]);

        $response->assertRedirect();

        foreach ($products as $product) {
            $product->refresh();

            $this->assertTrue($product->approved);
            $this->assertFalse($product->is_published);
            $this->assertSame('2026-05-11 04:00:00', $product->published_at?->copy()->timezone('UTC')->format('Y-m-d H:i:s'));
        }

        Carbon::setTestNow();
    }
}
