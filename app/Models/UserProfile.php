<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'bio',
        'website',
        'notification_preferences', // Added
    ];

    protected $casts = [
        'notification_preferences' => 'array', // Added
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a specific notification preference is enabled.
     * Defaults to true if not explicitly set to false.
     *
     * @param string $preferenceKey e.g., 'product_status_notifications', 'product_approval_notifications'
     * @return bool
     */
    public function hasNotificationPreference(string $preferenceKey): bool
    {
        // If notification_preferences is null or the key doesn't exist, default to true.
        // If the key exists and is explicitly false, return false. Otherwise, true.
        return $this->notification_preferences[$preferenceKey] ?? true;
    }

    /**
     * Check if the user has opted out of a specific notification.
     * This is essentially the inverse of hasNotificationPreference for clarity in some contexts.
     *
     * @param string $preferenceKey
     * @return bool
     */
    public function optedOutOfNotification(string $preferenceKey): bool
    {
        return ($this->notification_preferences[$preferenceKey] ?? true) === false;
    }
}
