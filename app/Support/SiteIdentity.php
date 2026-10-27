<?php

namespace App\Support;

class SiteIdentity
{
    public function name(): string
    {
        return (string) config('app.name');
    }

    public function url(string $path = '/'): string
    {
        return rtrim(config('app.url'), '/').($path === '/' ? '/' : '/'.ltrim($path, '/'));
    }

    public function description(): string
    {
        return config('agent_access.description');
    }

    public function organization(): array
    {
        $logo = config('theme.logo_url');
        $logo = $logo ? (str_starts_with($logo, 'http') ? $logo : $this->url('/storage/'.ltrim($logo, '/'))) : $this->url('/favicon/android-chrome-512x512.png');

        return [
            '@type' => 'Organization', '@id' => $this->url().'#organization',
            'name' => $this->name(), 'url' => rtrim($this->url(), '/'),
            'description' => $this->description(), 'logo' => $logo,
            'email' => config('agent_access.contact'), 'sameAs' => config('agent_access.socials'),
        ];
    }
}
