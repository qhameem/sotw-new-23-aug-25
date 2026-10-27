<?php

namespace App\Services\Markdown;

use App\Support\SiteIdentity;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Discovery
{
    public function __construct(private readonly Registry $registry, private readonly SiteIdentity $site) {}

    public function documents(): array
    {
        return app(CacheVersion::class)->remember('discovery', 86400, function () {
            $groups = [];
            $seen = [];
            $budget = (int) config('agent_access.full_budget');
            $full = '# '.$this->site->name()." - full content\n\n> ".$this->site->description()."\n\nGenerated: ".now('UTC')->toDateString()."\n\nDynamic route families include up to ".config('agent_access.collection_limit').' candidates each. Paginated listings include their first page. Find all eligible pages through '.$this->site->url('/api/md/_catalog')." and listing pagination.\n";
            foreach ($this->registry->listMarkdownRoutes((int) config('agent_access.collection_limit')) as $entry) {
                if (isset($seen[$entry['path']]) || str_contains($entry['path'], '/page/')) {
                    continue;
                }
                $seen[$entry['path']] = true;
                try {
                    $page = $entry['document'] ?? $this->registry->render($entry['path']);
                } catch (HttpExceptionInterface $e) {
                    if ($e->getStatusCode() === 404) {
                        continue;
                    }
                    throw $e;
                }
                $section = "\n---\n<!-- source: ".$page['canonical']." -->\n".$page['markdown'];
                if (strlen($full.$section) > $budget - 300) {
                    Log::warning('Agent full-content budget reached', ['limit' => $budget, 'path' => $entry['path']]);
                    $full .= "\nContent limit reached. Additional pages: ".$this->site->url('/api/md/_catalog')."\n";
                    break;
                }
                $full .= $section;
                $groups[$entry['group']][] = '- '.app(Format::class)->link($page['title'], $entry['markdown']).': '.app(Format::class)->text($page['summary']);
            }
            $index = '# '.$this->site->name()."\n\n> ".$this->site->description()."\n\nBrowsing is free. Makers can submit products using badge or paid launch options. Contact: ".config('agent_access.contact').".\n\n<!-- keep in sync with robots.txt and ai.txt -->\n\nDynamic route families are limited to ".config('agent_access.collection_limit')." candidates each; use the catalog and listing pagination for the rest.\n";
            foreach (['Core pages', 'Docs / Guides', 'Articles', 'Software', 'Optional'] as $group) {
                if (isset($groups[$group])) {
                    $index .= "\n## {$group}\n".implode("\n", $groups[$group])."\n";
                }
            }
            $index .= "\n## Machine-readable\n";
            foreach (['Full content' => '/llms-full.txt', 'Markdown catalog' => '/api/md/_catalog', 'Sitemap' => '/sitemap.xml', 'AI usage policy' => '/ai.txt'] as $label => $path) {
                $index .= '- '.app(Format::class)->link($label, $path)."\n";
            }

            return ['llms.txt' => $index, 'llms-full.txt' => $full];
        });
    }

    public function catalog(int $offset, int $limit): array
    {
        $items = [];
        $position = 0;
        $scanned = 0;
        foreach ($this->registry->candidateRoutes() as $entry) {
            if ($position++ < $offset) {
                continue;
            }
            try {
                $page = $entry['document'] ?? $this->registry->render($entry['path']);
                $items[] = [
                    'title' => $page['title'], 'description' => $page['summary'],
                    'canonical' => $page['canonical'], 'markdown' => $entry['markdown'],
                ];
            } catch (HttpExceptionInterface $e) {
                if ($e->getStatusCode() !== 404) {
                    throw $e;
                }
            }
            if (count($items) >= $limit || ++$scanned >= 200) {
                return ['items' => $items, 'next' => $this->site->url('/api/md/_catalog?offset='.$position.'&limit='.$limit)];
            }
        }

        return ['items' => $items, 'next' => null];
    }

    public function policy(): string
    {
        return "# ai.txt - v1.1.1\n# keep in sync with robots.txt and llms.txt\n"
            ."\n[identity]\nname: ".$this->site->name()."\nurl: ".rtrim($this->site->url(), '/')."\ndescription: ".$this->site->description()."\ncontact: ".config('agent_access.contact')
            ."\n\n[permissions]\nallowed: summarise, quote with attribution, cite, index for search and answers"
            ."\n\n[restrictions]\nno fabricated quotes\nno fake reviews or ratings\nno presenting our content as the model's own\nno training on private user-generated data"
            ."\n\n[attribution]\ncite: ".$this->site->name().' + canonical HTML URL'
            ."\n\n[scope]\nincluded: public, published, indexable pages listed in ".$this->site->url('/api/md/_catalog')
            ."\nexcluded: ".implode(', ', $this->privatePaths())
            ."\n\n[contact]\nemail: ".config('agent_access.contact')."\n\nLast updated: ".config('agent_access.updated')."\n";
    }

    public function privatePaths(): array
    {
        return array_values(array_unique(config('agent_access.private_paths')));
    }

    public function robots(): string
    {
        $text = "# keep in sync with llms.txt and ai.txt\n# Agent discovery: ".$this->site->url('/llms.txt')."\n# AI usage policy: ".$this->site->url('/ai.txt')."\n\n";
        foreach (config('agent_access.blocked_bots') as $bot) {
            $text .= "User-agent: {$bot}\nDisallow: /\n\n";
        }
        foreach ([...config('agent_access.search_bots'), '*'] as $bot) {
            $text .= "User-agent: {$bot}\n";
            foreach ($this->privatePaths() as $path) {
                $text .= "Disallow: {$path}\n";
            }
            // Discovery API is public; ordinary API paths remain excluded.
            $text .= "Allow: /api/md/\n\n";
        }

        return $text.'Sitemap: '.$this->site->url('/sitemap.xml')."\n";
    }
}
