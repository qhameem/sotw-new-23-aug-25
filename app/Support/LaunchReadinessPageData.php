<?php

namespace App\Support;

use App\Models\ToolUser;
use Illuminate\Support\Facades\Auth;

class LaunchReadinessPageData
{
    public function __construct(
        private readonly ToolSettings $toolSettings,
        private readonly LaunchReadinessBranding $branding,
    ) {}

    public function merge(array $data = [], ?ToolUser $toolUser = null, ?string $currentHost = null): array
    {
        $toolUser ??= Auth::guard('tool_user')->user();
        $toolSlug = $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY);

        return array_merge([
            'toolSlug' => $toolSlug,
            'toolPath' => $this->toolSettings->path(ToolSettings::LAUNCH_READINESS_KEY),
            'toolUser' => $toolUser,
            'toolUserIsAdmin' => $toolUser?->isAdmin() ?? false,
            'toolOgImage' => $this->branding->publicOgImageUrl() ?? asset('images/tools/launch-readiness-og.svg'),
            'toolGoogleAuthEnabled' => ToolGoogleAuth::isAvailableForCurrentHost($currentHost ?? request()->getHost()),
            'toolGoogleAuthUnavailableReason' => ToolGoogleAuth::unavailableReason(),
            'toolBranding' => $this->branding->get(),
            'toolBrandingSiteName' => $this->branding->siteName(),
            'toolBrandingLogoUrl' => $this->branding->publicLogoUrl(),
            'toolBrandingFaviconUrl' => $this->branding->publicFaviconUrl(),
            'toolBrandingOgImageUrl' => $this->branding->publicOgImageUrl(),
            'toolBrandingGeneratedIconUrls' => $this->branding->publicGeneratedIconUrls(),
            'toolBrandingManifestUrl' => $this->branding->publicManifestUrl(),
            'toolHomepageH1' => $this->branding->homepageH1(),
            'toolHomepageTitleTag' => $this->branding->homepageTitleTag(),
            'toolHomepageMetaDescription' => $this->branding->homepageMetaDescription(),
            'toolBrandingFontUrl' => $this->branding->fontUrl(),
            'toolBrandingFontCssStack' => $this->branding->fontCssStack(),
            'toolBrandingFontSize' => $this->branding->fontSize(),
            'toolBrandingFontColor' => $this->branding->fontColor(),
            'toolBrandingBackgroundColor' => $this->branding->backgroundColor(),
        ], $data);
    }
}
