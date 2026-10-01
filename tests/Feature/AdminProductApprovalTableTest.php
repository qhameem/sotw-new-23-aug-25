<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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
}
