<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'path_thumb',
        'path_medium',
        'alt_text',
        'type',
    ];

    protected $appends = [
        'url',
        'thumb_url',
        'medium_url',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->resolvePathUrl($this->path);
    }

    public function getThumbUrlAttribute(): ?string
    {
        return $this->resolvePathUrl($this->path_thumb);
    }

    public function getMediumUrlAttribute(): ?string
    {
        return $this->resolvePathUrl($this->path_medium);
    }

    protected function resolvePathUrl(?string $path): ?string
    {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
