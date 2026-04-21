<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleEditorService
{
    /**
     * These fields map directly to the articles table and are safe to fill.
     */
    private const ARTICLE_FIELDS = [
        'title',
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
    ];

    public function availableStatuses(?User $user): array
    {
        $statuses = [
            'draft' => 'Draft',
            'published' => 'Published',
        ];

        if ($user?->hasRole('admin')) {
            $statuses['scheduled'] = 'Scheduled';
        }

        return $statuses;
    }

    public function save(Article $article, array $data, ?User $actor, bool $createRevision = true, ?string $reason = null): Article
    {
        return DB::transaction(function () use ($article, $data, $actor, $createRevision, $reason) {
            $isNew = !$article->exists;

            if ($isNew && $actor !== null) {
                $article->user()->associate($actor);
            }

            $article->fill(Arr::only($data, self::ARTICLE_FIELDS));

            if (array_key_exists('slug', $data)) {
                $article->slug = filled($data['slug'])
                    ? Str::slug($data['slug'])
                    : Str::slug($data['title'] ?? $article->title ?? 'untitled-draft');
            }

            if (blank($article->title)) {
                $article->title = 'Untitled Draft';
            }

            if (blank($article->content)) {
                $article->content = '<p></p>';
            }

            if ($article->status === 'published' && blank($article->published_at)) {
                $article->published_at = now();
            }

            if ($article->status === 'draft' && array_key_exists('published_at', $data) && blank($data['published_at'])) {
                $article->published_at = null;
            }

            $article->save();

            if (array_key_exists('categories', $data)) {
                $article->categories()->sync($data['categories'] ?? []);
            }

            if (array_key_exists('tags', $data)) {
                $article->tags()->sync($data['tags'] ?? []);
            }

            $article->load('categories', 'tags', 'author');

            if ($createRevision) {
                $this->storeRevision($article, $actor, $reason ?? ($isNew ? 'created' : 'updated'));
            }

            return $article;
        });
    }

    public function autosave(Article $article, array $data, ?User $actor): Article
    {
        if (!$article->exists) {
            $data['status'] = 'draft';
            $data['title'] = filled($data['title'] ?? null) ? $data['title'] : 'Untitled Draft';
            $data['content'] = filled($data['content'] ?? null) ? $data['content'] : '<p></p>';
        } else {
            $data['status'] = $article->status;
            $data['published_at'] = $article->published_at;
        }

        return $this->save($article, $data, $actor, false);
    }

    public function storeRevision(Article $article, ?User $actor, string $reason = 'updated'): ArticleRevision
    {
        $article->loadMissing('categories:id', 'tags:id');

        return $article->revisions()->create([
            'user_id' => $actor?->id,
            'reason' => $reason,
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'status' => $article->status,
            'published_at' => $article->published_at,
            'meta_title' => $article->meta_title,
            'meta_description' => $article->meta_description,
            'meta_keywords' => $article->meta_keywords,
            'og_title' => $article->og_title,
            'og_description' => $article->og_description,
            'og_image' => $article->og_image,
            'og_url' => $article->og_url,
            'twitter_card' => $article->twitter_card,
            'twitter_title' => $article->twitter_title,
            'twitter_description' => $article->twitter_description,
            'featured_image_path' => $article->featured_image_path,
            'category_ids' => $article->categories->modelKeys(),
            'tag_ids' => $article->tags->modelKeys(),
        ]);
    }

    public function restoreRevision(ArticleRevision $revision, ?User $actor): Article
    {
        return DB::transaction(function () use ($revision, $actor) {
            $article = $revision->article()->with('categories', 'tags')->firstOrFail();

            $this->storeRevision($article, $actor, 'pre-restore');

            return $this->save($article, [
                'title' => $revision->title,
                'slug' => $revision->slug,
                'content' => $revision->content,
                'status' => $revision->status,
                'published_at' => $revision->published_at,
                'meta_title' => $revision->meta_title,
                'meta_description' => $revision->meta_description,
                'meta_keywords' => $revision->meta_keywords,
                'og_title' => $revision->og_title,
                'og_description' => $revision->og_description,
                'og_image' => $revision->og_image,
                'og_url' => $revision->og_url,
                'twitter_card' => $revision->twitter_card,
                'twitter_title' => $revision->twitter_title,
                'twitter_description' => $revision->twitter_description,
                'featured_image_path' => $revision->featured_image_path,
                'categories' => $revision->category_ids ?? [],
                'tags' => $revision->tag_ids ?? [],
            ], $actor, true, 'restored');
        });
    }
}
