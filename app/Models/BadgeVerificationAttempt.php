<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BadgeVerificationAttempt extends Model
{
    protected $fillable = [
        'product_id', 'triggered_by_user_id', 'trigger', 'url', 'verified',
        'http_status', 'response_hash', 'matched_element', 'message',
        'request_ip', 'checked_at',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'checked_at' => 'datetime',
    ];
}
