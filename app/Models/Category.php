<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;

class Category extends Model implements Sitemapable
{
    protected $fillable = [
        'name', 'slug', 'description'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function types()
    {
        return $this->belongsToMany(Type::class, 'category_types');
    }

    public function toSitemapTag(): Url | string | array
    {
        // Ensure the category has a slug
        if (!$this->slug) {
            return []; // Return empty array if not sitemapable
        }

        // Using the 'categories.show' route as it's used for displaying products for a category.
        $url = route('categories.show', $this->slug);

        return Url::create($url)
            ->setLastModificationDate(Carbon::parse($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.7);
    }
}
