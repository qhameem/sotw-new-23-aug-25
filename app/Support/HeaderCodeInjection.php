<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HeaderCodeInjection
{
    private const SETTINGS_FILE = 'settings.json';

    public function forRequest(Request $request): string
    {
        if ($request->routeIs('admin.*') || ! Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            return '';
        }

        $settings = json_decode(Storage::disk('local')->get(self::SETTINGS_FILE), true);

        if (! is_array($settings)) {
            return '';
        }

        return trim((string) ($settings['google_analytics_code'] ?? ''));
    }
}
