<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use GuzzleHttp\Client;
use App\Services\CategoryClassifier;
use App\Services\FaviconExtractorService;
use App\Jobs\FetchAndCacheLogo;
use App\Jobs\FetchBasicInfo;
use Illuminate\Support\Facades\Cache;

class ProductMetaController extends Controller
{
    protected $classifier;

    public function __construct(CategoryClassifier $classifier)
    {
        $this->classifier = $classifier;
    }

    public function __invoke(Request $request)
    {
        $url = $request->query('url');
        if (!$url) {
            return response()->json(['error' => 'No URL provided'], 400);
        }

        FetchBasicInfo::dispatch($url);

        return response()->json(['message' => 'Processing started']);
    }

    private function findPricingPage(string $baseUrl, string $html): ?string
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $links = $doc->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (preg_match('/(pricing|plans|subscribe)/i', $href)) {
                // Handle relative vs absolute URLs
                if (Str::startsWith($href, ['http', '//'])) {
                    return $href;
                }
                $parsedUrl = parse_url($baseUrl);
                return $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/' . ltrim($href, '/');
            }
        }

        return null;
    }
    public function getCachedLogos(Request $request)
    {
        $url = $request->query('url');
        if (!$url) {
            return response()->json(['error' => 'No URL provided'], 400);
        }

        $cacheKey = 'product_meta_' . md5($url);

        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        return response()->json(['status' => 'pending']);
    }
}
