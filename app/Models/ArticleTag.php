<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;

class ArticleTag extends Model implements Sitemapable
{
    use HasFactory;

    protected $table = 'article_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * The articles that belong to the tag.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag_pivot', 'article_tag_id', 'article_id')->withTimestamps();
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
        // Only include if there are published posts with this tag
        if ($this->articles()->where('status', 'published')->where('published_at', '<=', Carbon::now())->doesntExist()) {
            return [];
        }

        // Find the last updated post with this tag to set as last modification date for the tag URL
        $lastModifiedPost = $this->articles()
                                  ->where('status', 'published')
                                  ->where('published_at', '<=', Carbon::now())
                                  ->orderBy('updated_at', 'desc')
                                  ->first();
                                  
        return Url::create(route('articles.tag', $this->slug))
            ->setLastModificationDate($lastModifiedPost ? Carbon::parse($lastModifiedPost->updated_at) : Carbon::parse($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.6); // Tags might be slightly lower priority than categories or posts
    }
}