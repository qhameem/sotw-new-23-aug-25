<?php

namespace Tests\Feature;

use App\Models\CustomPage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_and_unpublish_a_root_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $data = [
            'slug' => 'startup-directories',
            'title' => 'Startup Directories',
            'body' => '**Directory list** <script>unsafe()</script> [Unsafe](javascript:alert(1))',
            'is_published' => '0',
        ];

        $this->actingAs($admin)->get(route('admin.pages.create'))->assertOk();
        $this->post(route('admin.pages.store'), array_replace($data, ['body' => '']))
            ->assertRedirect(route('admin.pages.index'));
        $this->get(route('admin.pages.index'))->assertOk()->assertSee('Page created.');
        $page = CustomPage::sole();
        $this->assertSame('', $page->body);
        $this->get('/startup-directories')->assertNotFound();

        $edit = route('admin.pages.update', $page);
        $this->put($edit, array_replace($data, ['is_published' => '1']))->assertSessionHasNoErrors();
        $this->get('/startup-directories')
            ->assertOk()
            ->assertSee('<strong>Directory list</strong>', false)
            ->assertDontSee('<script>unsafe()', false)
            ->assertDontSee('href="javascript:alert(1)"', false);

        $this->put($edit, $data)->assertSessionHasNoErrors();
        $this->get('/startup-directories')->assertNotFound();
    }

    public function test_page_slugs_cannot_replace_existing_urls(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $product = Product::factory()->create(['slug' => 'existing-product']);
        $data = ['title' => 'Test page', 'body' => 'Content', 'is_published' => '1'];

        $this->actingAs($admin);
        foreach (['about', 'products', $product->slug] as $slug) {
            $this->post(route('admin.pages.store'), $data + ['slug' => $slug])
                ->assertSessionHasErrors('slug');
        }

        $this->assertDatabaseCount('custom_pages', 0);
        $this->get('/'.$product->slug)
            ->assertStatus(301)
            ->assertRedirect(route('products.show', ['product' => $product->slug]));
    }

    public function test_regular_users_cannot_create_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.pages.store'), [
                'slug' => 'startup-directories',
                'title' => 'Startup Directories',
                'body' => 'Content',
                'is_published' => '1',
            ])
            ->assertForbidden();
    }
}
