<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductCollectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_can_save_a_product_to_multiple_collections_including_default_and_new_lists()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->postJson(route('products.collections.sync', $product), [
            'collections' => [
                [
                    'default_name' => 'Favorites',
                    'comment' => 'Keep this handy.',
                ],
                [
                    'default_name' => 'Saved for Later',
                    'comment' => 'Review next week.',
                ],
            ],
            'new_collection' => [
                'name' => 'Pricing Research',
                'visibility' => 'private',
                'comment' => 'Useful for competitor notes.',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'is_saved' => true,
                'saved_collection_count' => 3,
            ]);

        $this->assertDatabaseHas('product_collections', [
            'user_id' => $user->id,
            'name' => 'Favorites',
            'visibility' => 'public',
        ]);

        $this->assertDatabaseHas('product_collections', [
            'user_id' => $user->id,
            'name' => 'Saved for Later',
            'visibility' => 'public',
        ]);

        $this->assertDatabaseHas('product_collections', [
            'user_id' => $user->id,
            'name' => 'Pricing Research',
            'visibility' => 'private',
        ]);

        $favorites = ProductCollection::query()->where('user_id', $user->id)->where('name', 'Favorites')->firstOrFail();
        $savedForLater = ProductCollection::query()->where('user_id', $user->id)->where('name', 'Saved for Later')->firstOrFail();
        $research = ProductCollection::query()->where('user_id', $user->id)->where('name', 'Pricing Research')->firstOrFail();

        $this->assertDatabaseHas('product_collection_items', [
            'product_collection_id' => $favorites->id,
            'product_id' => $product->id,
            'comment' => 'Keep this handy.',
        ]);

        $this->assertDatabaseHas('product_collection_items', [
            'product_collection_id' => $savedForLater->id,
            'product_id' => $product->id,
            'comment' => 'Review next week.',
        ]);

        $this->assertDatabaseHas('product_collection_items', [
            'product_collection_id' => $research->id,
            'product_id' => $product->id,
            'comment' => 'Useful for competitor notes.',
        ]);
    }

    #[Test]
    public function syncing_collections_removes_a_product_from_lists_that_are_no_longer_selected()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $keepCollection = ProductCollection::factory()->for($user)->create(['name' => 'Keep']);
        $removeCollection = ProductCollection::factory()->for($user)->create(['name' => 'Remove']);

        $keepCollection->items()->create([
            'product_id' => $product->id,
            'comment' => 'Still useful.',
        ]);

        $removeCollection->items()->create([
            'product_id' => $product->id,
            'comment' => 'Drop this one.',
        ]);

        $response = $this->actingAs($user)->postJson(route('products.collections.sync', $product), [
            'collections' => [
                [
                    'id' => $keepCollection->id,
                    'comment' => 'Still useful.',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'is_saved' => true,
                'saved_collection_count' => 1,
            ]);

        $this->assertDatabaseHas('product_collection_items', [
            'product_collection_id' => $keepCollection->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseMissing('product_collection_items', [
            'product_collection_id' => $removeCollection->id,
            'product_id' => $product->id,
        ]);
    }

    #[Test]
    public function a_user_can_create_update_and_delete_their_collection()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('collections.store'), [
                'name' => 'Launch Week',
                'visibility' => 'public',
            ])
            ->assertRedirect(route('collections.index'));

        $collection = ProductCollection::query()->where('user_id', $user->id)->where('name', 'Launch Week')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('collections.update', $collection), [
                'name' => 'Launch Week Picks',
                'visibility' => 'private',
            ])
            ->assertRedirect();

        $collection->refresh();

        $this->assertSame('Launch Week Picks', $collection->name);
        $this->assertSame('private', $collection->visibility);

        $this->actingAs($user)
            ->delete(route('collections.destroy', $collection))
            ->assertRedirect(route('collections.index'));

        $this->assertDatabaseMissing('product_collections', [
            'id' => $collection->id,
        ]);
    }

    #[Test]
    public function public_collections_are_visible_to_everyone_but_private_ones_are_owner_only()
    {
        $owner = User::factory()->create();
        $publicCollection = ProductCollection::factory()->for($owner)->create([
            'name' => 'Public Favorites',
            'slug' => null,
            'visibility' => 'public',
        ]);
        $privateCollection = ProductCollection::factory()->for($owner)->create([
            'name' => 'Private Notes',
            'slug' => null,
            'visibility' => 'private',
        ]);

        $owner->refresh();

        $this->assertNotNull($owner->public_handle);

        $this->get(route('collections.show', $publicCollection->publicRouteParameters()))
            ->assertOk()
            ->assertSee('Public Favorites');

        $this->get(route('collections.show', $privateCollection->publicRouteParameters()))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('collections.show', $privateCollection->publicRouteParameters()))
            ->assertOk()
            ->assertSee('Private Notes');
    }

    #[Test]
    public function different_users_can_publish_collections_with_the_same_slug_under_different_handles()
    {
        $firstOwner = User::factory()->create(['name' => 'Alex Johnson']);
        $secondOwner = User::factory()->create(['name' => 'Alex Johnson']);

        $firstCollection = ProductCollection::factory()->for($firstOwner)->create([
            'name' => 'AI Research Stack',
            'slug' => null,
            'visibility' => 'public',
        ]);

        $secondCollection = ProductCollection::factory()->for($secondOwner)->create([
            'name' => 'AI Research Stack',
            'slug' => null,
            'visibility' => 'public',
        ]);

        $firstOwner->refresh();
        $secondOwner->refresh();

        $this->assertSame('ai-research-stack', $firstCollection->slug);
        $this->assertSame('ai-research-stack', $secondCollection->slug);
        $this->assertNotSame($firstOwner->public_handle, $secondOwner->public_handle);

        $this->get(route('collections.show', $firstCollection->publicRouteParameters()))
            ->assertOk()
            ->assertSee('AI Research Stack');

        $this->get(route('collections.show', $secondCollection->publicRouteParameters()))
            ->assertOk()
            ->assertSee('AI Research Stack');
    }

    #[Test]
    public function authenticated_users_can_see_the_save_to_collection_controls_on_product_pages()
    {
        $viewer = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($viewer)
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Save Product')
            ->assertSee('Save Selection');
    }

    #[Test]
    public function authenticated_users_can_view_the_dedicated_collections_management_page()
    {
        $user = User::factory()->create();
        ProductCollection::factory()->for($user)->create([
            'name' => 'Workspace Tools',
            'visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->get(route('collections.index'))
            ->assertOk()
            ->assertSee('Manage your saved products in one place')
            ->assertSee('Workspace Tools');
    }
}
