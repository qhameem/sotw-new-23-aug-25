<?php

namespace App\Support;

class SocialLinkValidator
{
    private const ALLOWED_MAKER_LINK_HOSTS = [
        'addons.mozilla.org',
        'addons.opera.com',
        'apps.apple.com',
        'apps.microsoft.com',
        'behance.net',
        'chrome.google.com',
        'chromewebstore.google.com',
        'dev.to',
        'discord.com',
        'discord.gg',
        'dribbble.com',
        'facebook.com',
        'github.com',
        'gist.github.com',
        'gitlab.com',
        'instagram.com',
        'linkedin.com',
        'medium.com',
        'microsoftedge.microsoft.com',
        'play.google.com',
        'producthunt.com',
        'reddit.com',
        'substack.com',
        'threads.net',
        'tiktok.com',
        'youtu.be',
        'youtube.com',
    ];

    private const ALLOWED_MAKER_LINK_MESSAGE = 'Only profile, social, app store, or browser extension links like GitHub, LinkedIn, App Store, Play Store, and similar listings are allowed.';

    public static function isAllowedMakerLinkUrl(?string $url): bool
    {
        if (!is_string($url) || trim($url) === '') {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return false;
        }

        $host = self::normalizeHost($parts['host']);
        foreach (self::ALLOWED_MAKER_LINK_HOSTS as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    public static function allowedMakerLinkMessage(): string
    {
        return self::ALLOWED_MAKER_LINK_MESSAGE;
    }

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        return str_starts_with($host, 'www.')
            ? substr($host, 4)
            : $host;
    }
}
