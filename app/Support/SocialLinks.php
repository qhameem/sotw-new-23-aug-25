<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class SocialLinks
{
    public const DEFAULT_X_PROFILE_URL = 'https://x.com/qHameem';

    public static function xProfileUrl(): string
    {
        $disk = Storage::disk('local');
        $settings = $disk->exists('settings.json')
            ? json_decode($disk->get('settings.json'), true)
            : [];

        return $settings['x_profile_url'] ?? self::DEFAULT_X_PROFILE_URL;
    }
}
