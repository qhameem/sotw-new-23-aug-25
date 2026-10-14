<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

class ExpandableEditorialLists
{
    public static function render(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?><div id="editorial-lists">'.$html.'</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return $html;
        }

        $xpath = new DOMXPath($document);
        $root = $xpath->query('//*[@id="editorial-lists"]')->item(0);
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $lists = iterator_to_array($xpath->query('./ul | ./ol', $root));
        foreach ($lists as $list) {
            $items = iterator_to_array($xpath->query('./li', $list));
            if (count($items) <= 4) {
                continue;
            }

            $remaining = $list->cloneNode(false);
            $remaining->removeAttribute('id');
            if ($list->tagName === 'ol') {
                $start = $list->hasAttribute('start') ? (int) $list->getAttribute('start') : 1;
                $step = $list->hasAttribute('reversed') ? -1 : 1;
                if ($list->hasAttribute('reversed') && ! $list->hasAttribute('start')) {
                    $start = count($items);
                    $list->setAttribute('start', (string) $start);
                }
                foreach (array_slice($items, 0, 4) as $item) {
                    $start = ($item->hasAttribute('value') ? (int) $item->getAttribute('value') : $start) + $step;
                }
                $remaining->setAttribute('start', (string) $start);
            }
            foreach (array_slice($items, 4) as $item) {
                $remaining->appendChild($item);
            }

            $details = $document->createElement('details');
            $summary = $document->createElement('summary', 'Show more');
            $summary->setAttribute('class', 'cursor-pointer text-sm font-semibold');
            $details->appendChild($summary);
            $details->appendChild($remaining);
            $root->insertBefore($details, $list->nextSibling);
        }

        $result = '';
        foreach ($root->childNodes as $node) {
            $result .= $document->saveHTML($node);
        }

        return $result;
    }
}
