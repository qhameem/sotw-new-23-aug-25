<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class LaunchReadinessBadges
{
    public function all(): array
    {
        $settings = $this->settings();

        return $settings['tools'][ToolSettings::LAUNCH_READINESS_KEY]['footer_badges'] ?? [];
    }

    public function save(array $badges): void
    {
        $settings = $this->settings();
        $settings['tools'][ToolSettings::LAUNCH_READINESS_KEY]['footer_badges'] = array_values($badges);

        Storage::disk('local')->put('settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function settings(): array
    {
        $disk = Storage::disk('local');

        return $disk->exists('settings.json') ? (json_decode($disk->get('settings.json'), true) ?: []) : [];
    }
}
