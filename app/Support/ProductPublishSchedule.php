<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ProductPublishSchedule
{
    public const DEFAULT_PUBLISH_TIME = '07:00';

    public static function getPublishTime(): string
    {
        if (!Storage::disk('local')->exists('settings.json')) {
            return self::DEFAULT_PUBLISH_TIME;
        }

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);

        if (!is_array($settings)) {
            return self::DEFAULT_PUBLISH_TIME;
        }

        $publishTime = $settings['product_publish_time'] ?? self::DEFAULT_PUBLISH_TIME;

        return is_string($publishTime) && preg_match('/^\d{2}:\d{2}$/', $publishTime)
            ? $publishTime
            : self::DEFAULT_PUBLISH_TIME;
    }

    public static function getPublishHourMinute(): array
    {
        return array_map('intval', explode(':', self::getPublishTime()));
    }

    public static function forDate(Carbon|string $date): Carbon
    {
        [$hour, $minute] = self::getPublishHourMinute();

        $publishDate = $date instanceof Carbon
            ? $date->copy()->setTimezone('UTC')
            : Carbon::parse($date, 'UTC');

        return $publishDate->startOfDay()->setTime($hour, $minute, 0);
    }

    public static function nextLaunchTime(?Carbon $from = null): Carbon
    {
        [$hour, $minute] = self::getPublishHourMinute();

        $now = ($from ?? Carbon::now('UTC'))->copy()->setTimezone('UTC');
        $nextLaunch = $now->copy()->startOfDay()->setTime($hour, $minute, 0);

        if ($now->gte($nextLaunch)) {
            $nextLaunch->addDay();
        }

        return $nextLaunch;
    }
}
