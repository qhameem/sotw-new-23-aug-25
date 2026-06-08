<?php

namespace App\Services;

use App\Models\CodeSnippet;
use Illuminate\Http\Request;

class CodeSnippetVisibilityService
{
    public function __construct(
        protected ?RequestLocationService $locationService = null
    ) {
        $this->locationService ??= app(RequestLocationService::class);
    }

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
        return $this->locationService->resolveIpAddress($request);
    }

    public function resolveCountryCode(Request $request): ?string
    {
        return $this->locationService->resolveCountryCode($request);
    }
}
