<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;

class ArticleCategory extends Model implements Sitemapable
{
    use HasFactory;

    protected $table = 'article_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
    ];

    /**
     * The articles that belong to the category.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category_pivot', 'article_category_id', 'article_id')->withTimestamps();
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ArticleCategory::class, 'parent_id');
    }

    /**
     * Set the name and automatically generate the slug.
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (!isset($this->attributes['slug']) || empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * Override route key name to use slug for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function toSitemapTag(): Url | string | array
    {
        // Only include if there are published posts in this category
        if ($this->articles()->where('status', 'published')->where('published_at', '<=', Carbon::now())->doesntExist()) {
            return [];
        }

        // Find the last updated post in this category to set as last modification date for the category URL
        $lastModifiedPost = $this->articles()
                                  ->where('status', 'published')
                                  ->where('published_at', '<=', Carbon::now())
                                  ->orderBy('updated_at', 'desc')
                                  ->first();

        return Url::create(route('articles.category', $this->slug))
            ->setLastModificationDate($lastModifiedPost ? Carbon::parse($lastModifiedPost->updated_at) : Carbon::parse($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.7);
    }
}