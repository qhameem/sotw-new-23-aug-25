<?php
namespace App\Services;

use App\Models\CustomPage;
use Illuminate\Support\Str;

class CustomPageContent
{
    public static function style(array $style): string
    {
        $css = [];
        if (isset($style['size']) && is_numeric($style['size'])) $css[] = 'font-size:'.max(10, min(96, (int) $style['size'])).'px';
        if (isset($style['weight']) && in_array((string) $style['weight'], ['300','400','500','600','700','800','900'], true)) $css[] = 'font-weight:'.$style['weight'];
        if (isset($style['color']) && preg_match('/\A#[a-fA-F0-9]{6}\z/', $style['color'])) $css[] = 'color:'.$style['color'];
        if (isset($style['align']) && in_array($style['align'], ['left','center','right','justify'], true)) $css[] = 'text-align:'.$style['align'];
        if (isset($style['leading']) && is_numeric($style['leading'])) $css[] = 'line-height:'.max(1, min(3, (float) $style['leading']));
        return implode(';', $css);
    }

    public static function render(CustomPage $page): string
    {
        $html = Str::markdown($page->body ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"><div id="custom-page-content">'.$html.'</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
            $counts = [];
            foreach ((new \DOMXPath($dom))->query('//h1|//h2|//h3|//h4|//h5|//h6|//p|//li|//th|//td|//blockquote|//pre') as $node) {
                $identity = $node->nodeName.':'.$node->textContent;
                $occurrence = $counts[$identity] ?? 0;
                $counts[$identity] = $occurrence + 1;
                $key = hash('sha256', $identity.':'.$occurrence);
                $node->setAttribute('data-page-style-key', $key);
                $node->setAttribute('style', self::style(($page->text_styles ?? [])[$key] ?? []));
            }
            $root = $dom->getElementById('custom-page-content');
            $result = '';
            foreach ($root->childNodes as $child) $result .= $dom->saveHTML($child);
            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
