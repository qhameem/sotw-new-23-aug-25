<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_name',
        'type',
        'content',
        'tagline',
        'target_url',
        'open_in_new_tab',
        'is_active',
        'start_date',
        'end_date',
        'target_countries',
        'target_routes',
        'target_category_ids',
        'audience_scope',
        'device_types',
        'weight',
        'priority',
        'is_house_ad',
        'impressions_count',
        'clicks_count',
        'manages_own_image',
    ];

    protected $appends = [
        'image_url',
        'effective_status',
        'schedule_state',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'target_countries' => 'array',
        'target_routes' => 'array',
        'target_category_ids' => 'array',
        'device_types' => 'array',
        'weight' => 'integer',
        'priority' => 'integer',
        'is_house_ad' => 'boolean',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
        'manages_own_image' => 'boolean',
    ];

    /**
     * The ad zones that belong to the ad.
     */
    public function adZones(): BelongsToMany
    {
        return $this->belongsToMany(AdZone::class, 'ad_ad_zone');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->usesImageContent() || ! is_string($this->content) || trim($this->content) === '') {
            return null;
        }

        $storagePath = static::normalizeStoragePath($this->content);

        if ($storagePath !== null) {
            return Storage::url($storagePath);
        }

        if (filter_var($this->content, FILTER_VALIDATE_URL)) {
            return $this->content;
        }

        return str_starts_with($this->content, '/')
            ? $this->content
            : '/' . ltrim($this->content, '/');
    }

    public function getEffectiveStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->start_date && $this->start_date->isFuture()) {
            return 'scheduled';
        }

        if ($this->end_date && $this->end_date->lt($now)) {
            return 'expired';
        }

        return 'active';
    }

    public function getScheduleStateAttribute(): string
    {
        if ($this->start_date === null && $this->end_date === null) {
            return 'always_on';
        }

        $now = now();

        if ($this->start_date && $this->start_date->isFuture()) {
            return 'starts_' . $this->start_date->toIso8601String();
        }

        if ($this->end_date && $this->end_date->lt($now)) {
            return 'ended_' . $this->end_date->toIso8601String();
        }

        if ($this->end_date) {
            return 'ends_' . $this->end_date->toIso8601String();
        }

        return 'running';
    }

    public function isEligibleAt(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->start_date && $this->start_date->gt($at)) {
            return false;
        }

        if ($this->end_date && $this->end_date->lt($at)) {
            return false;
        }

        return true;
    }

    public function hasManagedImage(): bool
    {
        return $this->usesImageContent()
            && $this->manages_own_image
            && static::normalizeStoragePath($this->content) !== null;
    }

    public function usesImageContent(): bool
    {
        return in_array($this->type, ['image_banner', 'product_listing_card'], true);
    }

    public static function normalizeStoragePath(?string $content): ?string
    {
        if (! is_string($content)) {
            return null;
        }

        $content = trim($content);

        if ($content === '') {
            return null;
        }

        if (str_starts_with($content, '/storage/')) {
            return ltrim(substr($content, strlen('/storage/')), '/');
        }

        if (str_starts_with($content, 'storage/')) {
            return ltrim(substr($content, strlen('storage/')), '/');
        }

        if (filter_var($content, FILTER_VALIDATE_URL)) {
            $path = parse_url($content, PHP_URL_PATH);

            if (is_string($path) && str_starts_with($path, '/storage/')) {
                return ltrim(substr($path, strlen('/storage/')), '/');
            }

            return null;
        }

        return str_contains($content, '://') ? null : ltrim($content, '/');
    }
}
