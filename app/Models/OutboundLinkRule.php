<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundLinkRule extends Model
{
    use HasFactory;

    public const MATCH_TYPE_EXACT_URL = 'exact_url';
    public const MATCH_TYPE_DOMAIN = 'domain';
    public const MATCH_TYPE_DOMAIN_PATH_PREFIX = 'domain_path_prefix';

    public const SOURCE_SCOPE_ALL = 'all';

    protected $fillable = [
        'name',
        'match_type',
        'pattern',
        'source_scope',
        'rel_nofollow',
        'rel_ugc',
        'rel_sponsored',
        'rel_noopener',
        'rel_noreferrer',
        'priority',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rel_nofollow' => 'boolean',
        'rel_ugc' => 'boolean',
        'rel_sponsored' => 'boolean',
        'rel_noopener' => 'boolean',
        'rel_noreferrer' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
