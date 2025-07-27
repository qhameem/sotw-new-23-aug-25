<?php

namespace App\Listeners;

use App\Events\ProductApproved;
use App\Mail\ProductApprovedNotification as EmailNotification; // Alias for clarity
use App\Models\EmailLog;
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
        Log::info("SendProductApprovedNotification listener triggered for product ID: {$event->product->id}");

        $user = $event->user;
        $product = $event->product;

        // Send In-App Notification
        try {
            $user->notify(new ProductApprovedInApp($product));
        } catch (Exception $e) {
            Log::error("Failed to send in-app notification for product ID {$product->id}: " . $e->getMessage());
        }

        // Email Notification Logic
        if (!$user->profile) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'failed',
                'message' => 'User profile not found.'
            ]);
            Log::warning("Email not sent for product ID {$product->id}: User profile not found.");
            return;
        }

        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'failed',
                'message' => 'Invalid email address.'
            ]);
            Log::warning("Email not sent for product ID {$product->id}: Invalid email address '{$user->email}'.");
            return;
        }

        if ($user->profile->optedOutOfNotification('product_approval_notifications')) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'skipped',
                'message' => 'User opted out of this notification.'
            ]);
            Log::info("Email skipped for product ID {$product->id}: User opted out.");
            return;
        }
        
        try {
            // Log that email sending is initiated before attempting to send
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'initiated',
                'message' => 'Attempting to send product approval email.'
            ]);

            Mail::to($user->email)->send(new EmailNotification($user, $product));
            
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'sent',
                'message' => 'Email sent successfully.'
            ]);
            Log::info("Email sent successfully for product ID {$product->id} to {$user->email}.");
        } catch (Exception $e) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'failed',
                'message' => 'Failed to send email: ' . $e->getMessage()
            ]);
            Log::error("Failed to send email for product ID {$product->id}: " . $e->getMessage());
        }
    }
}
