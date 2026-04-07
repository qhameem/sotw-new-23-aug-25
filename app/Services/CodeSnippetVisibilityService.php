<?php

namespace App\Services;

use App\Models\CodeSnippet;
use Illuminate\Http\Request;

class CodeSnippetVisibilityService
{
    public function shouldRender(CodeSnippet $snippet, Request $request): bool
    {
        $requestIp = $this->resolveIpAddress($request);

        if ($requestIp !== null && in_array($requestIp, $snippet->excluded_ips ?? [], true)) {
            return false;
        }

        $countryCode = $this->resolveCountryCode($request);

        if ($countryCode !== null && in_array($countryCode, $snippet->excluded_countries ?? [], true)) {
            return false;
        }

        return true;
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
