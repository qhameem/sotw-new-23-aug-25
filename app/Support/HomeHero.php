<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class HomeHero
{
    public const DEFAULT_FEATURES = ['Weekly curated launches', 'Dedicated product page', 'Reach software enthusiasts'];

    public static function featureIcons(): array
    {
        $icons = self::settings()['feature_icons'] ?? [];

        return is_array($icons) ? $icons : [];
    }

    public static function saveFeatureIcon(int $index, string $path): void
    {
        $icons = self::featureIcons();
        $icons[$index] = $path;
        self::saveSettings(['feature_icons' => $icons]);
    }

    public static function features(): array
    {
        $saved = self::settings()['features'] ?? [];

        return collect(self::DEFAULT_FEATURES)->map(
            fn ($default, $index) => is_string($saved[$index] ?? null) && trim($saved[$index]) !== ''
                ? $saved[$index] : $default
        )->all();
    }

    public static function saveFeature(int $index, string $label): void
    {
        $features = self::features();
        $features[$index] = $label;
        self::saveSettings(['features' => $features]);
    }

    public const DEFAULT_DESCRIPTION = 'Discover handpicked tools for work and life. Building something? Submit your software to Software on the Web and give it a place in our weekly launches.';

    private static function settings(): array
    {
        $disk = Storage::disk('local');
        $saved = $disk->exists('home-hero.json') ? json_decode($disk->get('home-hero.json'), true) : [];

        return is_array($saved) ? $saved : [];
    }

    public static function description(): string
    {
        $description = self::settings()['description'] ?? null;

        return is_string($description) && trim($description) !== '' ? $description : self::DEFAULT_DESCRIPTION;
    }

    public static function saveDescription(string $description): void
    {
        self::saveSettings(['description' => $description]);
    }

    public const DEFAULT_HEADLINE = [
        'headline' => 'Great software deserves',
        'highlight' => 'to be discovered.',
    ];

    public static function headline(): array
    {
        $saved = self::settings();

        $headline = collect(self::DEFAULT_HEADLINE)->map(
            fn ($default, $key) => is_string($saved[$key] ?? null) && trim($saved[$key]) !== ''
                ? $saved[$key] : $default
        )->all();

        $phrases = is_array($saved['highlights'] ?? null) ? $saved['highlights'] : [$headline['highlight']];
        $headline['highlights'] = array_values(array_filter($phrases, fn ($phrase) => is_string($phrase) && trim($phrase) !== ''));
        $headline['highlights'] = $headline['highlights'] ?: [$headline['highlight']];
        $headline['highlight'] = $headline['highlights'][0];

        return $headline;
    }

    public static function saveHeadline(array $headline): void
    {
        self::saveSettings($headline);
    }

    private static function saveSettings(array $settings): void
    {
        if (! Storage::disk('local')->put('home-hero.json', json_encode(array_replace(self::settings(), $settings), JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('Unable to save homepage content.');
        }
    }
}
