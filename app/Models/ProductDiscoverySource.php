<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductDiscoverySource extends Model
{
    protected $fillable = [
        'name', 'url', 'type', 'item_selector', 'link_selector',
        'title_selector', 'description_selector', 'is_active', 'max_items',
        'last_scanned_at', 'last_success_at', 'last_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_scanned_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function recommendations(): HasMany
    {
        return $this->hasMany(ProductRecommendation::class, 'source_id');
    }

    public function faviconUrl(): ?string
    {
        $scheme = parse_url($this->url, PHP_URL_SCHEME);
        $host = parse_url($this->url, PHP_URL_HOST);
        $port = parse_url($this->url, PHP_URL_PORT);

        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '') {
            return null;
        }

        return $scheme.'://'.$host.($port ? ':'.$port : '').'/favicon.ico';
    }
}
