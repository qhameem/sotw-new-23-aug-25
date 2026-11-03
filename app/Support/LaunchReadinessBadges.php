<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class LaunchReadinessBadges
{
    public function inlineHtml(string $code): ?string
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8"><div id="badge-root">'.$code.'</div>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $root = $document->getElementById('badge-root');
        if (! $root || $root->getElementsByTagName('img')->length === 0) {
            return null;
        }

        $allowed = [
            'a' => ['href', 'target', 'rel', 'title'],
            'img' => ['src', 'alt', 'width', 'height', 'title', 'loading'],
        ];

        foreach ($document->getElementsByTagName('*') as $element) {
            if (in_array($element->tagName, ['html', 'body'], true) || $element === $root) {
                continue;
            }
            if (! isset($allowed[$element->tagName])) {
                return null;
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                if (! in_array($attribute->name, $allowed[$element->tagName], true)) {
                    $element->removeAttribute($attribute->name);
                }
            }
            foreach (['href', 'src'] as $attribute) {
                if ($element->hasAttribute($attribute)
                    && ! preg_match('~^https?://~i', $element->getAttribute($attribute))) {
                    return null;
                }
            }
            if ($element->tagName === 'a' && $element->getAttribute('target') === '_blank') {
                $tokens = preg_split('/\s+/', trim($element->getAttribute('rel'))) ?: [];
                $element->setAttribute('rel', trim(implode(' ', array_unique([...$tokens, 'noopener']))));
            }
        }

        $html = '';
        foreach ($root->childNodes as $node) {
            $html .= $document->saveHTML($node);
        }

        return $html;
    }

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
