<?php

namespace App\Console\Commands;

use App\Models\StartupDirectory;
use App\Services\AiProviderRoutingService;
use App\Services\StartupDirectoryCategorySuggester;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;

class ClassifyStartupDirectories extends Command
{
    protected $signature = 'directories:classify
        {report : Path to the JSON review report}
        {--apply : Apply rows with an approved_category}
        {--ai : Use Firecrawl and configured Cerebras or Cloudflare AI for unmatched listings}
        {--limit=100 : Maximum rows to review in this batch}
        {--after-id=0 : Resume after this directory ID}';

    protected $description = 'Propose categories for uncategorized directories or apply approved proposals';

    public function handle(StartupDirectoryCategorySuggester $suggester, AiProviderRoutingService $router): int
    {
        $path = $this->argument('report');
        if ($this->option('apply')) {
            return $this->applyReport($path);
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2000]]);
        $afterId = filter_var($this->option('after-id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($limit === false || $afterId === false) {
            $this->error('Use a limit of 1–2000 and a nonnegative after-id.');

            return self::FAILURE;
        }

        $provider = null;
        if ($this->option('ai')) {
            $provider = $router->orderedConfiguredProviders(['cerebras', 'cloudflare'])[0] ?? null;
            if ($provider === null || ! config('services.firecrawl.enabled') || ! config('services.firecrawl.api_key')) {
                $this->error('AI suggestions require enabled Firecrawl and a configured Cerebras or Cloudflare AI provider.');

                return self::FAILURE;
            }
        }

        $file = @fopen($path, 'x');
        if ($file === false) {
            $this->error('Cannot create report. Use a new path in a writable directory.');

            return self::FAILURE;
        }

        try {
            $rows = [];
            $directories = StartupDirectory::query()->whereNull('category')->where('id', '>', $afterId)
                ->orderBy('id')->limit($limit)->get(['id', 'name', 'website_url']);

            foreach ($directories as $directory) {
                [$category, $evidence] = $suggester->suggest($directory, $provider);
                $rows[] = [
                    'id' => $directory->id,
                    'name' => $directory->name,
                    'website_url' => $directory->website_url,
                    'suggested_category' => $category,
                    'evidence' => $evidence,
                    'approved_category' => null,
                ];
            }

            $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR)."\n";
            if (fwrite($file, $json) !== strlen($json)) {
                $this->error('Could not write the complete report.');

                return self::FAILURE;
            }
        } finally {
            fclose($file);
        }

        $suggestions = count(array_filter(array_column($rows, 'suggested_category')));
        $this->info(count($rows)." reviewed; {$suggestions} suggestions; last ID ".($directories->last()?->id ?? $afterId).'.');

        return self::SUCCESS;
    }

    private function applyReport(string $path): int
    {
        try {
            $json = is_file($path) ? file_get_contents($path) : false;
            $rows = $json === false ? null : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $rows = null;
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            $this->error('Expected a valid JSON review report.');

            return self::FAILURE;
        }

        $approved = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! array_key_exists('approved_category', $row)) {
                $this->error('Every report row must include approved_category.');

                return self::FAILURE;
            }

            $category = $row['approved_category'];
            if ($category === null) {
                continue;
            }

            if (! is_string($category) || ! isset(StartupDirectory::CATEGORIES[$category])
                || ! is_int($row['id'] ?? null) || $row['id'] < 1 || isset($approved[$row['id']])
                || ! is_string($row['name'] ?? null) || ! is_string($row['website_url'] ?? null)) {
                $this->error('An approved row has an invalid category, identity, or duplicate ID.');

                return self::FAILURE;
            }

            $approved[$row['id']] = $row;
        }

        ksort($approved);
        $applied = DB::transaction(function () use ($approved): array {
            $applied = [];
            foreach ($approved as $id => $row) {
                $directory = StartupDirectory::query()->whereKey($id)->whereNull('category')->lockForUpdate()->first();
                if ($directory === null || $directory->name !== $row['name'] || $directory->website_url !== $row['website_url']) {
                    continue;
                }

                $directory->update(['category' => $row['approved_category']]);
                $applied[] = $id;
            }

            return $applied;
        }, 3);

        foreach ($applied as $id) {
            $this->line("Updated {$id}: {$approved[$id]['approved_category']}");
        }

        $this->info(count($applied).' updated; '.(count($approved) - count($applied)).' skipped because the listing changed or was already categorized.');

        return self::SUCCESS;
    }
}
