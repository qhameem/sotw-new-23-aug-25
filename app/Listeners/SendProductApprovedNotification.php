<?php

namespace App\Listeners;

use App\Events\ProductApproved;
use App\Mail\ProductApprovedNotification as EmailNotification; // Alias for clarity
use App\Notifications\ProductApprovedInApp; // Import In-App Notification
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendProductApprovedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProductApproved $event): void
    {
        Log::info("SendProductApprovedNotification listener: Handling ProductApproved event for product ID {$event->product->id}, user ID {$event->user->id}.");
        $user = $event->user;
        $product = $event->product;

        // Send In-App Notification
        try {
            $user->notify(new ProductApprovedInApp($product));
            Log::info("In-App Product Approved Notification: Sent successfully to user {$user->id} for product {$product->id}.");
        } catch (Exception $e) {
            Log::error("In-App Product Approved Notification: Failed to send to user {$user->id} for product {$product->id}. Error: {$e->getMessage()}");
            // Log and continue, as email notification might still be desired/possible.
        }

        // Email Notification Logic
        // 0. Ensure user profile exists (for email preferences)
        if (!$user->profile) {
            Log::warning("Email Product Approved Notification: User profile not found for user {$user->id}. Product ID: {$product->id}. Cannot check email notification preferences. Skipping email.");
            return; // Skip email if profile is missing
        }

        // 1. Check if user's email is valid
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            Log::warning("Email Product Approved Notification: Invalid email for user {$user->id}. Product ID: {$product->id}. Email: {$user->email}. Skipping email.");
            return; // Skip email if invalid
        }

        // 2. Check user's email notification preferences for 'product_approval_notifications'
        if ($user->profile->optedOutOfNotification('product_approval_notifications')) {
            Log::info("Email Product Approved Notification: User {$user->id} opted out of 'product_approval_notifications' (email). Product ID: {$product->id}. Skipping email.");
            return; // Skip email if opted out
        }
        
        try {
            Mail::to($user->email)->queue(new \App\Mail\ProductApproved($product));
            Log::info("Email Product Approved Notification: Email queued successfully for user {$user->id} for product {$product->id}.");
        } catch (Exception $e) {
            Log::error("Email Product Approved Notification: Failed to queue email for user {$user->id} for product {$product->id}. Error: {$e->getMessage()}");
            // Since this is not a queued job itself, we don't re-throw.
            // The failure is logged, and we can decide if further action is needed,
            // like a notification to an admin channel.
        }
    }
}
