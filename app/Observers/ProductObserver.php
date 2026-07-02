<?php

namespace App\Observers;

use App\Jobs\SubmitUrlNotifications;
use App\Models\Product;
use App\Services\UrlNotificationService;

class ProductObserver
{
    protected static array $pendingNotifications = [];

    protected const INDEXABLE_ATTRIBUTES = [
        'approved',
        'is_published',
        'published_at',
        'slug',
        'name',
        'tagline',
        'product_page_tagline',
        'description',
        'link',
        'logo',
        'video_url',
        'x_account',
        'sell_product',
        'asking_price',
        'price',
        'currency',
        'pricing_page_url',
    ];

    public function created(Product $product): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        if ($this->isLive($product)) {
            $this->dispatchNotifications([$this->productUrl($product)]);
        }
    }

    public function updating(Product $product): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        $wasLive = $this->wasLive($product);
        $isLive = $this->isLive($product);
        $dirtyAttributes = array_keys($product->getDirty());
        $updatedUrls = [];
        $deletedUrls = [];

        if ($wasLive && ! $isLive) {
            $deletedUrls[] = $this->originalProductUrl($product);
        }

        if ($isLive && $this->hasRelevantDirtyChanges($dirtyAttributes)) {
            if ($wasLive && in_array('slug', $dirtyAttributes, true)) {
                $deletedUrls[] = $this->originalProductUrl($product);
            }

            $updatedUrls[] = $this->productUrl($product);
        }

        self::$pendingNotifications[spl_object_id($product)] = [
            'updated' => collect($updatedUrls)->filter()->unique()->values()->all(),
            'deleted' => collect($deletedUrls)->filter()->unique()->values()->all(),
        ];
    }

    public function updated(Product $product): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        $objectId = spl_object_id($product);
        $notifications = self::$pendingNotifications[$objectId] ?? ['updated' => [], 'deleted' => []];

        unset(self::$pendingNotifications[$objectId]);

        $this->dispatchNotifications($notifications['updated'], $notifications['deleted']);
    }

    public function deleted(Product $product): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        if ($this->wasLive($product)) {
            $this->dispatchNotifications([], [$this->originalProductUrl($product)]);
        }
    }

    protected function isLive(Product $product): bool
    {
        return (bool) $product->approved && (bool) $product->is_published;
    }

    protected function productUrl(Product $product): string
    {
        return route('products.show', $product->slug);
    }

    protected function wasLive(Product $product): bool
    {
        return (bool) $product->getOriginal('approved') && (bool) $product->getOriginal('is_published');
    }

    protected function originalProductUrl(Product $product): string
    {
        return route('products.show', $product->getOriginal('slug') ?: $product->slug);
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
