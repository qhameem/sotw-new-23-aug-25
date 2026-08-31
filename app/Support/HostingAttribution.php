<?php

namespace App\Support;

class HostingAttribution
{
    public static function resolve(string $host, array $evidence): array
    {
        $cdns = array_values(array_unique(array_column(array_filter($evidence, fn ($item) => $item['role'] === 'cdn'), 'provider')));
        $platforms = [];
        $networks = [];
        foreach ($evidence as $item) {
            if (! $item['provider'] || $item['role'] === 'cdn') {
                continue;
            }
            if ($item['role'] === 'platform') {
                $platforms[$item['provider']][$item['type']] = true;
            } else {
                $networks[$item['provider']][$item['type']] = true;
            }
        }
        $provider = null;
        $reason = 'Insufficient evidence to identify the hosting provider.';
        if ($cdns !== []) {
            $reason = 'A CDN/proxy was detected; the origin hosting provider cannot be confirmed.';
        } elseif (count($platforms) === 1) {
            $candidate = array_key_first($platforms);
            $signals = $platforms[$candidate];
            if (isset($signals['cname']) || (isset($signals['http']) && isset($networks[$candidate]))) {
                $provider = $candidate;
                $reason = 'Hosting platform inferred from public signals; verify before submitting.';
            }
        } elseif (count($platforms) > 1 || count($networks) > 1) {
            $reason = 'Conflicting provider signals; enter the hosting provider manually if known.';
        } elseif (count($networks) === 1) {
            $candidate = array_key_first($networks);
            $signals = $networks[$candidate];
            if (isset($signals['rdap']) && (isset($signals['asn']) || isset($signals['ptr']))) {
                $provider = $candidate;
                $reason = 'Infrastructure provider inferred from matching network signals; the seller may be a reseller.';
            }
        }

        return [
            'hosting_provider' => $provider,
            'hosting_note' => $reason,
            'hosting_details' => [
                'status' => $provider ? 'inferred' : 'unknown',
                'provider' => $provider,
                'host' => $host,
                'cdn_providers' => $cdns,
                'evidence' => array_values($evidence),
            ],
        ];
    }

    public static function normalize(?string $provider, ?array $details, ?string $url): array
    {
        $host = strtolower(rtrim((string) parse_url($url ?? '', PHP_URL_HOST), '.'));
        $details ??= [];
        $expiredInference = ($details['status'] ?? '') === 'inferred' && ($details['host'] ?? '') !== $host;
        if (($details['host'] ?? '') !== $host) {
            $details = [];
        }
        $status = filled($provider) ? 'user_provided' : 'unknown';
        if ($expiredInference) {
            $status = 'unknown';
        } elseif (filled($provider) && ($details['provider'] ?? null) === $provider) {
            if (($details['status'] ?? '') === 'unknown') {
                $status = 'unknown';
            } elseif (($details['status'] ?? '') === 'inferred') {
                $resolved = self::resolve($host, $details['evidence'] ?? []);
                $status = $resolved['hosting_provider'] === $provider ? 'inferred' : 'unknown';
            }
        }

        return [
            'status' => $status,
            'provider' => $provider ?: null,
            'host' => $host,
            'cdn_providers' => $details['cdn_providers'] ?? [],
            'evidence' => $details['evidence'] ?? [],
        ];
    }

    public static function rules(): array
    {
        return [
            'hosting_details' => 'nullable|array:status,provider,host,cdn_providers,evidence',
            'hosting_details.status' => 'nullable|in:inferred,user_provided,unknown',
            'hosting_details.provider' => 'nullable|string|max:255',
            'hosting_details.host' => 'nullable|string|max:253',
            'hosting_details.cdn_providers' => 'nullable|array|max:10',
            'hosting_details.cdn_providers.*' => 'string|max:255',
            'hosting_details.evidence' => 'nullable|array|max:30',
            'hosting_details.evidence.*' => 'array:type,value,provider,role',
            'hosting_details.evidence.*.type' => 'required|in:cname,rdap,asn,ptr,http',
            'hosting_details.evidence.*.value' => 'required|string|max:500',
            'hosting_details.evidence.*.provider' => 'nullable|string|max:255',
            'hosting_details.evidence.*.role' => 'required|in:platform,infrastructure,cdn',
        ];
    }
}
