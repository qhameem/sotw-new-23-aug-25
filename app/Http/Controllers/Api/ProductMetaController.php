<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;

class ProductMetaController extends Controller
{
    public function __invoke(Request $request)
    {
        $url = $request->query('url');
        if (!$url) {
            return response()->json(['error' => 'No URL provided'], 400);
        }
        try {
            $client = new Client(['timeout' => 5]);
            $res = $client->get($url);
            $html = (string) $res->getBody();
            $doc = new \DOMDocument();
            @$doc->loadHTML($html);
            $title = $doc->getElementsByTagName('title')->item(0)?->textContent ?? '';
            $description = '';
            $favicon = '';
            foreach ($doc->getElementsByTagName('meta') as $meta) {
                if (strtolower($meta->getAttribute('name')) === 'description') {
                    $description = $meta->getAttribute('content');
                }
            }
            foreach ($doc->getElementsByTagName('link') as $link) {
                $rel = strtolower($link->getAttribute('rel'));
                if (str_contains($rel, 'icon')) {
                    $favicon = $link->getAttribute('href');
                    if (!Str::startsWith($favicon, ['http', '//'])) {
                        $parsed = parse_url($url);
                        $base = $parsed['scheme'] . '://' . $parsed['host'];
                        $favicon = $base . '/' . ltrim($favicon, '/');
                    }
                    break;
                }
            }
            $slug = Str::slug($title);
            return response()->json([
                'title' => $title,
                'description' => $description,
                'favicon' => $favicon,
                'slug' => $slug,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch meta'], 500);
        }
    }
}
