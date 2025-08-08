<?php

namespace App\Helpers;

class HtmlHelper
{
    /**
     * Add rel="ugc nofollow" to all links in a HTML string.
     *
     * @param string $html
     * @return string
     */
    public static function addNofollowToLinks(string $html): string
    {
        if (empty($html)) {
            return '';
        }
        
        return preg_replace_callback(
            '/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/',
            function ($matches) {
                return '<a href="' . $matches[1] . '" rel="ugc nofollow"';
            },
            $html
        );
    }
}