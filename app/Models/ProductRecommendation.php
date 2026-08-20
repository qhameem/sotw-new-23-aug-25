<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecommendation extends Model
{
    public const STATUSES = ['new', 'shortlisted', 'dismissed'];

    protected $fillable = [
        'source_id', 'title', 'url', 'url_hash', 'description', 'score',
        'status', 'discovered_at', 'last_seen_at',
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ProductDiscoverySource::class, 'source_id');
    }
}
