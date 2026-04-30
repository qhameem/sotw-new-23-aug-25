<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\DescriptionRewriterService;
use App\Services\ProductEditorialContentService;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BackfillProductEditorialContent extends Command
{
    protected $signature = 'products:backfill-editorial-content
        {product? : Product slug or numeric ID}
        {--limit=50 : Maximum number of products to process when no product is specified}
        {--dry-run : Show which products would be updated without saving}
        {--force : Rewrite even if the description already has structured editorial signals}';

    protected $description = 'Backfill older product descriptions into the structured humanized format used for editorial SEO signals.';

    public function __construct(
        private readonly DescriptionRewriterService $descriptionRewriterService,
        private readonly ProductEditorialContentService $productEditorialContentService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $products = $this->resolveProducts();

        if ($products->isEmpty()) {
            $this->warn('No matching products were found.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($products as $product) {
            $processed++;
            $hasStructuredSignals = $this->productEditorialContentService->hasStructuredEditorialSignals($product->description);

            if ($hasStructuredSignals && !$force) {
                $skipped++;
                $this->line("SKIP {$product->slug}: already has structured editorial signals.");
                continue;
            }

            $rawDescription = $this->buildRawDescription($product);

            if ($rawDescription === '') {
                $failed++;
                $this->warn("FAIL {$product->slug}: no usable source description found.");
                continue;
            }

            $pageTextContext = $this->buildPageTextContext($product);
            $rewritten = $this->descriptionRewriterService->rewrite(
                $product->name,
                $rawDescription,
                $pageTextContext
            );

            if (!is_string($rewritten) || trim($rewritten) === '') {
                $failed++;
                $this->warn("FAIL {$product->slug}: rewrite service returned no content.");
                continue;
            }

            if ($dryRun) {
                $updated++;
                $preview = Str::limit(trim(strip_tags($rewritten)), 140);
                $this->info("DRY-RUN {$product->slug}: would update description.");
                $this->line("Preview: {$preview}");
                continue;
            }

            $product->description = $rewritten;
            $product->save();

            $updated++;
            $this->info("UPDATED {$product->slug}: structured editorial description saved.");
        }

        $this->newLine();
        $this->line("Processed: {$processed}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveProducts()
    {
        $query = Product::query()
            ->with(['categories.types', 'techStacks'])
            ->where('approved', true)
            ->where('is_published', true)
            ->orderBy('id');

        $identifier = $this->argument('product');

        if (is_string($identifier) && $identifier !== '') {
            if (ctype_digit($identifier)) {
                $query->where('id', (int) $identifier);
            } else {
                $query->where('slug', $identifier);
            }

            return $query->get();
        }

        return $query->limit(max(1, (int) $this->option('limit')))->get();
    }

    private function buildRawDescription(Product $product): string
    {
        $plainDescription = trim(strip_tags((string) $product->description));

        if ($plainDescription !== '') {
            return $plainDescription;
        }

        return trim(implode(' ', array_filter([
            $product->product_page_tagline,
            $product->tagline,
            $product->name,
        ])));
    }

    private function buildPageTextContext(Product $product): string
    {
        $contextParts = array_filter([
            'Product: ' . $product->name,
            $product->tagline ? 'Tagline: ' . $product->tagline : null,
            $product->product_page_tagline ? 'Detailed tagline: ' . $product->product_page_tagline : null,
            $product->categories->isNotEmpty() ? 'Categories: ' . $product->categories->pluck('name')->implode(', ') : null,
            $product->techStacks->isNotEmpty() ? 'Tech stack: ' . $product->techStacks->pluck('name')->implode(', ') : null,
        ]);

        $remoteContext = $this->fetchRemoteContext($product);

        if ($remoteContext !== '') {
            $contextParts[] = $remoteContext;
        }

        return trim(implode("\n", $contextParts));
    }

    private function fetchRemoteContext(Product $product): string
    {
        if (!is_string($product->link) || trim($product->link) === '') {
            return '';
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ])->timeout(15)->get($product->link);

            if (!$response->successful()) {
                return '';
            }

            $html = $response->body();
            $document = new DOMDocument();

            libxml_use_internal_errors(true);
            $loaded = $document->loadHTML($html);
            libxml_clear_errors();

            if (!$loaded) {
                return '';
            }

            $title = trim($document->getElementsByTagName('title')->item(0)?->textContent ?? '');
            $metaDescription = '';

            foreach ($document->getElementsByTagName('meta') as $meta) {
                if (strtolower((string) $meta->getAttribute('name')) === 'description') {
                    $metaDescription = trim((string) $meta->getAttribute('content'));
                    break;
                }
            }

            $cleanDocument = clone $document;
            $xpath = new DOMXPath($cleanDocument);
            $noise = $xpath->query('//nav | //header | //footer | //script | //style | //noscript | //aside');

            foreach ($noise as $node) {
                $node->parentNode?->removeChild($node);
            }

            $parts = array_filter([
                $title !== '' ? 'Title: ' . $title : null,
                $metaDescription !== '' ? 'Meta Description: ' . $metaDescription : null,
            ]);

            foreach (['h1', 'h2', 'h3'] as $tag) {
                foreach ($cleanDocument->getElementsByTagName($tag) as $node) {
                    $text = trim($node->textContent);

                    if ($text !== '') {
                        $parts[] = strtoupper($tag) . ': ' . $text;
                    }
                }
            }

            $bodyText = trim($cleanDocument->getElementsByTagName('body')->item(0)?->textContent ?? '');

            if ($bodyText !== '') {
                $parts[] = 'BODY CONTENT: ' . Str::limit(preg_replace('/\s+/u', ' ', $bodyText), 5000, '');
            }

            return implode("\n", $parts);
        } catch (\Throwable) {
            return '';
        }
    }
}
