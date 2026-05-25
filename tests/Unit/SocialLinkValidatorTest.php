<?php

namespace Tests\Unit;

use App\Support\SocialLinkValidator;
use PHPUnit\Framework\TestCase;

class SocialLinkValidatorTest extends TestCase
{
    public function test_it_allows_social_profile_and_store_hosts_for_maker_links(): void
    {
        $allowedUrls = [
            'https://github.com/example/product',
            'https://apps.apple.com/us/app/example-app/id123456789',
            'https://play.google.com/store/apps/details?id=com.example.app',
            'https://chromewebstore.google.com/detail/example-extension/abcdefghijklmnopabcdefghijklmnop',
            'https://addons.mozilla.org/en-US/firefox/addon/example-extension/',
            'https://microsoftedge.microsoft.com/addons/detail/example-extension/abcdefghijklmnopabcdefghijklmnop',
            'https://addons.opera.com/en/extensions/details/example-extension/',
        ];

        foreach ($allowedUrls as $url) {
            self::assertTrue(SocialLinkValidator::isAllowedMakerLinkUrl($url), "Failed asserting that [{$url}] is allowed.");
        }
    }

    public function test_it_rejects_unapproved_hosts_for_maker_links(): void
    {
        self::assertFalse(SocialLinkValidator::isAllowedMakerLinkUrl('https://example.com/download'));
    }
}
