<?php

namespace App\Jobs;

use App\Models\Category;
use App\Services\CategoryClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class FetchCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;
    protected $textContent;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $url, string $textContent)
    {
        $this->url = $url;
        $this->textContent = $textContent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CategoryClassifier $classifier)
    {
        $suggestedCategories = $classifier->classify($this->textContent);

        $categoryIds = [];
        if (!empty($suggestedCategories)) {
            $categoryNames = array_keys($suggestedCategories);
            $categoryIds = Category::whereIn('name', $categoryNames)->pluck('id')->toArray();
        }

        $cacheKey = 'product_meta_' . md5($this->url);
        $cachedData = Cache::get($cacheKey, []);
        $cachedData['categories'] = array_unique($categoryIds);
        $cachedData['status'] = 'processing_logos';
        Cache::put($cacheKey, $cachedData, now()->addHours(24));

        FetchAndCacheLogo::dispatch($this->url);
    }
}
