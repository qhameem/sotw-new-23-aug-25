<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\RelatedProductService;
use App\Services\SitemapIndexWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateAlternativesSitemap extends Command
{
    private const DATABASE_CHUNK_SIZE = 250;

    protected $signature = 'sitemap:generate-alternatives
        {--chunk-size=10000 : Maximum URLs per alternatives sitemap file}';

    protected $description = 'Generate indexable alternatives sitemaps without rebuilding comparison URLs';

    public function handle(RelatedProductService $relatedProducts, SitemapIndexWriter $indexWriter): int
    {
        $chunkSize = filter_var($this->option('chunk-size'), FILTER_VALIDATE_INT);

        if ($chunkSize === false || $chunkSize < 1 || $chunkSize > 50000) {
            $this->error('The --chunk-size value must be between 1 and 50000.');

            return self::FAILURE;
        }

        $sitemapDirectory = public_path('sitemaps');
        File::ensureDirectoryExists($sitemapDirectory);

        foreach (File::glob($sitemapDirectory.DIRECTORY_SEPARATOR.'alternatives*.xml') as $path) {
            File::delete($path);
        }

        $sitemap = Sitemap::create();
        $urlCount = 0;
        $fileCount = 0;
        $urlsInCurrentFile = 0;

        Product::approvedAndPublished()
            ->with(['categories.types', 'techStacks'])
            ->chunkById(self::DATABASE_CHUNK_SIZE, function ($products) use (
                $relatedProducts,
                $chunkSize,
                $sitemapDirectory,
                &$sitemap,
                &$urlCount,
                &$fileCount,
                &$urlsInCurrentFile
            ): void {
                foreach ($products as $product) {
                    $alternatives = $relatedProducts->getAlternatives($product, 15);

                    if ($relatedProducts->shouldNoindexAlternatives($product, $alternatives)) {
                        continue;
                    }

                    $sitemap->add(
                        Url::create(route('pseo.alternatives', $product->slug))
                            ->setPriority(0.8)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    );

                    $urlCount++;
                    $urlsInCurrentFile++;

                    if ($urlsInCurrentFile === $chunkSize) {
                        $fileCount++;
                        $sitemap->writeToFile($sitemapDirectory."/alternatives-{$fileCount}.xml");
                        $sitemap = Sitemap::create();
                        $urlsInCurrentFile = 0;
                    }
                }
            });

        if ($urlsInCurrentFile > 0 || $fileCount === 0) {
            $fileCount++;
            $filename = $fileCount === 1 ? 'alternatives.xml' : "alternatives-{$fileCount}.xml";
            $sitemap->writeToFile($sitemapDirectory.'/'.$filename);
        }

        $indexWriter->write(public_path('sitemap.xml'), $sitemapDirectory);

        $this->info("Generated {$urlCount} alternatives URLs across {$fileCount} sitemap file(s).");

        return self::SUCCESS;
    }
}
