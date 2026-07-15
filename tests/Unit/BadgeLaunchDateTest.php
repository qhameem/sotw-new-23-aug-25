<?php

namespace Tests\Unit;

use App\Services\BadgeService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BadgeLaunchDateTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('validLaunchDates')]
    public function test_badge_launch_accepts_any_date_starting_next_monday(string $date): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-16 12:00:00', 'UTC'));

        $resolved = app(BadgeService::class)->resolveBadgeLaunchDate($date);

        $this->assertSame($date.' 07:00:00', $resolved->format('Y-m-d H:i:s'));
    }

    public static function validLaunchDates(): array
    {
        return [
            'next Monday' => ['2026-07-20'],
            'Tuesday after next Monday' => ['2026-07-21'],
            'later non-Monday date' => ['2026-08-14'],
        ];
    }

    public function test_badge_launch_rejects_dates_before_next_monday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-16 12:00:00', 'UTC'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('starting from next Monday');

        app(BadgeService::class)->resolveBadgeLaunchDate('2026-07-19');
    }

    public function test_badge_launch_rejects_dates_beyond_365_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-16 12:00:00', 'UTC'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('within the next 365 days');

        app(BadgeService::class)->resolveBadgeLaunchDate('2027-07-17');
    }
}
