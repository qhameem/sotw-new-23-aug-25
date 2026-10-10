<?php

namespace App\Services;

class PlatformDetector
{
    public function detect(string $source): array
    {
        $rules = [
            'MacOS' => '~\.dmg\b|apps\.apple\.com/(?:[^\s"<>]*/)?mac\b|mac app store~i',
            'Windows' => '~\.(?:exe|msi)\b|apps\.microsoft\.com|microsoft store~i',
            'iOS' => '~apps\.apple\.com/(?![^\s"<>]*/mac\b)|\b(?:ios|iphone|ipad) app store\b~i',
            'Android' => '~play\.google\.com|\.apk\b~i',
            'Chrome extension' => '~chromewebstore\.google\.com|chrome\.google\.com/webstore~i',
        ];
        $platforms = [];
        foreach ($rules as $name => $pattern) {
            if (preg_match($pattern, $source)) {
                $platforms[] = $name;
            }
        }
        if (preg_match('~(?:https?://)?(?:app|login|signin)\.[^\s/]+|/(?:app|sign-?in|login)\b~i', $source)
            && preg_match('~\b(?:web app|browser-based|in your browser|online dashboard)\b~i', $source)) {
            $platforms[] = 'Browser';
        }

        return $platforms;
    }
}
