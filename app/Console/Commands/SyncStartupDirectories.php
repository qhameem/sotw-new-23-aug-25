<?php

namespace App\Console\Commands;

use App\Models\StartupDirectory;
use App\Services\StartupDirectoryArchiveCategories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SyncStartupDirectories extends Command
{
    protected $signature = 'directories:sync {--dry-run : Report additions without writing to the database}';

    protected $description = 'Import missing startup directories from the CSV archive';

    public function handle(): int
    {
        $path = base_path('launchpanda-best-startup-directories.csv');
        $file = fopen($path, 'r');

        if ($file === false) {
            $this->error("Unable to open directory archive: {$path}");

            return self::FAILURE;
        }

        try {
            if (fgetcsv($file, escape: '') !== ['Directory name', 'URL', 'DR', 'Link type', 'Price']) {
                throw new RuntimeException('The directory archive has an unexpected header.');
            }

            $knownDomains = StartupDirectory::query()
                ->pluck('website_url')
                ->map(fn (string $url) => $this->domain($url))
                ->filter()
                ->flip()
                ->all();

            $timestamp = now();
            $rows = [];
            $duplicates = 0;

            while (($csvRow = fgetcsv($file, escape: '')) !== false) {
                if (count($csvRow) !== 5) {
                    throw new RuntimeException('A directory archive row has an unexpected number of fields.');
                }

                [$name, $url, $dr, $linkType, $price] = $csvRow;
                $domain = $this->domain($url);

                if ($name === '' || $domain === '' || !filter_var($url, FILTER_VALIDATE_URL)
                    || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    throw new RuntimeException("Invalid directory archive row: {$name}");
                }

                if (isset($knownDomains[$domain])) {
                    $duplicates++;
                    continue;
                }

                $knownDomains[$domain] = true;
                $rows[] = [
                    'name' => $name,
                    'website_url' => $url,
                    'category' => $this->category($name),
                    'pricing' => $this->pricing($price),
                    'price_details' => $price !== '' ? $price : null,
                    'link_type' => match (strtolower(trim($linkType))) {
                        'dofollow' => 'dofollow',
                        'nofollow' => 'nofollow',
                        'conditional', 'dofollow with badge' => 'conditional',
                        default => null,
                    },
                    'dr' => $dr !== '' && is_numeric($dr) ? (int) $dr : null,
                    'source_url' => isset($this->types[$name])
                        ? 'https://submitsaas.com/'
                        : 'https://ideaproof.io/launch-directories',
                    'priority' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            fclose($file);
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: '.count($rows).' directories to add; '.$duplicates.' already present.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 100) as $batch) {
                DB::table('startup_directories')->insert($batch);
            }
        });

        $this->info('Added '.count($rows).' directories; skipped '.$duplicates.' existing domains.');

        return self::SUCCESS;
    }

    private function domain(string $url): string
    {
        return Str::replaceStart('www.', '', strtolower(parse_url($url, PHP_URL_HOST) ?? ''));
    }

    private function category(string $name): ?string
    {
        $type = $this->types[$name] ?? null;

        return StartupDirectoryArchiveCategories::BY_NAME[$name] ?? match ($type) {
            'AI Directory' => 'ai',
            'Launchpad', 'Startup Directory' => 'launch',
            'Software Directory' => 'discovery',
            'General Directory' => 'general',
            'Community' => 'community',
            default => null,
        };
    }

    private function pricing(string $price): ?string
    {
        $price = strtolower(trim($price));

        return match (true) {
            $price === 'free' => 'free',
            str_contains($price, 'conditional'), Str::startsWith($price, ['freemium', 'free + paid']) => 'freemium',
            str_starts_with($price, 'paid') => 'paid',
            default => null,
        };
    }

    private array $types = [
        'SmolLaunch' => 'Launchpad',
        'Good AI Tools' => 'Software Directory',
        'Product Watch' => 'Launchpad',
        'SaaS Browser' => 'Software Directory',
        'ShowMySites' => 'Software Directory',
        'NextGen Tools' => 'Launchpad',
        'Acid Tools' => 'AI Directory',
        'Sitelike.org' => 'Software Directory',
        'Addon Biz' => 'General Directory',
        'Unite List' => 'Software Directory',
        'ShinyLaunch' => 'Launchpad',
        'Product Wing' => 'Software Directory',
        'AI X Collection' => 'AI Directory',
        'Launch' => 'Launchpad',
        'AppaList' => 'AI Directory',
        'AI Tech Viral' => 'AI Directory',
        'VibeCodingList' => 'Software Directory',
        'My Launch Stash' => 'Software Directory',
        'MakeItLast' => 'Software Directory',
        'Linkorado' => 'General Directory',
        'Million Dot Homepage' => 'Software Directory',
        'LaunchClash' => 'Launchpad',
        'RankInPublic' => 'Software Directory',
        'Best Tool Vault' => 'Software Directory',
        'Trustiner' => 'Software Directory',
        'That App Show' => 'Software Directory',
        'Power Up Tools' => 'AI Directory',
        'ProofStories' => 'AI Directory',
        'SaaS Wheel' => 'Software Directory',
        'Appsy Tools' => 'Software Directory',
        'AI Toolz' => 'AI Directory',
        'Scout Forge' => 'Software Directory',
        'Hunt for Tools' => 'Software Directory',
        'Launch Scroll' => 'Software Directory',
        'AIGC 160' => 'AI Directory',
        'Startup Vessel' => 'Software Directory',
        'Solver Tools' => 'Software Directory',
        'Ramen.Tools' => 'Others',
        'Software Bolt' => 'Software Directory',
        'Beam Tools' => 'Software Directory',
        'Toshi List' => 'Software Directory',
        'Tool Finddir' => 'AI Directory',
        'Saas Field' => 'AI Directory',
        'Stack Directory' => 'Software Directory',
        'Product List Dir' => 'Software Directory',
        'My Start Tools' => 'Software Directory',
        'RankCert' => 'Launchpad',
        'aat.ee' => 'Launchpad',
        'SaaS Roots' => 'Software Directory',
        'Ash List' => 'Software Directory',
        'DodoLaunch' => 'Launchpad',
        'LaunchFree.io' => 'Launchpad',
        'App Hub List' => 'Software Directory',
        'Top Trend Tools' => 'Software Directory',
        'Tools Cosmos' => 'Software Directory',
        'WeekHack' => 'Launchpad',
        'Saaspa.ge' => 'Launchpad',
        'Tool Signal' => 'Software Directory',
        'Tool Journey' => 'Software Directory',
        'SaaS Tools Dir' => 'Software Directory',
        'Super AI Boom' => 'AI Directory',
        'Tools List HQ' => 'Software Directory',
        'Source Dir' => 'Software Directory',
        'Tool Prism' => 'AI Directory',
        'The Key Tools' => 'AI Directory',
        'AppLauncher' => 'Launchpad',
        'FreeListingUK' => 'General Directory',
        'Tiny Tool Hub' => 'Software Directory',
        'Smart Kit Hub' => 'Software Directory',
        'Latest AI Updates' => 'AI Directory',
        'The App Tools' => 'AI Directory',
        'Forward Future' => 'AI Directory',
        'The Mega Tools' => 'Software Directory',
        'The Core Tools' => 'Software Directory',
        'builtbyindies' => 'Launchpad',
        'AIToolsCapital' => 'AI Directory',
        'SaaS Hub Directory' => 'Software Directory',
        'Tools Under Radar' => 'Software Directory',
        'App Networker' => 'Software Directory',
        'ShipNLaunch' => 'Launchpad',
        'SaaS Avenue' => 'Software Directory',
        'Rank my AI' => 'AI Directory',
        'ShipYardHQ' => 'General Directory',
        'SaaS Networker' => 'Software Directory',
        'Visalytica' => 'Software Directory',
        'SaaS Badge' => 'Software Directory',
        'Toolkit Index' => 'Software Directory',
        'ProductsFarm' => 'Launchpad',
        'Stellar Launch' => 'Launchpad',
        'SaaS Branch' => 'Software Directory',
        'SaaS Crawler' => 'Software Directory',
        'DR Checker' => 'Others',
        'ProductArena' => 'Software Directory',
        'SaaS State' => 'Software Directory',
        'SaaS Territory' => 'Software Directory',
        'Tool Dynamo' => 'Software Directory',
        'Tool Ignite' => 'Software Directory',
        'Launchpad' => 'Launchpad',
        'LeanVibe' => 'Software Directory',
        'SaaS Scanner' => 'Software Directory',
        'Tool Networker' => 'Software Directory',
        'Tool Parade' => 'Software Directory',
        'AI Tools Lister' => 'AI Directory',
        'Stackboo' => 'Launchpad',
        'IndieNeed' => 'Launchpad',
        'EverFeatured' => 'Software Directory',
        'Hypertools' => 'AI Directory',
        'ToolTrove' => 'AI Directory',
        'Endors' => 'Software Directory',
        'Zearches.' => 'General Directory',
        'AIArt.Tools' => 'AI Directory',
        'AIInLink' => 'Software Directory',
        'Hunt Startup' => 'Startup Directory',
        'ProductCool' => 'Software Directory',
        'Online AI Tools Directory' => 'AI Directory',
        'AI ToolBoard' => 'AI Directory',
        'ShipDesk.dev' => 'Launchpad',
        'Arcapush' => 'Software Directory',
        'App Launcher' => 'Launchpad',
        'Ignlab Launch' => 'Software Directory',
        'aifeed.dev' => 'AI Directory',
        'JunkStartups' => 'Startup Directory',
        'GoodToolOnline' => 'Launchpad',
        'Rank Anything' => 'General Directory',
        'ChipJourney' => 'Software Directory',
        'aier.im' => 'AI Directory',
        'PromoteIndex' => 'Software Directory',
        'AiComing' => 'AI Directory',
        'The Next AI' => 'AI Directory',
        'Product Launch' => 'Launchpad',
        'MeWebLink' => 'Software Directory',
        'etooly.eu' => 'AI Directory',
        'Hatchr' => 'Software Directory',
        'AI SaaS' => 'Launchpad',
        'OpenUpvote' => 'Software Directory',
        'AI Finder Guru' => 'AI Directory',
        'Super AI Tools' => 'AI Directory',
        'Launch Daddy' => 'Launchpad',
        'BuildTrail' => 'Others',
        'Oksaas' => 'Software Directory',
        'PRKatalog' => 'General Directory',
        'Build a Streak' => 'Software Directory',
        'LaunchSquare' => 'Launchpad',
    ];
}
