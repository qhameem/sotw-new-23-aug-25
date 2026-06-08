<?php

namespace App\Services;

use App\Support\CountryOptions;
use Illuminate\Http\Request;

class RequestLocationService
{
    public function resolve(Request $request): array
    {
        $countryCode = $this->resolveCountryCode($request);

        return [
            'ip_address' => $this->resolveIpAddress($request),
            'country_code' => $countryCode,
            'country_name' => $this->resolveCountryName($request, $countryCode),
            'city' => $this->resolveCity($request),
        ];
    }

    public function resolveIpAddress(Request $request): ?string
    {
        $candidates = [
            $request->header('CF-Connecting-IP'),
            $request->header('X-Real-IP'),
            $this->firstForwardedIp($request->header('X-Forwarded-For')),
            $request->ip(),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var(trim($candidate), FILTER_VALIDATE_IP)) {
                return trim($candidate);
            }
        }

        return null;
    }

    public function resolveCountryCode(Request $request): ?string
    {
        $candidates = [
            $request->header('CF-IPCountry'),
            $request->header('CloudFront-Viewer-Country'),
            $request->header('X-Country-Code'),
            $request->header('X-Country'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $normalized = strtoupper(trim($candidate));

            if (preg_match('/^[A-Z]{2}$/', $normalized) === 1) {
                return $normalized;
            }
        }

        return null;
    }

    public function resolveCountryName(Request $request, ?string $countryCode = null): ?string
    {
        $candidates = [
            $request->header('CF-IPCountry-Name'),
            $request->header('X-Country-Name'),
            $request->header('CloudFront-Viewer-Country-Name'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        $countryCode ??= $this->resolveCountryCode($request);

        if (! $countryCode) {
            return null;
        }

        static $countries;

        $countries ??= CountryOptions::all();

        return $countries[$countryCode] ?? null;
    }

    public function resolveCity(Request $request): ?string
    {
        $candidates = [
            $request->header('CF-IPCity'),
            $request->header('X-City'),
            $request->header('CloudFront-Viewer-City'),
            $request->header('X-Appengine-City'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $normalized = trim($candidate);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    protected function firstForwardedIp(?string $forwardedFor): ?string
    {
        if (! is_string($forwardedFor) || trim($forwardedFor) === '') {
            return null;
        }

        $parts = explode(',', $forwardedFor);
        $first = trim($parts[0] ?? '');

        return $first !== '' ? $first : null;
    }
}
