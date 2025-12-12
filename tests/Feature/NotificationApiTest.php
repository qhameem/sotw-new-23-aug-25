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