<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_name',
        'type',
        'content',
        'target_url',
        'open_in_new_tab',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * The ad zones that belong to the ad.
     */
    public function adZones(): BelongsToMany
    {
        return $this->belongsToMany(AdZone::class, 'ad_ad_zone');
    }
}
