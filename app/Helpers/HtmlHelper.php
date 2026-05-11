<?php

namespace App\Helpers;

use App\Services\OutboundLinkPolicyService;

class HtmlHelper
{
    /**
     * Add rel="ugc nofollow" to all links in a HTML string.
     *
     * @param string $html
     * @return string
     */
    public static function addNofollowToLinks(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        return app(OutboundLinkPolicyService::class)->sanitizeHtml($html, 'product_description');
    }

    /**
     * Get the last YouTube video ID from a URL.
     *
     * @param string $url
     * @return string|null
     */
    public static function getLastYoutubeId(string $url): ?string
    {
        preg_match('/(youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $match);
        return $match[2] ?? null;
    }
}
