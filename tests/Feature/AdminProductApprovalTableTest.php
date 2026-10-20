<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductApprovalTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow('2026-10-01 12:00:00');
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_failed_badge_filter_and_search_apply_before_pagination(): void
    {
        Product::factory()->create(['name' => 'Failed Target', 'approved' => true, 'is_published' => false, 'published_at' => '2026-10-05 07:00:00', 'submission_type' => 'badge', 'badge_verified' => false, 'badge_consecutive_failures' => 1]);
        Product::factory()->create(['name' => 'Pending Badge', 'approved' => true, 'is_published' => false, 'published_at' => '2026-10-05 07:00:00', 'submission_type' => 'badge', 'badge_verified' => false, 'badge_consecutive_failures' => 0]);
        $this->get(route('admin.product-approvals.index', ['status' => 'failed_badge', 'search' => 'Target', 'per_page' => 999]))
            ->assertOk()->assertSee('Failed Target')->assertDontSee('Pending Badge')
            ->assertSee('Oct 5, 2026 (1 scheduled)')->assertViewHas('perPage', 20);
    }

    public function test_top_bar_scheduled_count_excludes_unverified_badge_submissions(): void
    {
        $scheduled = ['approved' => true, 'is_published' => false, 'published_at' => now()->addWeek()];
        foreach ([
            ['submission_type' => 'badge', 'badge_verified' => true],
            ['submission_type' => 'paid', 'badge_verified' => false],
            ['submission_type' => 'free', 'badge_verified' => false],
            ['submission_type' => 'badge', 'badge_verified' => false, 'badge_consecutive_failures' => 1],
            ['submission_type' => 'badge', 'badge_verified' => false, 'badge_consecutive_failures' => 0],
            ['submission_type' => 'badge', 'badge_verified' => true, 'approved' => false],
            ['submission_type' => 'badge', 'badge_verified' => true, 'is_published' => true],
            ['submission_type' => 'badge', 'badge_verified' => true, 'published_at' => null],
        ] as $attributes) {
            Product::factory()->create(array_merge($scheduled, $attributes));
        }

        $scheduledCount = null;
        View::composer('components.top-bar', function ($view) use (&$scheduledCount) {
            $scheduledCount = $view->getData()['scheduledApprovalCount'];
        });

        $this->get(route('admin.product-approvals.index'))
            ->assertOk()
            ->assertViewHas('scheduledProductsCount', 3)
            ->assertViewHas('shownProductsCount', 1)
            ->assertViewHas('failedBadgeProductsCount', 1)
            ->assertSee('class="approval-summary"', false);

        $this->assertSame(3, $scheduledCount);

        $this->get(route('admin.product-approvals.index', ['status' => 'scheduled']))
            ->assertOk()
            ->assertViewHas('approvedProducts', fn ($products) => $products->total() === 3);
    }

    public function test_submitter_type_filter_separates_admin_and_non_admin_products(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $member = User::factory()->create();
        Product::factory()->create(['name' => 'Admin Product', 'user_id' => $admin->id, 'approved' => true, 'is_published' => true, 'published_at' => now()]);
        Product::factory()->create(['name' => 'Member Product', 'user_id' => $member->id, 'approved' => true, 'is_published' => true, 'published_at' => now()]);

        $this->get(route('admin.product-approvals.index', ['submitter_type' => 'admin']))
            ->assertOk()->assertSee('Admin Product')->assertDontSee('Member Product');

        $this->get(route('admin.product-approvals.index', ['submitter_type' => 'non_admin']))
            ->assertOk()->assertSee('Member Product')->assertDontSee('Admin Product');
    }

    public function test_bulk_reschedule_changes_only_selected_approved_products(): void
    {
        $selected = Product::factory()->count(2)->create(['approved' => true, 'is_published' => true, 'published_at' => now()]);
        $untouched = Product::factory()->create(['approved' => true, 'is_published' => true, 'published_at' => now()]);
        $pending = Product::factory()->create(['approved' => false, 'is_published' => false, 'published_at' => null]);
        $this->post(route('admin.product-approvals.bulk-manage'), [
            'products' => [...$selected->pluck('id'), $pending->id], 'action' => 'reschedule', 'published_at' => '2026-10-05',
        ])->assertSessionHas('success', '2 product(s) updated. 1 skipped.');
        foreach ($selected as $product) {
            $this->assertFalse($product->fresh()->is_published);
            $this->assertSame('2026-10-05 07:00:00', $product->fresh()->published_at->format('Y-m-d H:i:s'));
        }
        $this->assertTrue($untouched->fresh()->is_published);
        $this->assertNull($pending->fresh()->published_at);
    }

    public function test_bulk_disapprove_and_invalid_actions(): void
    {
        $product = Product::factory()->create(['approved' => true]);
        $this->post(route('admin.product-approvals.bulk-manage'), ['products' => [$product->id], 'action' => 'delete'])->assertSessionHasErrors('action');
        $this->assertTrue($product->fresh()->approved);
        $this->post(route('admin.product-approvals.bulk-manage'), ['products' => [$product->id], 'action' => 'disapprove'])->assertSessionHas('success');
        $this->assertFalse($product->fresh()->approved);
    }

    public function test_non_admin_cannot_bulk_manage(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.product-approvals.bulk-manage'), ['products' => [1], 'action' => 'disapprove'])->assertForbidden();
    }

    public function test_admin_can_delete_pending_scheduled_and_published_products(): void
    {
        foreach ([[false, false], [true, false], [true, true]] as [$approved, $published]) {
            $product = Product::factory()->create([
                'approved' => $approved,
                'is_published' => $published,
                'published_at' => $approved ? now()->addWeek() : null,
            ]);
            $this->get(route('admin.product-approvals.index'))
                ->assertOk()->assertSee(route('admin.product-approvals.destroy', $product), false);
            $this->delete(route('admin.product-approvals.destroy', $product))
                ->assertRedirect(route('admin.product-approvals.index'))
                ->assertSessionHas('success', 'Product deleted successfully.');
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
        }
    }

    public function test_non_admin_cannot_delete_products_from_approvals(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create())
            ->delete(route('admin.product-approvals.destroy', $product))->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
