<?php

namespace App\Jobs;

use App\Services\FaviconExtractorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class FetchAndCacheLogo implements ShouldQueue
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
    public function handle(FaviconExtractorService $faviconExtractor)
    {
        $logos = $faviconExtractor->extract($this->url);
        
        $cacheKey = 'product_meta_' . md5($this->url);
        $cachedData = Cache::get($cacheKey, []);
        $cachedData['logos'] = $logos;
        $cachedData['status'] = 'completed';
        Cache::put($cacheKey, $cachedData, now()->addHours(24));
    }
}
