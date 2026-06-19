<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidSubmissionCheckout extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'product_id',
        'status',
        'product_name',
        'product_link',
        'schedule_date',
        'amount_cents',
        'currency',
        'submission_payload',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_event_id',
        'idempotency_key',
        'failure_message',
        'schedule_date_changed_at',
        'receipt_sent_at',
        'processed_at',
        'paid_at',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'schedule_date_changed_at' => 'datetime',
        'receipt_sent_at' => 'datetime',
        'submission_payload' => 'array',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasUsedScheduleDateChange(): bool
    {
        return $this->schedule_date_changed_at !== null;
    }

    public function canChangeScheduleDateOnce(): bool
    {
        if ($this->hasUsedScheduleDateChange()) {
            return false;
        }

        $product = $this->product;

        if (!$product) {
            return true;
        }

        if ((bool) $product->is_published) {
            return false;
        }

        if ($product->published_at && $product->published_at->isPast()) {
            return false;
        }

        return true;
    }
}
