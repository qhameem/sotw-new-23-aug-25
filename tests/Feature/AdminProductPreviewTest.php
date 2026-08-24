<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_an_unapproved_product_with_noindex_protection(): void
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::factory()->create([
            'name' => 'Pending Preview Product',
            'slug' => 'pending-preview-product',
            'approved' => false,
            'is_published' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.product-approvals.preview', $product));

        $response->assertOk();
        $response->assertSee('Pending Preview Product');
        $response->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_unapproved_product_remains_unavailable_on_the_public_product_route(): void
    {
        $product = Product::factory()->create([
            'slug' => 'private-pending-product',
            'approved' => false,
            'is_published' => false,
        ]);

        $this->get(route('products.show', $product->slug))->assertNotFound();
    }

    public function test_approval_card_links_to_the_admin_preview(): void
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::factory()->create([
            'approved' => false,
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.product-approvals.index'))
            ->assertOk()
            ->assertSee(route('admin.product-approvals.preview', $product), false)
            ->assertSee('Preview page');
    }
}
