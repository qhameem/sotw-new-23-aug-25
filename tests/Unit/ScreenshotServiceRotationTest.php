<?php

namespace Tests\Unit;

use App\Services\ScreenshotService;
use PHPUnit\Framework\TestCase;

class ScreenshotServiceRotationTest extends TestCase
{
    public function test_weighted_sequence_is_reduced_by_greatest_common_divisor(): void
    {
        $service = new TestableScreenshotService();

        $sequence = $service->exposedBuildWeightedProviderSequence([
            ['name' => 'apiflash', 'weight' => 100],
            ['name' => 'screenshotone', 'weight' => 100],
            ['name' => 'snaprender', 'weight' => 500],
            ['name' => 'microlink', 'weight' => 50],
            ['name' => 'screenshotbase', 'weight' => 300],
        ]);

        self::assertCount(21, $sequence);
        self::assertSame(2, count(array_keys($sequence, 'apiflash', true)));
        self::assertSame(2, count(array_keys($sequence, 'screenshotone', true)));
        self::assertSame(10, count(array_keys($sequence, 'snaprender', true)));
        self::assertSame(1, count(array_keys($sequence, 'microlink', true)));
        self::assertSame(6, count(array_keys($sequence, 'screenshotbase', true)));
    }

    public function test_distinct_provider_order_starts_from_rotation_index(): void
    {
        $service = new TestableScreenshotService();

        $sequence = [
            'apiflash',
            'apiflash',
            'screenshotone',
            'screenshotone',
            'snaprender',
            'snaprender',
            'snaprender',
            'microlink',
            'screenshotbase',
            'screenshotbase',
        ];

        self::assertSame(
            ['snaprender', 'microlink', 'screenshotbase', 'apiflash', 'screenshotone'],
            $service->exposedDistinctProvidersFromSequence($sequence, 4)
        );
    }
}

class TestableScreenshotService extends ScreenshotService
{
    public function exposedBuildWeightedProviderSequence(array $snapshots): array
    {
        return $this->buildWeightedProviderSequence($snapshots);
    }

    public function exposedDistinctProvidersFromSequence(array $sequence, int $startIndex): array
    {
        return $this->distinctProvidersFromSequence($sequence, $startIndex);
    }
}
