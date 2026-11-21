<?php

namespace App\Models;

use App\Support\CategoryTypeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $fillable = ['name', 'description'];

    public function scopeVisibleInPickers(Builder $query): Builder
    {
        return $query->where('name', '!=', CategoryTypeRegistry::LEGACY_SOFTWARE_NAME);
    }

    public static function softwareFilterIds($id): array
    {
        $ids = static::whereIn('name', [
            CategoryTypeRegistry::SOFTWARE_NAME,
            CategoryTypeRegistry::LEGACY_SOFTWARE_NAME,
        ])->pluck('id');

        return $ids->contains((int) $id) ? $ids->all() : [$id];
    }

    public function getCategoryOwnerIdAttribute(): ?int
    {
        if ($this->name === CategoryTypeRegistry::LEGACY_SOFTWARE_NAME) {
            return static::where('name', CategoryTypeRegistry::SOFTWARE_NAME)->value('id') ?? $this->id;
        }

        return $this->id;
    }

    public function assignedCategories()
    {
        return $this->belongsToMany(Category::class, 'category_types');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_types', parentKey: 'category_owner_id');
    }
}
