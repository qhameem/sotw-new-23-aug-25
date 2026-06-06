<?php

namespace App\Support;

class ToolGoogleAuth
{
    public static function redirectUri(string $fallback): string
    {
        return (string) (config('services.google.tool_redirect') ?: $fallback);
    }

    public static function isAvailableForCurrentHost(?string $currentHost = null): bool
    {
        $clientId = (string) config('services.google.client_id');
        $clientSecret = (string) config('services.google.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            return false;
        }

        $toolRedirect = (string) config('services.google.tool_redirect');

        if ($toolRedirect !== '') {
            return true;
        }

        $host = strtolower((string) ($currentHost ?: request()?->getHost() ?: parse_url(config('app.url'), PHP_URL_HOST)));

        if ($host === '') {
            return false;
        }

        return ! self::isLocalHost($host);
    }

    public static function unavailableReason(): string
    {
        return 'Google sign-in is unavailable on this local environment until a tool-specific OAuth redirect URI is configured.';
    }

    private static function isLocalHost(string $host): bool
    {
        return $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local');
    }
}
