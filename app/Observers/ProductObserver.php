<?php

namespace App\Observers;

use App\Jobs\SubmitIndexNowUrls;
use App\Models\Product;
use App\Services\IndexNowService;

class ProductObserver
{
    protected static array $pendingSubmissionUrls = [];

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
        if (! $this->indexNowEnabled()) {
            return;
        }

        if ($this->isLive($product)) {
            $this->dispatchUrls([$this->productUrl($product)]);
        }
    }

    public function updating(Product $product): void
    {
        if (! $this->indexNowEnabled()) {
            return;
        }

        $isLive = $this->isLive($product);
        $dirtyAttributes = array_keys($product->getDirty());
        $urls = [];

        if (in_array('approved', $dirtyAttributes, true) && ! $product->approved) {
            $urls[] = $this->originalProductUrl($product);
        }

        if (in_array('is_published', $dirtyAttributes, true) && ! $product->is_published) {
            $urls[] = $this->originalProductUrl($product);
        }

        if ($isLive && $this->hasRelevantDirtyChanges($dirtyAttributes)) {
            if (in_array('slug', $dirtyAttributes, true)) {
                $urls[] = $this->originalProductUrl($product);
            }

            $urls[] = $this->productUrl($product);
        }

        self::$pendingSubmissionUrls[spl_object_id($product)] = collect($urls)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function updated(Product $product): void
    {
        if (! $this->indexNowEnabled()) {
            return;
        }

        $objectId = spl_object_id($product);
        $urls = self::$pendingSubmissionUrls[$objectId] ?? [];

        unset(self::$pendingSubmissionUrls[$objectId]);

        $this->dispatchUrls($urls);
    }

    public function deleted(Product $product): void
    {
        if (! $this->indexNowEnabled()) {
            return;
        }

        if ((bool) $product->getOriginal('approved') && (bool) $product->getOriginal('is_published')) {
            $this->dispatchUrls([$this->originalProductUrl($product)]);
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

    protected function originalProductUrl(Product $product): string
    {
        return route('products.show', $product->getOriginal('slug') ?: $product->slug);
    }

    protected function dispatchUrls(array $urls): void
    {
        $urls = collect($urls)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($urls === []) {
            return;
        }

        SubmitIndexNowUrls::dispatch($urls);
    }

    protected function indexNowEnabled(): bool
    {
        return app(IndexNowService::class)->isEnabled();
    }

    protected function hasRelevantDirtyChanges(array $dirtyAttributes): bool
    {
        return collect($dirtyAttributes)
            ->intersect(self::INDEXABLE_ATTRIBUTES)
            ->isNotEmpty();
    }
}
