<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

class ProductPageMarkdown
{
    public function convert(string $html, string $url): string
    {
        if (trim($html) === '') {
            return '';
        }
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//script|//style|//noscript|//template|//svg|//nav|//footer|//aside|//*[@hidden]|//*[@aria-hidden="true"]') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }
        $title = trim($xpath->evaluate('string(//title)'));
        $body = $document->getElementsByTagName('body')->item(0) ?? $document;
        $markdown = ($title !== '' ? '# '.$this->plain($title)."\n\n" : '').$this->render($body, $url);

        return trim(preg_replace('/\n{3,}/', "\n\n", $markdown) ?? $markdown);
    }

    public function resolveUrl(string $base, string $href): ?string
    {
        try {
            $resolved = UriResolver::resolve(new Uri($base), new Uri(trim($href)));

            return in_array(strtolower($resolved->getScheme()), ['http', 'https'], true) && $resolved->getHost() !== ''
                ? (string) $resolved : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function limit(string $markdown, int $budget): string
    {
        if ($budget <= 0) {
            return '';
        }
        if (mb_strlen($markdown) <= $budget) {
            return $markdown;
        }
        $sections = preg_split('/\n(?=#{1,6} )/', $markdown, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ranked = [];
        foreach ($sections as $index => $section) {
            $heading = strtok($section, "\n");
            $score = preg_match('/pricing|plans?|features?|integrations?|platforms?|requirements?|faq|questions?|use cases?|who|audience|download/i', $heading) ? 3 : 0;
            $score += str_contains($section, '| ---') ? 4 : 0;
            $ranked[] = ['index' => $index, 'text' => $section, 'score' => $score + ($index === 0 ? 1 : 0)];
        }
        usort($ranked, fn ($a, $b) => ($b['score'] <=> $a['score']) ?: ($a['index'] <=> $b['index']));
        $selected = [];
        foreach ($ranked as $section) {
            $remaining = $budget - array_sum(array_map('mb_strlen', $selected)) - count($selected) * 2;
            if ($remaining <= 0) {
                break;
            }
            // Reserve space for multiple sections rather than spending the budget on one long introduction.
            $allowance = min($remaining, max(800, (int) floor($budget / min(4, max(1, count($sections))))));
            $selected[$section['index']] = $this->wholeBlocks($section['text'], $allowance);
        }
        ksort($selected);

        return implode("\n\n", array_filter($selected));
    }

    private function wholeBlocks(string $text, int $budget): string
    {
        if (mb_strlen($text) <= $budget) {
            return $text;
        }
        $prefix = mb_substr($text, 0, $budget);
        $boundary = mb_strrpos($prefix, "\n\n");
        if ($boundary !== false && $boundary > $budget / 2) {
            return rtrim(mb_substr($prefix, 0, $boundary));
        }
        $boundary = mb_strrpos($prefix, "\n");

        if ($boundary !== false && ($boundary > $budget / 2 || str_contains($prefix, '| ---'))) {
            return rtrim(mb_substr($prefix, 0, $boundary));
        }
        $space = mb_strrpos($prefix, ' ');

        return rtrim($space !== false && $space > $budget / 2 ? mb_substr($prefix, 0, $space) : $prefix);
    }

    private function render(DOMNode $node, string $url, int $depth = 0): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $this->plain($node->textContent);
        }
        if (! $node instanceof DOMElement && ! $node instanceof DOMDocument) {
            return '';
        }
        $tag = strtolower($node->nodeName);
        if ($tag === 'table') {
            return $this->table($node, $url);
        }
        $text = '';
        $isList = in_array($tag, ['ul', 'ol'], true);
        foreach ($node->childNodes as $child) {
            if ($isList && $child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                $item = trim($this->render($child, $url, $depth + 1));
                $text .= str_repeat('  ', $depth).($tag === 'ol' ? '1. ' : '- ').$item."\n";
            } else {
                $text .= $this->render($child, $url, $depth);
            }
        }
        $trimmed = trim($text);
        if (preg_match('/^h([1-6])$/', $tag, $match)) {
            return "\n\n".str_repeat('#', (int) $match[1]).' '.$trimmed."\n\n";
        }
        if ($tag === 'a' && $node instanceof DOMElement) {
            $href = $this->resolveUrl($url, $node->getAttribute('href'));

            return $href && $trimmed !== '' ? '['.str_replace(['[', ']'], ['\\[', '\\]'], $trimmed).']('.str_replace(['(', ')', ' '], ['%28', '%29', '%20'], $href).')' : $text;
        }
        if ($tag === 'br') {
            return "\n";
        }
        if ($tag === 'img' && $node instanceof DOMElement) {
            return $this->plain($node->getAttribute('alt'));
        }
        if (in_array($tag, ['p', 'div', 'section', 'article', 'main', 'header', 'form', 'dl', 'dt', 'dd', 'blockquote', 'pre', 'ul', 'ol'], true)) {
            return $trimmed === '' ? '' : "\n\n".$trimmed."\n\n";
        }

        return $text;
    }

    private function table(DOMElement $table, string $url): string
    {
        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $row) {
            $cells = [];
            foreach ($row->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->tagName), ['th', 'td'], true)) {
                    $value = trim(preg_replace('/\s+/u', ' ', $this->render($cell, $url)) ?? '');
                    $cells[] = str_replace('|', '\\|', $value);
                }
            }
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }
        if ($rows === []) {
            return '';
        }
        $width = max(array_map('count', $rows));
        $lines = [];
        foreach ($rows as $index => $cells) {
            $lines[] = '| '.implode(' | ', array_pad($cells, $width, '')).' |';
            if ($index === 0) {
                $lines[] = '| '.implode(' | ', array_fill(0, $width, '---')).' |';
            }
        }
        $caption = $table->getElementsByTagName('caption')->item(0)?->textContent;

        return "\n\n".($caption ? $this->plain($caption)."\n\n" : '').implode("\n", $lines)."\n\n";
    }

    private function plain(string $text): string
    {
        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }
}
