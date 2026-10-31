<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_pages_share_the_layout_and_highlight_the_current_section(): void
    {
        Storage::fake('local');
        Http::fake();
        $admin = User::factory()->create(['name' => 'Workspace Admin']);
        $admin->assignRole(Role::findOrCreate('admin'));
        $this->actingAs($admin);

        foreach (config('admin-navigation.primary') as $item) {
            $response = $this->get(route($item['route']));
            $response->assertOk()->assertSee('approval-shell', false)->assertSee('Admin navigation');
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $links = (new \DOMXPath($document))->query('//nav[contains(@class,"approval-navigation")]/a[@aria-current="page"]');
            $this->assertCount(1, $links);
            $this->assertSame(route($item['route']), $links->item(0)->getAttribute('href'));
        }
    }

    public function test_unverified_admin_can_open_the_sidebar_dashboard(): void
    {
        $admin = User::factory()->unverified()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Catalog & administration', false)
            ->assertSee(route('admin.dashboard'), false);

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_admin_dashboard_requires_an_admin_account(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_child_pages_keep_the_parent_navigation_and_actions(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $this->actingAs($admin);
        $response = $this->get(route('admin.categories.create'));
        $response->assertOk()->assertSee('approval-shell', false)->assertSee(route('admin.categories.store'), false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $link = (new \DOMXPath($document))->query('//nav[contains(@class,"approval-navigation")]/a[@aria-current="page"]')->item(0);
        $this->assertSame(route('admin.categories.index'), $link->getAttribute('href'));

        $this->get(route('admin.categories.index'))->assertOk()
            ->assertSee(route('admin.categories.create'), false)
            ->assertSee(route('admin.category-types.create'), false);
    }

    public function test_users_are_paginated_with_roles_and_product_counts(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        User::factory()->count(25)->create();
        Product::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk()->assertViewHas('userCount', 26)->assertSee('Registered users');
        $users = $response->viewData('users');
        $this->assertSame(20, $users->count());
        $this->assertSame(26, $users->total());
        $this->assertTrue($users->first()->relationLoaded('roles'));

        $response = $this->get(route('admin.users.index', ['page' => 2]));
        $response->assertOk();
        $this->assertSame(6, $response->viewData('users')->count());
        $allUsers = $users->getCollection()->concat($response->viewData('users')->getCollection());
        $this->assertSame(26, $allUsers->pluck('id')->unique()->count());
        $this->assertSame(1, $allUsers->firstWhere('id', $admin->id)->products_count);
    }

    public function test_member_dashboard_keeps_the_public_layout_and_admin_pages_stay_protected(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()->assertDontSee('aria-label="Admin navigation"', false);
        $this->get(route('admin.users.index'))->assertForbidden();
    }
}
