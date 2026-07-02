<?php

namespace App\Services;

use App\Contracts\UrlNotificationProvider;

class UrlNotificationService
{
    /**
     * @return array<int, UrlNotificationProvider>
     */
    protected function providers(): array
    {
        return [
            app(IndexNowService::class),
            app(GoogleIndexingService::class),
        ];
    }

    public function isEnabled(): bool
    {
        foreach ($this->providers() as $provider) {
            if ($provider->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    public function submit(array $updatedUrls = [], array $deletedUrls = []): void
    {
        foreach ($this->providers() as $provider) {
            if (! $provider->isEnabled()) {
                continue;
            }

            if ($updatedUrls !== []) {
                $provider->notifyUrls($updatedUrls, UrlNotificationProvider::TYPE_UPDATED);
            }

            if ($deletedUrls !== []) {
                $provider->notifyUrls($deletedUrls, UrlNotificationProvider::TYPE_DELETED);
            }
        }
    }
}
