<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolAuthMagicLink extends Model
{
    protected $fillable = [
        'tool_user_id',
        'email',
        'token_hash',
        'otp_code_hash',
        'redirect_to',
        'expires_at',
        'consumed_at',
        'requested_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(ToolUser::class, 'tool_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasBeenConsumed(): bool
    {
        return $this->consumed_at !== null;
    }
}
