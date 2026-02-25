<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use App\Services\NameExtractorService;
use App\Services\DescriptionRewriterService;
use App\Services\ScreenshotService;

class FetchBasicInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new Client([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
        $res = $client->get($this->url);
        $html = (string) $res->getBody();
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $title = $doc->getElementsByTagName('title')->item(0)?->textContent ?? '';
        $rawDescription = '';
        foreach ($doc->getElementsByTagName('meta') as $meta) {
            if (strtolower($meta->getAttribute('name')) === 'description') {
                $rawDescription = $meta->getAttribute('content');
                break;
            }
        }

        $nameExtractor = new NameExtractorService();
        $productName = $nameExtractor->extract($title, $this->url);

        // Gather page text for additional context (cleaned of noise)
        $cleanDoc = clone $doc;
        $xpath = new \DOMXPath($cleanDoc);
        $noise = $xpath->query('//nav | //header | //footer | //script | //style | //noscript | //aside');
        foreach ($noise as $node) {
            $node->parentNode?->removeChild($node);
        }

        $textContent = "Title: {$title}\nDescription: {$rawDescription}\n";
        foreach (['h1', 'h2', 'h3'] as $tag) {
            foreach ($cleanDoc->getElementsByTagName($tag) as $node) {
                $textContent .= "\n" . strtoupper($tag) . ": " . trim($node->textContent);
            }
        }
        $textContent .= "\n\nBODY CONTENT:\n" . trim($cleanDoc->getElementsByTagName('body')->item(0)?->textContent ?? '');

        // Rewrite description with Groq AI (falls back to raw if API fails)
        $rewriter = new DescriptionRewriterService();
        $structuredDescription = $rewriter->rewrite($productName, $rawDescription, $textContent)
            ?? $rawDescription;

        // Generate AI taglines with Groq (falls back to raw description if API fails)
        $taglineRewriter = new \App\Services\TaglineRewriterService();
        $aiTaglines = $taglineRewriter->rewrite($productName, $rawDescription, $textContent);

        $tagline = $aiTaglines['tagline'] ?? $rawDescription;
        $detailedTagline = $aiTaglines['product_page_tagline'] ?? $rawDescription;

        $cacheKey = 'product_meta_' . md5($this->url);
        $ogImage = null;
        foreach ($doc->getElementsByTagName('meta') as $meta) {
            if ($meta->getAttribute('property') === 'og:image') {
                $ogImage = $meta->getAttribute('content');
                break;
            }
        }

        Cache::put($cacheKey, [
            'title' => $productName,
            'description' => $structuredDescription,
            'tagline' => $tagline,
            'product_page_tagline' => $detailedTagline,
            'og_image' => $ogImage,
            'status' => 'processing_categories',
        ], now()->addHours(24));

        FetchCategories::dispatch($this->url, $textContent);
    }
}
