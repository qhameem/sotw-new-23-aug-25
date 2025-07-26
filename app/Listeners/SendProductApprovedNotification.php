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
        $user = $event->user;
        $product = $event->product;

        EmailLog::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => 'initiated',
            'message' => 'Processing product approval email.'
        ]);

        // Send In-App Notification
        try {
            $user->notify(new ProductApprovedInApp($product));
        } catch (Exception $e) {
            // Log and continue
        }

        // Email Notification Logic
        if (!$user->profile) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'failed',
                'message' => 'User profile not found.'
            ]);
            return;
        }

        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'failed',
                'message' => 'Invalid email address.'
            ]);
            return;
        }

        if ($user->profile->optedOutOfNotification('product_approval_notifications')) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'skipped',
                'message' => 'User opted out of this notification.'
            ]);
            return;
        }
        
        try {
            Mail::to($user->email)->queue(new \App\Mail\ProductApproved($product));
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'queued',
                'message' => 'Email has been successfully queued for sending.'
            ]);
        } catch (Exception $e) {
            EmailLog::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'status' => 'failed',
                'message' => 'Failed to queue email: ' . $e->getMessage()
            ]);
        }
    }
}
