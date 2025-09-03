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
        $client = new Client(['timeout' => 5]);
        $res = $client->get($this->url);
        $html = (string) $res->getBody();
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $title = $doc->getElementsByTagName('title')->item(0)?->textContent ?? '';
        $description = '';
        foreach ($doc->getElementsByTagName('meta') as $meta) {
            if (strtolower($meta->getAttribute('name')) === 'description') {
                $description = $meta->getAttribute('content');
                break;
            }
        }

        $nameExtractor = new NameExtractorService();
        $productName = $nameExtractor->extract($title, $this->url);

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
            'description' => $description,
            'tagline' => $description,
            'product_page_tagline' => $description,
            'og_image' => $ogImage,
            'status' => 'processing_categories',
        ], now()->addHours(24));

        $textContent = $title . ' ' . $description;
        foreach ($doc->getElementsByTagName('h1') as $h1) {
            $textContent .= ' ' . $h1->textContent;
        }
        foreach ($doc->getElementsByTagName('h2') as $h2) {
            $textContent .= ' ' . $h2->textContent;
        }
        $textContent .= ' ' . $doc->getElementsByTagName('body')->item(0)?->textContent ?? '';

        FetchCategories::dispatch($this->url, $textContent);
    }
}
