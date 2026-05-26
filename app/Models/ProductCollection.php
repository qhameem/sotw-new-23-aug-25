<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductCollection extends Model
{
    use HasFactory;

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    public const DEFAULT_NAMES = [
        'Favorites',
        'Saved for Later',
        'Free Tools',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'visibility',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $collection) {
            if ($collection->user_id) {
                $collection->user()->first()?->ensurePublicHandle();
            }

            if (blank($collection->slug)) {
                $collection->slug = static::generateUniqueSlug($collection->name, $collection->user_id);
            }
        });
    }

    public static function defaultNames(): array
    {
        return self::DEFAULT_NAMES;
    }

    public static function generateUniqueSlug(string $name, ?int $userId = null): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'collection';
        $slug = $baseSlug;
        $counter = 2;

        while (static::query()
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    public function publicRouteParameters(): array
    {
        $owner = $this->relationLoaded('user') ? $this->getRelation('user') : $this->user;

        if ($owner) {
            $owner->ensurePublicHandle();
        }

        return [
            'owner' => $owner,
            'collectionSlug' => $this->slug,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductCollectionItem::class)->latest();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_collection_items')
            ->withPivot(['comment'])
            ->withTimestamps();
    }

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }
}
