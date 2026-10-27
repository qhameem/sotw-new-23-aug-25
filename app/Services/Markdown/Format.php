<?php

namespace App\Services\Markdown;

use App\Services\ProductPageMarkdown;
use App\Support\SiteIdentity;

class Format
{
    public function text(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    public function escape(mixed $value): string
    {
        return preg_replace('/([\\\\`*_{}\[\]<>#!|])/', '\\\\$1', $this->text($value)) ?? '';
    }

    public function link(mixed $label, string $path): string
    {
        $url = app(ProductPageMarkdown::class)->resolveUrl(app(SiteIdentity::class)->url(), $path);

        return $url ? '['.$this->escape($label).']('.str_replace(['(', ')', ' '], ['%28', '%29', '%20'], $url).')' : $this->escape($label);
    }

    public function html(?string $content, string $canonical): string
    {
        return app(ProductPageMarkdown::class)->convert($content ?? '', $canonical);
    }

    public function table(array $rows): string
    {
        $lines = ['| Field | Value |', '| --- | --- |'];
        foreach ($rows as $name => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = '| '.$this->escape($name).' | '.$this->escape($value).' |';
            }
        }

        return implode("\n", $lines)."\n";
    }

    public function page(string $title, string $summary, string $canonical, string $body, mixed $updated = null, array $related = []): array
    {
        $date = $updated ? \Illuminate\Support\Carbon::parse($updated)->toDateString() : null;
        $markdown = '# '.$this->escape($title)."\n\n> ".$this->escape($summary)."\n\nCanonical: ".$canonical."\n";
        if ($date) {
            $markdown .= 'Last updated: '.$date."\n";
        }
        $markdown .= "\n".trim($body)."\n";
        if ($related) {
            $markdown .= "\n## Related\n";
            foreach ($related as $label => $url) {
                $markdown .= '- '.$this->link($label, $url)."\n";
            }
        }

        return compact('title', 'summary', 'canonical', 'markdown', 'date');
    }
}
