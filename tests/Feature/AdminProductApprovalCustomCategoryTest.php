<?php

namespace Tests\Feature;

use App\Models\CustomCategorySubmission;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductApprovalCustomCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_custom_category_approval_requests_are_idempotent()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        Type::updateOrCreate(['id' => 1], ['name' => 'Category']);
        Type::updateOrCreate(['id' => 3], ['name' => 'Best for']);

        $product = Product::factory()->create();
        $submission = CustomCategorySubmission::create([
            'product_id' => $product->id,
            'type' => 'category',
            'name' => 'Privacy',
            'status' => 'pending',
        ]);

        $payload = [
            'slug' => 'privacy',
            'description' => 'Privacy software helps businesses protect sensitive data without slowing everyday work down.',
            'meta_description' => 'Find privacy software that helps teams reduce risk, protect customer data, and compare practical options before choosing a vendor.',
        ];

        $first = $this->actingAs($admin)->postJson(
            route('admin.product-approvals.approve-custom-category', [$product, $submission]),
            $payload
        );

        $second = $this->actingAs($admin)->postJson(
            route('admin.product-approvals.approve-custom-category', [$product, $submission]),
            $payload
        );

        $first->assertOk()->assertJson(['success' => true]);
        $second->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', ['name' => 'Privacy', 'slug' => 'privacy']);
        $this->assertDatabaseHas('category_product', ['product_id' => $product->id]);
        $this->assertDatabaseHas('custom_category_submissions', [
            'id' => $submission->id,
            'status' => 'approved',
        ]);
    }

    public function test_product_cannot_be_published_while_a_custom_category_is_pending()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $product = Product::factory()->create(['approved' => false, 'is_published' => false]);

        CustomCategorySubmission::create([
            'product_id' => $product->id,
            'type' => 'category',
            'name' => 'Privacy',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.product-approvals.approve', $product), [
            'publish_option' => 'now',
        ]);

        $response->assertSessionHas('error');
        $this->assertFalse($product->fresh()->approved);
        $this->assertFalse($product->fresh()->is_published);
    }

    public function test_approval_page_renders_custom_category_modal_fields()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $product = Product::factory()->create(['approved' => false]);
        $submission = CustomCategorySubmission::create([
            'product_id' => $product->id,
            'type' => 'category',
            'name' => 'Privacy',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.product-approvals.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('data-custom-category-open="'.$product->id.'"', false)
            ->assertSee('custom-category-modal-'.$product->id, false)
            ->assertSee('name="description"', false)
            ->assertSee('name="meta_description"', false)
            ->assertSee(route('admin.product-approvals.approve-custom-category', [$product, $submission]), false);
    }
}
