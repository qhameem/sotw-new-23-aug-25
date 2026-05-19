<?php

namespace App\Support;

class SocialLinkValidator
{
    private const ALLOWED_MAKER_LINK_HOSTS = [
        'github.com',
        'gist.github.com',
        'gitlab.com',
        'linkedin.com',
        'facebook.com',
        'instagram.com',
        'youtube.com',
        'youtu.be',
        'discord.com',
        'discord.gg',
        'reddit.com',
        'tiktok.com',
        'medium.com',
        'substack.com',
        'dev.to',
        'threads.net',
        'behance.net',
        'dribbble.com',
        'producthunt.com',
    ];

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

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        return str_starts_with($host, 'www.')
            ? substr($host, 4)
            : $host;
    }
}
