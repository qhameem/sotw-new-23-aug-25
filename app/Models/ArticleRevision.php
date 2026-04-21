<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'reason',
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
        'category_ids',
        'tag_ids',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'category_ids' => 'array',
        'tag_ids' => 'array',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
