<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PremiumLaunchPricing
{
    public const DEFAULT_PRICE_CENTS = 1200;
    private const SETTINGS_FILE = 'settings.json';
    private const SETTINGS_KEY = 'premium_launch_price_cents';

    public static function cents(): int
    {
        if (!Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            return self::DEFAULT_PRICE_CENTS;
        }

        $settings = json_decode(Storage::disk('local')->get(self::SETTINGS_FILE), true);
        $value = is_array($settings) ? ($settings[self::SETTINGS_KEY] ?? null) : null;

        if (!is_numeric($value)) {
            return self::DEFAULT_PRICE_CENTS;
        }

        return max(0, (int) $value);
    }

    public static function amount(): string
    {
        return number_format(self::cents() / 100, 2, '.', '');
    }

    public static function display(): string
    {
        $amount = self::amount();

        return preg_replace('/\.00$/', '', '$' . $amount) ?? ('$' . $amount);
    }
}
