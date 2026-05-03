<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_products_search_matches_domain_and_owner_email(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $ownerOne = User::factory()->create(['name' => 'Acme Owner', 'email' => 'owner@acme.com']);
        $ownerTwo = User::factory()->create(['name' => 'Beta Owner', 'email' => 'owner@beta.io']);

        $acme = Product::factory()->create([
            'name' => 'Acme Suite',
            'link' => 'https://www.acme.com',
            'user_id' => $ownerOne->id,
        ]);

        $beta = Product::factory()->create([
            'name' => 'Beta Stack',
            'link' => 'https://beta.io',
            'user_id' => $ownerTwo->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['q' => 'acme.com']));

        $response->assertOk();
        $response->assertSee('Acme Suite');
        $response->assertSee('acme.com');
        $this->assertSame(
            ['Acme Suite'],
            $response->viewData('products')->getCollection()->pluck('name')->all()
        );

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['q' => 'owner@beta.io']));

        $response->assertOk();
        $response->assertSee('Beta Stack');
        $this->assertSame(
            ['Beta Stack'],
            $response->viewData('products')->getCollection()->pluck('name')->all()
        );
    }

    public function test_admin_can_reassign_product_owner_from_admin_products_flow(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $currentOwner = User::factory()->create(['email' => 'old@example.com']);
        $newOwner = User::factory()->create(['email' => 'new@example.com']);

        $product = Product::factory()->create([
            'user_id' => $currentOwner->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.products.assign.store'), [
            'product_id' => $product->id,
            'user_id' => $newOwner->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame($newOwner->id, $product->fresh()->user_id);
    }

    public function test_admin_products_autocomplete_returns_matching_products(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $ownerOne = User::factory()->create(['name' => 'Acme Owner', 'email' => 'owner@acme.com']);
        $ownerTwo = User::factory()->create(['name' => 'Beta Owner', 'email' => 'owner@beta.io']);

        Product::factory()->create([
            'name' => 'Acme Suite',
            'link' => 'https://www.acme.com',
            'user_id' => $ownerOne->id,
        ]);

        Product::factory()->create([
            'name' => 'Beta Stack',
            'link' => 'https://beta.io',
            'user_id' => $ownerTwo->id,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.products.autocomplete', ['q' => 'acme']));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'name' => 'Acme Suite',
                'domain' => 'acme.com',
                'owner_name' => 'Acme Owner',
                'owner_email' => 'owner@acme.com',
            ]);
    }

    public function test_admin_products_page_can_show_selected_product_inline(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $owner = User::factory()->create(['name' => 'Selected Owner', 'email' => 'owner@selected.com']);
        $product = Product::factory()->create([
            'name' => 'Selected Product',
            'link' => 'https://selected.com',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index', [
            'q' => 'Selected',
            'selected_product_id' => $product->id,
        ]));

        $response->assertOk();
        $response->assertSee('Selected product');
        $response->assertSee('Loaded inline from the search results');
        $response->assertSee('Selected Product');
        $response->assertSee('Selected Owner');
    }
}
