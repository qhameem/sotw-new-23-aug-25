<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Events\ProductApproved;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ProductApprovedInApp;

class TestEventNotificationSystem extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Use sync queue driver to process jobs immediately
        \Illuminate\Support\Facades\Config::set('queue.default', 'sync');
    }

    public function test_product_approved_event_triggers_notification()
    {
        Event::fake();
        // Don't fake notifications since we want to test the actual listener

        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create([
            'name' => 'Test Product',
            'slug' => 'test-product-approved'
        ]);

        // Dispatch the ProductApproved event
        event(new ProductApproved($product, $user));

        // Assert that the event was dispatched
        Event::assertDispatched(ProductApproved::class);

        // With sync queue, jobs are processed immediately
        // Refresh the user to get fresh notifications
        $user->refresh();
        
        // Check that the notification was stored in the database
        $this->assertCount(1, $user->notifications);
        
        $notification = $user->notifications->first();
        $this->assertInstanceOf(ProductApprovedInApp::class, $notification);
        $this->assertStringContainsString('has been approved', $notification->data['message']);
    }

    public function test_product_approved_stores_notification_in_database()
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create([
            'name' => 'Test Product',
            'slug' => 'test-product-approved-db'
        ]);

        // Manually trigger the notification
        $user->notify(new ProductApprovedInApp($product));

        // Check that the notification was stored in the database
        $this->assertCount(1, $user->notifications);
        
        $notification = $user->notifications->first();
        $this->assertStringContainsString('has been approved', $notification->data['message']);
        $this->assertEquals($product->id, $notification->data['product_id']);
        $this->assertEquals($product->name, $notification->data['product_name']);
    }
}