<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdZone extends Model
{
    use HasFactory;

    public const PLACEMENT_TYPES = [
        'header',
        'sidebar',
        'in_feed',
        'inline',
        'footer',
        'modal',
        'other',
    ];

    public const ROTATION_MODES = [
        'random',
        'priority',
        'weighted',
    ];

    public const DEVICE_SCOPES = [
        'all',
        'desktop',
        'mobile',
        'tablet',
    ];

    public const FALLBACK_MODES = [
        'empty',
        'house_ads',
    ];

    public const SUPPORTED_AD_TYPES = [
        'image_banner',
        'product_listing_card',
        'text_link',
        'html_snippet',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'render_location',
        'placement_type',
        'supported_ad_types',
        'max_ads',
        'rotation_mode',
        'device_scope',
        'fallback_mode',
        'display_after_nth_product',
    ];

    protected $casts = [
        'supported_ad_types' => 'array',
        'max_ads' => 'integer',
        'display_after_nth_product' => 'integer',
    ];

    /**
     * The ads that belong to the ad zone.
     */
    public function ads(): BelongsToMany
    {
        return $this->belongsToMany(Ad::class, 'ad_ad_zone');
    }

    public function supportsAdType(string $type): bool
    {
        $supportedTypes = $this->supported_ad_types ?: self::SUPPORTED_AD_TYPES;

        return in_array($type, $supportedTypes, true);
    }

    public function isListPlacement(): bool
    {
        return $this->placement_type === 'in_feed';
    }
}
