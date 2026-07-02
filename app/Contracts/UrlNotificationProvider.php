<?php

namespace App\Contracts;

interface UrlNotificationProvider
{
    public const TYPE_UPDATED = 'URL_UPDATED';
    public const TYPE_DELETED = 'URL_DELETED';

    public function isEnabled(): bool;

    public function notifyUrls(array $urls, string $type = self::TYPE_UPDATED): void;
}
