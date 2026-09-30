<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemErrorReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint',
        'severity',
        'source',
        'summary',
        'details',
        'context',
        'user_id',
        'occurrence_count',
        'first_occurred_at',
        'last_occurred_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'first_occurred_at' => 'datetime',
            'last_occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
