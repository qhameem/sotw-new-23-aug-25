<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\UserProductUpvote;
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

    public function test_admin_products_page_offers_an_unpublish_action_for_live_products(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        Product::factory()->create([
            'name' => 'Live Product',
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['q' => 'Live Product']));

        $response->assertOk();
        $response->assertSee('Published');
        $response->assertSee('Unpublish product');
    }

    public function test_admin_can_unpublish_a_product_from_admin_products(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $product = Product::factory()->create([
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.unpublish', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success', 'Product unpublished successfully.');
        $this->assertFalse($product->fresh()->is_published);
    }

    public function test_admin_products_page_shows_vote_source_breakdown_for_live_products(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $product = Product::factory()->create([
            'name' => 'Breakdown Product',
            'impressions' => 8,
            'outbound_clicks_count' => 4,
            'votes_count' => 8,
        ]);

        UserProductUpvote::create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $product->id,
        ]);

        UserProductUpvote::create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $product->id,
        ]);

        UserProductUpvote::create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['q' => 'Breakdown Product']));

        $response->assertOk();
        $response->assertSee('Upvote sources');
        $response->assertSee('Where 8 votes came from');
        $response->assertSee('Upvote button');
        $response->assertSee('3');
        $response->assertSee('37.5% of votes');
        $response->assertSee('Views');
        $response->assertSee('25% of votes');
        $response->assertSee('Link clicks');
        $response->assertSee('Base vote');
        $response->assertSee('8 views');
        $response->assertSee('4 link clicks');
    }

    public function test_admin_products_can_sort_by_manual_upvotes(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $lowest = Product::factory()->create(['name' => 'Lowest Manual Upvotes']);
        $highest = Product::factory()->create(['name' => 'Highest Manual Upvotes']);

        UserProductUpvote::create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $highest->id,
        ]);

        UserProductUpvote::create([
            'user_id' => User::factory()->create()->id,
            'product_id' => $highest->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index', [
            'sort_by' => 'user_upvotes_count',
            'sort_dir' => 'desc',
        ]));

        $response->assertOk();
        $response->assertSee('Manual upvotes');
        $this->assertSame(
            ['Highest Manual Upvotes', 'Lowest Manual Upvotes'],
            $response->viewData('products')->getCollection()->take(2)->pluck('name')->all()
        );
    }

    public function test_admin_products_can_sort_by_view_and_click_upvote_sources(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $viewLeader = Product::factory()->create([
            'name' => 'View Vote Leader',
            'impressions' => 8,
            'outbound_clicks_count' => 0,
        ]);

        $clickLeader = Product::factory()->create([
            'name' => 'Click Vote Leader',
            'impressions' => 0,
            'outbound_clicks_count' => 6,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index', [
            'sort_by' => 'view_upvotes',
            'sort_dir' => 'desc',
        ]));

        $response->assertOk();
        $response->assertSee('View upvotes');
        $this->assertSame(
            'View Vote Leader',
            $response->viewData('products')->getCollection()->first()->name
        );

        $response = $this->actingAs($admin)->get(route('admin.products.index', [
            'sort_by' => 'click_upvotes',
            'sort_dir' => 'desc',
        ]));

        $response->assertOk();
        $response->assertSee('Link-click upvotes');
        $this->assertSame(
            'Click Vote Leader',
            $response->viewData('products')->getCollection()->first()->name
        );
    }
}
