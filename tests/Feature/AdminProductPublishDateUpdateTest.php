<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductPublishDateUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_reschedule_a_published_product_and_clear_both_weeks(): void
    {
        Carbon::setTestNow('2026-05-11 12:00:00');
        Storage::fake('local');
        Storage::disk('local')->put('settings.json', json_encode(['product_publish_time' => '04:00']));
        $admin = $this->admin();
        $product = Product::factory()->create([
            'approved' => true, 'is_published' => true, 'published_at' => '2026-05-11 04:00:00',
        ]);
        $oldKey = 'product_list_week_2026_20_2026-05-11';
        $newKey = 'product_list_week_2026_21_2026-05-11';
        Cache::put($oldKey, 'stale');
        Cache::put($newKey, 'stale');

        $this->actingAs($admin)->patch(route('admin.product-approvals.publish-date.update', $product), [
            'published_at' => '2026-05-18',
        ])->assertRedirect()->assertSessionHas('success');

        $product->refresh();
        $this->assertFalse($product->is_published);
        $this->assertSame('2026-05-18 04:00:00', $product->published_at->format('Y-m-d H:i:s'));
        $this->assertNull(Cache::get($oldKey));
        $this->assertNull(Cache::get($newKey));
    }

    public function test_due_date_publishes_only_the_target_product(): void
    {
        Carbon::setTestNow('2026-05-11 12:00:00');
        Storage::fake('local');
        $product = Product::factory()->create([
            'approved' => true, 'is_published' => false, 'published_at' => '2026-05-18 07:00:00',
        ]);
        $untouched = Product::factory()->create([
            'approved' => true, 'is_published' => false, 'published_at' => '2026-05-18 07:00:00',
        ]);

        $this->actingAs($this->admin())->patch(route('admin.product-approvals.publish-date.update', $product), [
            'published_at' => '2026-05-11',
        ])->assertSessionHas('success');

        $this->assertTrue($product->fresh()->is_published);
        $this->assertFalse($untouched->fresh()->is_published);
    }

    public function test_invalid_dates_and_unapproved_products_are_rejected(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['approved' => true]);
        $this->actingAs($admin)->patch(route('admin.product-approvals.publish-date.update', $product), [
            'published_at' => '2026-02-30',
        ])->assertSessionHasErrors('published_at');

        $product->update(['approved' => false]);
        $this->patch(route('admin.product-approvals.publish-date.update', $product), [
            'published_at' => '2026-05-18',
        ])->assertNotFound();
    }

    public function test_non_admin_cannot_change_publish_dates(): void
    {
        $product = Product::factory()->create(['approved' => true]);
        $this->actingAs(User::factory()->create())->patch(route('admin.product-approvals.publish-date.update', $product), [
            'published_at' => '2026-05-18',
        ])->assertForbidden();
    }

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
