<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Carbon\Carbon;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\Log; // Added Log facade
use DOMDocument;

class Article extends Model implements Feedable, Sitemapable
{
    use HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_url',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'featured_image_path',
        'staff_pick',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the user that owns the article.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship, representing the author.
     */
    public function author(): BelongsTo
    {
        return $this->user();
    }

    /**
     * The categories that belong to the article.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category_pivot', 'article_id', 'article_category_id')->withTimestamps();
    }

    /**
     * The tags that belong to the article.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArticleTag::class, 'article_tag_pivot', 'article_id', 'article_tag_id')->withTimestamps();
    }

    /**
     * Set the title and tentatively generate the slug.
     * The 'saving' event will finalize and ensure uniqueness.
     *
     * @param  string  $value
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        // Tentatively set slug from title.
        // If title is empty or slugs to empty, the 'saving' event will handle it.
        if (!empty($value)) {
            $this->attributes['slug'] = Str::slug($value);
        } else {
            // If title is being cleared, allow slug to be potentially cleared too,
            // 'saving' event will generate a default if it remains empty.
            // However, to aid client-side JS, we might not want to clear it here if it was manually set.
            // For now, let's only set from title if title is not empty.
            // If slug was manually set, this won't overwrite it unless title changes.
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Article $post) {
            Log::info("Article@saving: Triggered for Post ID: " . ($post->id ?? 'NEW') . ", Title: '{$post->title}', Current Slug: '{$post->slug}'");

            $originalSlugBeforeModification = $post->slug;
            $titleSlug = !empty($post->title) ? Str::slug($post->title) : null;

            // 1. If slug is empty OR if the current slug is an auto-generated placeholder from a previous save (and title is now available)
            // OR if the slug is different from what the current title would generate (suggesting title changed and slug should follow, unless manually set)
            // This logic needs to be careful not to overwrite a manually set slug if the title changes.
            // For now, let's simplify: if slug is empty, generate from title. If title is also empty, generate placeholder.
            
            if (empty($post->slug)) {
                if ($titleSlug) {
                    $post->slug = $titleSlug;
                    Log::info("Article@saving: Slug was empty, generated from title: '{$post->slug}'");
                } else {
                    $post->slug = 'post-' . time() . '-' . Str::random(4);
                    Log::info("Article@saving: Slug and title were empty, generated placeholder: '{$post->slug}'");
                }
            } else {
                 // If slug is not empty, but it's different from what the current title would generate,
                 // it implies the title might have changed OR the slug was manually set.
                 // We should only auto-update from title if the slug wasn't manually set to something different.
                 // This part is tricky without knowing if a slug was manually edited.
                 // The current client-side JS tries to manage this.
                 // For backend, if a slug exists, the uniqueness check below is the most important.
                 Log::info("Article@saving: Post already has a slug: '{$post->slug}'. Proceeding to uniqueness check.");
            }


            // 3. Ensure uniqueness for the final slug.
            $finalSlug = $post->slug; // The slug we intend to save
            $originalSlugForUniqueness = $finalSlug; // Base for appending counter
            $counter = 1;
            
            $query = static::where('slug', $finalSlug);
            if ($post->exists && $post->id) {
                $query->where('id', '!=', $post->id);
            }
            
            while ($query->clone()->exists()) { // Clone the query for re-use in loop
                $finalSlug = $originalSlugForUniqueness . '-' . $counter++;
                $query = static::where('slug', $finalSlug); // Re-assign query for the new slug
                 if ($post->exists && $post->id) {
                    $query->where('id', '!=', $post->id);
                }
            }
            $post->slug = $finalSlug;

            if ($originalSlugBeforeModification !== $post->slug) {
                Log::info("Article@saving: Slug changed/generated. Old: '{$originalSlugBeforeModification}', New: '{$post->slug}' for Post ID: " . ($post->id ?? 'NEW'));
            } else if ($originalSlugBeforeModification === $post->slug && empty($originalSlugBeforeModification) && !empty($post->slug)) {
                 Log::info("Article@saving: Slug generated for new post. New: '{$post->slug}' for Post ID: NEW");
            }
        });
    }

    /**
     * Set the content attribute, adding rel="nofollow" to all <a> tags.
     *
     * @param  string  $value
     * @return void
     */
    public function setContentAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['content'] = $value;
            return;
        }

        $dom = new DOMDocument();
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $value, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $rel = $link->getAttribute('rel');
            if (empty($rel)) {
                $link->setAttribute('rel', 'nofollow');
            } else {
                $rels = explode(' ', $rel);
                if (!in_array('nofollow', $rels)) {
                    $rels[] = 'nofollow';
                }
                $link->setAttribute('rel', implode(' ', array_unique($rels)));
            }
        }

        $this->attributes['content'] = $dom->saveHTML();
    }

    /**
     * Override route key name to use slug for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create()
            ->id($this->id)
            ->title($this->title)
            ->summary(Str::limit(strip_tags($this->content), 300)) // Basic summary
            ->updated($this->updated_at ?? Carbon::now())
            ->link(route('articles.show', $this))
            ->authorName($this->author->name ?? 'Unknown Author')
            ->authorEmail($this->author->email ?? 'noreply@example.com'); // Fallback email
    }

    public static function getFeedItems()
    {
        return static::where('status', 'published')
            ->where('published_at', '<=', Carbon::now())
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function toSitemapTag(): Url | string | array
    {
        // Return an Url instance if you need to customize settings like changeFrequency, priority, lastModified
        // Ensure the post is published and not in the future
        if ($this->status !== 'published' || (new Carbon($this->published_at)) > Carbon::now()) {
            return []; // Don't include in sitemap
        }

        return Url::create(route('articles.show', $this->slug))
            ->setLastModificationDate(Carbon::parse($this->updated_at))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY) // Or daily, monthly, etc.
            ->setPriority(0.8); // Priority from 0.0 to 1.0
    }
}