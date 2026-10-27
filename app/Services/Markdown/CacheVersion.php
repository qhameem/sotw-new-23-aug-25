<?php

namespace App\Services\Markdown;

use App\Support\SiteIdentity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheVersion
{
    public function remember(string $resource, int $seconds, callable $render): mixed
    {
        $identity = app(SiteIdentity::class);
        $key = 'agent.v3.'.hash('sha256', $identity->url().$resource);
        $version = hash('sha256', json_encode([$identity->organization(), config('agent_access'), app(Registry::class)->definitions()]).Cache::get('agent.content-version', 'initial'));
        $cached = Cache::get($key);
        if (is_array($cached) && ($cached['version'] ?? null) === $version) {
            return $cached['value'];
        }
        $value = $render();
        Cache::put($key, compact('version', 'value'), $seconds);

        return $value;
    }

    public function invalidate(): void
    {
        Cache::forever('agent.content-version', (string) Str::uuid());
    }
}
