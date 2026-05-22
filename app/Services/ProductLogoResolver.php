<?php

namespace App\Services;

use App\Support\ProductLogo;

class ProductLogoResolver
{
    public function __construct(
        protected LogoExtractorService $logoExtractor,
        protected FaviconExtractorService $faviconExtractor,
    ) {
    }

    public function resolvePreferredLogoUrl(?string $productUrl, ?string $submittedLogoUrl = null): ?string
    {
        $submittedLogoUrl = is_string($submittedLogoUrl) ? trim($submittedLogoUrl) : null;

        if ($submittedLogoUrl !== null && $submittedLogoUrl !== '' && !ProductLogo::isBlockedExternalFaviconUrl($submittedLogoUrl)) {
            return $submittedLogoUrl;
        }

        return $this->discoverReplacementLogoUrl($productUrl);
    }

    public function discoverReplacementLogoUrl(?string $productUrl): ?string
    {
        $productUrl = is_string($productUrl) ? trim($productUrl) : null;

        if ($productUrl === null || $productUrl === '') {
            return null;
        }

        $logoCandidates = $this->safeExtract(fn () => $this->logoExtractor->extract($productUrl));
        $resolvedLogo = $this->firstUsableCandidate($logoCandidates);

        if ($resolvedLogo) {
            return $resolvedLogo;
        }

        $faviconCandidates = $this->safeExtract(fn () => $this->faviconExtractor->extract($productUrl));

        return $this->firstUsableCandidate($faviconCandidates);
    }

    protected function firstUsableCandidate(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);

            if ($candidate === '' || ProductLogo::isBlockedExternalFaviconUrl($candidate)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    protected function safeExtract(callable $extractor): array
    {
        try {
            $result = $extractor();

            return is_array($result) ? $result : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
