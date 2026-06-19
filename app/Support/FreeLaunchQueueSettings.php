<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class FreeLaunchQueueSettings
{
    public const DEFAULT_QUEUE_MONTHS = 6;
    private const SETTINGS_FILE = 'settings.json';
    private const SETTINGS_KEY = 'free_launch_queue_months';

    public static function months(): int
    {
        if (!Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            return self::DEFAULT_QUEUE_MONTHS;
        }

        $settings = json_decode(Storage::disk('local')->get(self::SETTINGS_FILE), true);
        $value = is_array($settings) ? ($settings[self::SETTINGS_KEY] ?? null) : null;

        if (!is_numeric($value)) {
            return self::DEFAULT_QUEUE_MONTHS;
        }

        return max(0, (int) $value);
    }
}
