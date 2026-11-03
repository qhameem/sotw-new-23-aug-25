<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bell_only_lists_non_admin_submissions(): void
    {
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $submitter = User::factory()->create();
        $product = \App\Models\Product::factory()->create(['user_id' => $submitter->id]);
        $adminProduct = \App\Models\Product::factory()->create(['user_id' => $admin->id]);

        foreach ([
            [\App\Notifications\ProductSubmitted::class, $product->id, 'User submission'],
            [\App\Notifications\ProductSubmitted::class, $adminProduct->id, 'Admin submission'],
            [\App\Notifications\BadgeVerificationFailed::class, $product->id, 'Badge verification failed test'],
            [\App\Notifications\TestNotification::class, $product->id, 'Other alert'],
        ] as [$type, $productId, $message]) {
            $admin->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'data' => ['product_id' => $productId, 'message' => $message],
            ]);
        }

        $this->actingAs($admin)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'notifications.data')
            ->assertJsonPath('notifications.data.0.data.message', 'User submission')
            ->assertJsonPath('unread_count', 1);

        $this->assertSame(1, (new \App\View\Components\NotificationBell())->unreadCount);

        $this->get(route('admin.product-approvals.index'))
            ->assertOk()
            ->assertSee('Badge verification notifications')
            ->assertSee('Badge verification failed test')
            ->assertSee(route('admin.products.show', $product), false);

        $this->putJson('/api/notifications/read-all')->assertOk();
        $this->assertSame(3, $admin->unreadNotifications()->count());
        $this->getJson('/api/notifications')->assertJsonPath('unread_count', 0);
    }

    public function test_non_admin_bell_retains_other_notification_types(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\ProductApprovedInApp::class,
            'data' => ['message' => 'Product approved'],
        ]);

        $this->actingAs($user)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'notifications.data')
            ->assertJsonPath('unread_count', 1);
    }

    /** @test */
    public function an_authenticated_user_can_fetch_their_notifications()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'notifications',
                'unread_count'
            ]);
    }

    /** @test */
    public function an_unauthenticated_user_cannot_fetch_notifications()
    {
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(401);
    }

    /** @test */
    public function an_authenticated_user_can_mark_a_notification_as_read()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $notification = new DatabaseNotification([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'Test notification'],
        ]);
        $user->notifications()->save($notification);

        $response = $this->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertNotNull($user->notifications()->find($notification->id)->read_at);
    }

    /** @test */
    public function an_authenticated_user_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->notify(new \App\Notifications\TestNotification());
        $user->notify(new \App\Notifications\TestNotification());

        $response = $this->putJson('/api/notifications/read-all');

        $response->assertStatus(200);
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }
}
