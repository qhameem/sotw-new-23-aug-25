<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'display_after_nth_product',
    ];

    /**
     * The ads that belong to the ad zone.
     */
    public function ads(): BelongsToMany
    {
        return $this->belongsToMany(Ad::class, 'ad_ad_zone');
    }
}
