<?php

namespace Tests\Unit;

use App\Support\ExtractionPageGuard;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ExtractionPageGuardTest extends TestCase
{
    public function test_challenge_and_error_titles_are_rejected(): void
    {
        foreach (['Just a moment...', 'Access Denied', 'Vercel Security Checkpoint', '403 Forbidden'] as $title) {
            $this->assertTrue(ExtractionPageGuard::isBlockedTitle($title));
        }
        $this->expectException(InvalidArgumentException::class);
        ExtractionPageGuard::assertUsable('<html><title>Just a moment...</title></html>');
    }

    public function test_real_product_content_is_not_rejected_for_mentioning_verification(): void
    {
        ExtractionPageGuard::assertUsable('<title>FeelMyMac</title><p>Verify you are human with our product.</p>');
        $this->assertFalse(ExtractionPageGuard::isBlockedTitle('Access management for teams'));
    }
}
