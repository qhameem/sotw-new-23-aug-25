<?php

namespace App\Observers;

use App\Jobs\SubmitUrlNotifications;
use App\Models\ProductMedia;
use App\Services\UrlNotificationService;

class ProductMediaObserver
{
    public function created(ProductMedia $media): void
    {
        $this->dispatchForProduct($media);
    }

    public function updated(ProductMedia $media): void
    {
        $this->dispatchForProduct($media);
    }

    public function deleted(ProductMedia $media): void
    {
        $this->dispatchForProduct($media);
    }

    protected function dispatchForProduct(ProductMedia $media): void
    {
        if (! app(UrlNotificationService::class)->isEnabled()) {
            return;
        }

        $product = $media->product()->first();

        if (! $product || ! $product->approved || ! $product->is_published) {
            return;
        }

        SubmitUrlNotifications::dispatch([route('products.show', $product->slug)], []);
    }
}
