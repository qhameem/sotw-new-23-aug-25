<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SubmissionSuccessModalSettings
{
    private const PATH = 'submission-success-modal.json';

    public static function get(): array
    {
        $settings = Storage::disk('local')->json(self::PATH);

        return array_replace([
            'enabled' => false,
            'delay' => 3,
            'title' => '',
            'body' => '',
            'ad_type' => 'none',
            'offer_disclosure' => '',
            'offer_bullet' => 'check',
            'offer_width' => 'compact',
            'offer_button_width' => 'full',
        ], is_array($settings) ? $settings : []);
    }

    public static function save(array $settings): void
    {
        $path = Storage::disk('local')->path(self::PATH);
        File::ensureDirectoryExists(dirname($path));
        File::replace($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
