<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ToolSettings
{
    public const TODO_LIST_KEY = 'todo_list';

    private const SETTINGS_FILE = 'settings.json';

    private const DEFAULTS = [
        self::TODO_LIST_KEY => [
            'slug' => 'todo-list-app',
            'legacy_slugs' => [
                'free-todo-list-tool',
            ],
        ],
    ];

    public function slug(string $toolKey): string
    {
        return $this->toolConfig($toolKey)['slug'];
    }

    public function path(string $toolKey): string
    {
        return '/tools/' . $this->slug($toolKey);
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

        if (!$normalizedSlug) {
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

    private function loadSettings(): array
    {
        if (!Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get(self::SETTINGS_FILE), true) ?: [];
    }

    private function normalizeSlug(mixed $value): ?string
    {
        $slug = Str::slug(trim((string) $value));

        return $slug !== '' ? $slug : null;
    }
}
