<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ToolSettings
{
    public const TODO_LIST_KEY = 'todo_list';

    public const LAUNCH_READINESS_KEY = 'launch_readiness';

    private const SETTINGS_FILE = 'settings.json';

    private const DEFAULTS = [
        self::TODO_LIST_KEY => [
            'slug' => 'todo-list-app',
            'legacy_slugs' => [
                'free-todo-list-tool',
            ],
        ],
        self::LAUNCH_READINESS_KEY => [
            'slug' => 'launch-readiness-checker',
            'legacy_slugs' => [
                'site-health',
            ],
        ],
    ];

    public function slug(string $toolKey): string
    {
        return $this->toolConfig($toolKey)['slug'];
    }

    public function path(string $toolKey): string
    {
        return '/tools/'.$this->slug($toolKey);
    }

    public function url(string $toolKey): string
    {
        return url($this->path($toolKey));
    }

    public function isCurrentSlug(string $toolKey, ?string $slug): bool
    {
        return $this->normalizeSlug($slug) === $this->slug($toolKey);
    }

    public function isLegacySlug(string $toolKey, ?string $slug): bool
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        if (! $normalizedSlug) {
            return false;
        }

        return in_array($normalizedSlug, $this->toolConfig($toolKey)['legacy_slugs'], true);
    }

    public function toolConfig(string $toolKey): array
    {
        $defaults = self::DEFAULTS[$toolKey] ?? [
            'slug' => Str::slug($toolKey),
            'legacy_slugs' => [],
        ];

        $settings = $this->loadSettings();
        $configuredTool = $settings['tools'][$toolKey] ?? [];

        $slug = $this->normalizeSlug($configuredTool['slug'] ?? null) ?: $defaults['slug'];

        $legacySlugs = collect($defaults['legacy_slugs'] ?? [])
            ->merge($configuredTool['legacy_slugs'] ?? [])
            ->map(fn ($value) => $this->normalizeSlug($value))
            ->filter()
            ->reject(fn ($value) => $value === $slug)
            ->unique()
            ->values()
            ->all();

        return [
            'slug' => $slug,
            'legacy_slugs' => $legacySlugs,
        ];
    }

    public function candidateSlug(mixed $value): ?string
    {
        return $this->normalizeSlug($value);
    }

    public function slugAvailable(string $toolKey, mixed $value): bool
    {
        $candidate = $this->normalizeSlug($value);

        if (! $candidate) {
            return false;
        }

        foreach (array_keys(self::DEFAULTS) as $otherToolKey) {
            if ($otherToolKey === $toolKey) {
                continue;
            }

            $config = $this->toolConfig($otherToolKey);

            if ($config['slug'] === $candidate) {
                return false;
            }

            if (in_array($candidate, $config['legacy_slugs'], true)) {
                return false;
            }
        }

        return true;
    }

    public function updateSlug(string $toolKey, mixed $value): string
    {
        $slug = $this->normalizeSlug($value);

        if (! $slug) {
            throw new \InvalidArgumentException('A valid tool slug is required.');
        }

        $settings = $this->loadSettings();
        $toolSettings = is_array($settings['tools'][$toolKey] ?? null) ? $settings['tools'][$toolKey] : [];
        $currentConfig = $this->toolConfig($toolKey);
        $currentSlug = $currentConfig['slug'];

        $legacySlugs = collect($toolSettings['legacy_slugs'] ?? [])
            ->merge($currentConfig['legacy_slugs'] ?? [])
            ->when($currentSlug !== $slug, fn ($collection) => $collection->push($currentSlug))
            ->map(fn ($legacySlug) => $this->normalizeSlug($legacySlug))
            ->filter()
            ->reject(fn ($legacySlug) => $legacySlug === $slug)
            ->unique()
            ->values()
            ->all();

        $toolSettings['slug'] = $slug;
        $toolSettings['legacy_slugs'] = $legacySlugs;
        $settings['tools'][$toolKey] = $toolSettings;

        $this->saveSettings($settings);

        return $slug;
    }

    private function loadSettings(): array
    {
        if (! Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get(self::SETTINGS_FILE), true) ?: [];
    }

    private function saveSettings(array $settings): void
    {
        Storage::disk('local')->put(self::SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function normalizeSlug(mixed $value): ?string
    {
        $slug = Str::slug(trim((string) $value));

        return $slug !== '' ? $slug : null;
    }
}
