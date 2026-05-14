<?php

namespace App\Support;

use InvalidArgumentException;

class PublicUrlGuard
{
    private const BLOCKED_HOSTS = [
        'localhost',
        'localhost.localdomain',
        'host.docker.internal',
        'metadata.google.internal',
    ];

    private const BLOCKED_HOST_SUFFIXES = [
        '.internal',
        '.local',
        '.localhost',
        '.localdomain',
        '.home',
        '.lan',
        '.test',
        '.invalid',
        '.example',
    ];

    public static function sanitizePublicHttpUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            throw new InvalidArgumentException('A URL is required.');
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw new InvalidArgumentException('Enter a valid absolute URL.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http and https URLs are allowed.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('URLs with embedded credentials are not allowed.');
        }

        $host = self::extractHost($url);
        if ($host === null || !self::isPublicHost($host)) {
            throw new InvalidArgumentException('That URL must point to a public website.');
        }

        if (!self::hostResolvesToPublicIp($host)) {
            throw new InvalidArgumentException('That URL must resolve to a public IP address.');
        }

        return explode('#', $url, 2)[0];
    }

    public static function extractHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return null;
        }

        return rtrim(strtolower($host), '.');
    }

    public static function hostMatchesAny(string $host, array $allowedHosts): bool
    {
        $normalizedHost = rtrim(strtolower($host), '.');

        foreach ($allowedHosts as $allowedHost) {
            $normalizedAllowedHost = rtrim(strtolower($allowedHost), '.');

            if (
                $normalizedHost === $normalizedAllowedHost
                || str_ends_with($normalizedHost, '.' . $normalizedAllowedHost)
            ) {
                return true;
            }
        }

        return false;
    }

    private static function isPublicHost(string $host): bool
    {
        $normalizedHost = rtrim(strtolower($host), '.');

        if (in_array($normalizedHost, self::BLOCKED_HOSTS, true)) {
            return false;
        }

        foreach (self::BLOCKED_HOST_SUFFIXES as $suffix) {
            if (str_ends_with($normalizedHost, $suffix)) {
                return false;
            }
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($normalizedHost);
        }

        return str_contains($normalizedHost, '.');
    }

    private static function hostResolvesToPublicIp(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host);
        }

        $ips = [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);

            if (is_array($records)) {
                foreach ($records as $record) {
                    if (!empty($record['ip']) && is_string($record['ip'])) {
                        $ips[] = $record['ip'];
                    }

                    if (!empty($record['ipv6']) && is_string($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        $ipv4Addresses = @gethostbynamel($host);
        if (is_array($ipv4Addresses)) {
            $ips = array_merge($ips, $ipv4Addresses);
        }

        $ips = array_values(array_unique(array_filter($ips, fn ($ip) => is_string($ip) && $ip !== '')));

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
