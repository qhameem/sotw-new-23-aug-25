<?php

namespace App\Support;

class HostingAttribution
{
    public static function resolve(string $host, array $evidence): array
    {
        $cdns = array_values(array_unique(array_column(array_filter($evidence, fn ($item) => $item['role'] === 'cdn' && filled($item['provider'] ?? null)), 'provider')));
        // Heuristic evidence strength, not a calibrated probability of correctness.
        $weights = ['cname' => 90, 'rdap' => 45, 'asn' => 30, 'ptr' => 10, 'http' => 20];
        $signals = [];
        foreach ($evidence as $item) {
            if (! filled($item['provider'] ?? null) || $item['role'] === 'cdn') {
                continue;
            }
            // Repeated IPs/records of the same type must not inflate confidence.
            $signals[$item['provider']][$item['type']] = $weights[$item['type']] ?? 0;
        }
        $candidates = [];
        foreach ($signals as $name => $types) {
            // Platform DNS outranks the platform's underlying cloud infrastructure.
            $score = min(isset($types['cname']) ? 95 : 80, array_sum($types));
            $candidates[] = ['provider' => $name, 'confidence' => $score, 'confidence_label' => self::confidenceLabel($score)];
        }
        usort($candidates, fn ($a, $b) => ($b['confidence'] <=> $a['confidence']) ?: strcmp($a['provider'], $b['provider']));
        $winner = $candidates[0] ?? null;
        $tied = isset($candidates[1]) && $winner['confidence'] === $candidates[1]['confidence'];
        $provider = null;
        $confidence = null;
        $reason = 'Not enough evidence to identify hosting.';
        if ($cdns !== []) {
            $reason = 'CDN detected; origin host unknown.';
        } elseif ($tied) {
            $reason = 'Conflicting providers have equal confidence.';
        } elseif ($winner && $winner['confidence'] >= 30) {
            $provider = $winner['provider'];
            $confidence = $winner['confidence'];
            $reason = 'Highest-confidence hosting match; editable.';
        }

        return [
            'hosting_provider' => $provider,
            'hosting_note' => $reason,
            'hosting_details' => [
                'status' => $provider ? 'inferred' : 'unknown',
                'provider' => $provider,
                'confidence' => $confidence,
                'confidence_label' => $confidence === null ? null : self::confidenceLabel($confidence),
                'candidates' => $candidates,
                'host' => $host,
                'cdn_providers' => $cdns,
                'evidence' => array_values($evidence),
            ],
        ];
    }

    private static function confidenceLabel(int $score): string
    {
        return $score >= 85 ? 'high' : ($score >= 60 ? 'medium' : 'low');
    }

    public static function normalize(?string $provider, ?array $details, ?string $url): array
    {
        $host = strtolower(rtrim((string) parse_url($url ?? '', PHP_URL_HOST), '.'));
        $details ??= [];
        $expiredInference = ($details['status'] ?? '') === 'inferred' && ($details['host'] ?? '') !== $host;
        if (($details['host'] ?? '') !== $host) {
            $details = [];
        }
        $resolved = self::resolve($host, $details['evidence'] ?? []);
        $status = filled($provider) ? 'user_provided' : 'unknown';
        if ($expiredInference) {
            $status = 'unknown';
        } elseif (filled($provider) && ($details['provider'] ?? null) === $provider) {
            if (($details['status'] ?? '') === 'unknown') {
                $status = 'unknown';
            } elseif (($details['status'] ?? '') === 'inferred') {
                $status = $resolved['hosting_provider'] === $provider ? 'inferred' : 'unknown';
            }
        }

        return [
            'status' => $status,
            'provider' => $provider ?: null,
            'confidence' => $status === 'inferred' ? $resolved['hosting_details']['confidence'] : null,
            'confidence_label' => $status === 'inferred' ? $resolved['hosting_details']['confidence_label'] : null,
            'candidates' => $resolved['hosting_details']['candidates'],
            'host' => $host,
            'cdn_providers' => $details['cdn_providers'] ?? [],
            'evidence' => $details['evidence'] ?? [],
        ];
    }

    public static function rules(): array
    {
        return [
            'hosting_details' => 'nullable|array:status,provider,host,cdn_providers,evidence,confidence,confidence_label,candidates',
            'hosting_details.confidence' => 'nullable|integer|min:0|max:100',
            'hosting_details.confidence_label' => 'nullable|in:low,medium,high',
            'hosting_details.candidates' => 'nullable|array|max:30',
            'hosting_details.candidates.*' => 'array:provider,confidence,confidence_label',
            'hosting_details.candidates.*.provider' => 'required|string|max:255',
            'hosting_details.candidates.*.confidence' => 'required|integer|min:0|max:100',
            'hosting_details.candidates.*.confidence_label' => 'required|in:low,medium,high',
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
