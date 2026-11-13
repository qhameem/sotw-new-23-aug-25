<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HeaderCodeInjection
{
    private const SETTINGS_FILE = 'settings.json';

    public function forRequest(Request $request): string
    {
        if (! app(HeaderCodeVisibility::class)->shouldRender($request)
            || ! Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            return '';
        }

        $settings = json_decode(Storage::disk('local')->get(self::SETTINGS_FILE), true);

        if (! is_array($settings)) {
            return '';
        }

        $code = (string) ($settings['google_analytics_code'] ?? '');

        if ($request->routeIs('launch-readiness.*')
            || $request->is('tools/launch-readiness-checker', 'tools/launch-readiness-checker/*')) {
            $code = preg_replace(
                '~<script\b[^>]*\s+src\s*=\s*(["\x27])https://(?:www\.)?startupbar\.co/widget/loader\.js(?:[?#][^"\x27]*)?\1[^>]*>\s*</script\s*>~i',
                '',
                $code,
            ) ?? $code;
        }

        return trim($code);
    }
}
