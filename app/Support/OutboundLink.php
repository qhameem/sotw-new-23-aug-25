<?php

namespace App\Support;

use App\Services\OutboundLinkPolicyService;

class OutboundLink
{
    public static function rel(?string $url, string $sourceType = 'system_view'): ?string
    {
        return app(OutboundLinkPolicyService::class)->relStringForUrl($url, $sourceType);
    }

    public static function sanitizeHtml(?string $html, string $sourceType = 'article'): string
    {
        return app(OutboundLinkPolicyService::class)->sanitizeHtml($html, $sourceType);
    }
}
