<?php

namespace App\Listeners;

use LemonSqueezy\Laravel\Events\WebhookReceived;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ProcessLemonSqueezyWebhook
{
    /**
     * Handle the event.
     *
     * @param  \LemonSqueezy\Laravel\Events\WebhookReceived  $event
     * @return void
     */
    public function handle(WebhookReceived $event): void
    {
        // You can access the webhook payload directly from the event.
        $payload = $event->payload;
        $eventName = $payload['meta']['event_name'];

        // Log the event for debugging.
        Log::info('Lemon Squeezy Webhook Received:', $payload);

        // Example: Handle a successful order.
        if ($eventName === 'order_created') {
            $orderData = $payload['data']['attributes'];
            $userEmail = $orderData['user_email'];

            // Find the user by email.
            $user = User::where('email', $userEmail)->first();

            if ($user) {
                // Your logic here. For example, grant access to a course,
                // update their role, or send a confirmation email.
                Log::info("Order created for user: {$user->email}");
                // $user->grantAccessToProduct($orderData['first_order_item']['product_id']);
            } else {
                Log::warning("Webhook Error: User with email {$userEmail} not found.");
            }
        }

        // Example: Handle a new subscription.
        if ($eventName === 'subscription_created') {
            $subscriptionData = $payload['data']['attributes'];
            $userEmail = $subscriptionData['user_email'];

            $user = User::where('email', $userEmail)->first();

            if ($user) {
                // Your logic here. For example, set the user's plan to "pro".
                Log::info("Subscription created for user: {$user->email}");
                // $user->update(['plan' => 'pro']);
            } else {
                Log::warning("Webhook Error: User with email {$userEmail} not found for subscription.");
            }
        }

        // You can add more conditions to handle other events like:
        // - 'subscription_updated'
        // - 'subscription_cancelled'
        // - 'subscription_resumed'
        // - 'subscription_expired'
        // - 'payment_success'
        // - 'refund_created'
    }
}