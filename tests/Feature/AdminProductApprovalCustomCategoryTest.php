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

        Type::insert([
            ['id' => 1, 'name' => 'Category', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Best for', 'created_at' => now(), 'updated_at' => now()],
        ]);

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
}
