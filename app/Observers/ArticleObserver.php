<?php

namespace App\Observers;

use App\Jobs\SubmitUrlNotifications;
use App\Models\Article;
use App\Services\UrlNotificationService;
use Illuminate\Support\Carbon;

class ArticleObserver
{
    protected const INDEXABLE_ATTRIBUTES = [
        'status',
        'published_at',
        'slug',
        'title',
        'content',
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

    protected static array $pendingNotifications = [];

    public function created(Article $article): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        if ($this->isLive($article)) {
            $this->dispatchNotifications([$this->articleUrl($article)]);
        }
    }

    public function updating(Article $article): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        $dirtyAttributes = array_keys($article->getDirty());
        $wasLive = $this->wasLive($article);
        $isLive = $this->isLive($article);
        $updatedUrls = [];
        $deletedUrls = [];

        if ($wasLive && ! $isLive) {
            $deletedUrls[] = $this->originalArticleUrl($article);
        }

        if ($isLive && $this->hasRelevantDirtyChanges($dirtyAttributes)) {
            if ($wasLive && in_array('slug', $dirtyAttributes, true)) {
                $deletedUrls[] = $this->originalArticleUrl($article);
            }

            $updatedUrls[] = $this->articleUrl($article);
        }

        self::$pendingNotifications[spl_object_id($article)] = [
            'updated' => collect($updatedUrls)->filter()->unique()->values()->all(),
            'deleted' => collect($deletedUrls)->filter()->unique()->values()->all(),
        ];
    }

    public function updated(Article $article): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        $objectId = spl_object_id($article);
        $notifications = self::$pendingNotifications[$objectId] ?? ['updated' => [], 'deleted' => []];

        unset(self::$pendingNotifications[$objectId]);

        $this->dispatchNotifications($notifications['updated'], $notifications['deleted']);
    }

    public function deleted(Article $article): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        if ($this->wasLive($article)) {
            $this->dispatchNotifications([], [$this->originalArticleUrl($article)]);
        }
    }

    protected function isLive(Article $article): bool
    {
        return $article->status === 'published'
            && $article->published_at !== null
            && $article->published_at->lte(now());
    }

    protected function wasLive(Article $article): bool
    {
        $publishedAt = $article->getOriginal('published_at');

        if (! $publishedAt) {
            return false;
        }

        return $article->getOriginal('status') === 'published'
            && Carbon::parse($publishedAt)->lte(now());
    }

    protected function articleUrl(Article $article): string
    {
        return route('articles.show', $article->slug);
    }

    protected function originalArticleUrl(Article $article): string
    {
        return route('articles.show', $article->getOriginal('slug') ?: $article->slug);
    }

    protected function dispatchNotifications(array $updatedUrls = [], array $deletedUrls = []): void
    {
        $updatedUrls = collect($updatedUrls)->filter()->unique()->values()->all();
        $deletedUrls = collect($deletedUrls)->filter()->unique()->values()->all();

        if ($updatedUrls === [] && $deletedUrls === []) {
            return;
        }

        SubmitUrlNotifications::dispatch($updatedUrls, $deletedUrls);
    }

    protected function notificationsEnabled(): bool
    {
        return app(UrlNotificationService::class)->isEnabled();
    }

    protected function hasRelevantDirtyChanges(array $dirtyAttributes): bool
    {
        return collect($dirtyAttributes)
            ->intersect(self::INDEXABLE_ATTRIBUTES)
            ->isNotEmpty();
    }
}
