<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'paid_submission_checkout_id',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(PaidSubmissionCheckout::class, 'paid_submission_checkout_id');
    }
}
