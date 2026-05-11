<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundLinkOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'occurrence_key',
        'normalized_url',
        'original_url',
        'domain',
        'path',
        'source_type',
        'source_id',
        'source_title',
        'source_admin_url',
        'anchor_text',
        'detected_rel',
        'first_seen_at',
        'last_seen_at',
        'occurrence_count',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'occurrence_count' => 'integer',
    ];
}
