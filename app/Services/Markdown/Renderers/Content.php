<?php

namespace App\Services\Markdown\Renderers;

use App\Models\Product;
use App\Services\Markdown\Format;
use App\Services\ProductPageSections;
use App\Support\CategoryPageType;
use App\Support\SiteIdentity;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Str;

class Content
{
    public function __construct(private readonly Format $format) {}

    public function render(string $kind, array $data, string $canonical, string $label): array
    {
        $f = $this->format;
        $updated = null;
        $summary = $data['metaDescription'] ?? $data['meta_description'] ?? '';
        $title = $data['pageTitle'] ?? $data['title'] ?? $label;
        $body = '';
        $related = ['Home' => app(SiteIdentity::class)->url(), 'Topics' => app(SiteIdentity::class)->url('/topics')];

        if ($kind === 'product') {
            $p = $data['product'];
            abort_unless($this->visible($p), 404);
            $title = $p->name;
            $summary = $summary ?: ($p->product_page_tagline ?: $p->tagline);
            $updated = $p->updated_at;
            if ($p->link) {
                $related['Official website'] = $p->link;
            }
            $sections = app(ProductPageSections::class)->build($p, $data['productEditorial'], $data['descriptionContent']);
            $body = '';
            if ($p->usesProductFacts()) {
                $body .= "\n## About\n\n";
                foreach ($p->product_facts ?? [] as $fact) {
                    if (is_string($fact) && trim($fact) !== '') {
                        $body .= '- '.$f->escape($fact)."\n";
                    }
                }
            } else {
                $body .= "\n## About\n\n";
                foreach ($sections['about_html'] as $html) {
                    $body .= $f->html($html, $canonical)."\n\n";
                }
                if (filled($p->summary) && ! str_contains(strip_tags(implode(' ', $sections['about_html'])), $p->summary)) {
                    $body .= $f->escape($p->summary)."\n\n";
                }
            }
            foreach ($p->media as $index => $media) {
                $body .= '- '.$f->link($media->alt_text ?: $p->name.' screenshot '.($index + 1), $media->medium_url ?: $media->url)."\n";
            }
            if ($p->video_url && $p->getEmbedUrl()) {
                $body .= $f->link($p->name.' video', $p->getEmbedUrl())."\n";
            }
            $overview = $sections['overview'];
            if (! collect($overview)->contains('key', 'audience') && $data['bestForCategories']->isNotEmpty()) {
                array_unshift($overview, ['title' => 'Who is it for?', 'text' => $data['bestForCategories']->pluck('name')->implode(', ')]);
            }
            foreach ($overview as $row) {
                $body .= '### '.$f->escape($row['title'])."\n\n".$f->escape($row['text'])."\n\n";
            }
            $sections['use_cases'] = array_values(array_unique(array_merge($data['useCaseCategories']->pluck('name')->all(), $sections['use_cases'])));
            foreach (['features' => 'Features', 'use_cases' => 'Use cases'] as $key => $heading) {
                if ($sections[$key]) {
                    $body .= "\n## {$heading}\n\n".implode("\n", array_map(fn ($item) => '- '.$f->escape($item), $sections[$key]))."\n";
                }
            }
            if ($p->user && ! $p->user->hasRole('admin')) {
                $body .= "\n## Submitted by\n\n".$f->escape($p->user->name)."\n";
            }
            $rows = app(\App\Services\ProductDetailData::class)->rows($p, $data['bestForCategories'], $data['platformCategories'], $data['useCaseCategories']);
            $body .= "\n## Details\n\n".$f->table(collect($rows)->map(fn ($items) => $items->pluck('label')->implode(', '))->all());
            if ($rows['Pricing']->isNotEmpty() && $p->pricing_page_url) {
                $body .= $f->link('View pricing', $p->pricing_page_url)."\n";
            }
            if ($sections['details_html']) {
                $body .= "\n## More details\n\n".$f->html($sections['details_html'], $canonical)."\n";
            }
            if ($p->not_for_text) {
                $body .= "\n## Limitations\n\n".$f->escape($p->not_for_text)."\n";
            }
            $links = is_array($p->maker_links) ? $p->maker_links : (json_decode($p->maker_links ?? '[]', true) ?: []);
            if ($x = Product::xProfileUrl($p->x_account)) {
                $links[] = $x;
            }
            foreach (array_unique(array_filter($links, 'is_string')) as $url) {
                $body .= '- '.$f->link(parse_url($url, PHP_URL_HOST) ?: 'Resource', $url)."\n";
            }
            $faqs = collect($p->faq ?: ($data['productEditorial']['faq'] ?? []))->filter(fn ($item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null))->unique('question')->take(6);
            foreach ($faqs as $item) {
                $body .= "\n### ".$f->escape($item['question'])."\n\n".$f->escape(Str::words($f->text($item['answer']), 100, '…'))."\n";
            }
            foreach ($data['alternativeProducts'] as $alternative) {
                if ($this->visible($alternative)) {
                    $related[$alternative->name] = '/product/'.$alternative->slug;
                }
            }
        } elseif ($kind === 'article') {
            $post = $data['post'];
            abort_unless($post->status === 'published' && $post->published_at && $post->published_at <= now(), 404);
            $title = $post->title;
            $summary = $post->meta_description ?: $post->excerpt;
            $updated = $post->updated_at;
            $body = $f->escape($summary)."\n\n".$f->html($post->content, $canonical);
            foreach ($data['relatedArticles'] ?? [] as $post) {
                $related[$post->title] = '/articles/'.$post->slug;
            }
        } elseif ($kind === 'articles') {
            $title = isset($data['category']) ? $data['category']->name : (isset($data['tag']) ? $data['tag']->name : 'Articles');
            $summary = $summary ?: 'Published articles on '.app(SiteIdentity::class)->name().'.';
            if (! empty($data['leadArticle'])) {
                $body .= $this->articles([$data['leadArticle']]);
            }
            $body .= $this->articles($data['posts']);
            $body .= $this->pagination($data['posts']);
        } elseif ($kind === 'topics') {
            $title = 'Topics';
            $summary = 'Browse software topics, use cases, audiences, and platforms.';
            foreach ($data['categoryNavigationGroups'] as $group) {
                $body .= "\n## ".$f->escape($group['label'] ?? $group['name'] ?? 'Topics')."\n\n";
                foreach ($group['items'] ?? $group['categories'] ?? [] as $item) {
                    $body .= '- '.$f->link($item['name'] ?? $item['label'], $item['url'])."\n";
                }
            }
        } elseif ($kind === 'collection') {
            $collection = $data['collection'];
            abort_unless($collection->isPublic(), 404);
            $title = $collection->name;
            $summary = 'Public software collection by '.$collection->user->name.'.';
            $updated = $collection->updated_at;
            foreach ($collection->items as $item) {
                if ($item->product && $this->visible($item->product)) {
                    $body .= $this->products([$item->product]);
                    if ($item->comment) {
                        $body .= '  '.$f->escape($item->comment)."\n";
                    }
                }
            }
        } elseif ($kind === 'changelog') {
            $title = 'Changelog';
            $summary = 'Product updates and releases.';
            foreach ($data['changelogs'] as $date => $logs) {
                $body .= "\n## ".$f->escape($date)."\n\n";
                foreach ($logs as $log) {
                    $body .= '### '.$f->escape($log->title)."\n\n".$f->html($log->description ?? $log->content ?? '', $canonical)."\n";
                    $updated ??= $log->updated_at;
                }
            }
        } elseif ($kind === 'directory') {
            abort_if(($data['search'] ?? '') !== '' || $data['entries']->isEmpty(), 404);
            $title = 'Software alternatives';
            $summary = 'Browse software alternatives and comparisons.';
            foreach ($data['entries'] as $entry) {
                $p = $entry['product'];
                if ($this->visible($p)) {
                    $body .= '- '.$f->link($p->name, '/product/'.$p->slug).' - '.$f->escape(Str::limit(strip_tags($p->tagline), 140))."\n";
                    foreach ($entry['links'] as $link) {
                        $body .= '  - '.$f->link($link['label'], $link['url'])."\n";
                    }
                }
            }
            $body .= $this->pagination($data['products']);
        } elseif ($kind === 'history') {
            $title = 'Public launch readiness history';
            $summary = 'Browse publicly saved launch readiness scans.';
            foreach ($data['history'] as $scan) {
                $body .= '- '.$f->link($scan->final_host ?: $scan->submitted_url, '/tools/'.app(\App\Support\ToolSettings::class)->slug('launch_readiness').'/results/'.$scan->result_token).' - Launch score: '.$f->escape($scan->launch_score)."\n".$f->table(['Scanned' => $scan->scanned_at?->toDateTimeString(), 'Passed' => $scan->passed_checks, 'Warnings' => $scan->warning_checks, 'Errors' => $scan->failed_checks]);
            }
            $body .= $this->pagination($data['history']);
        } elseif ($kind === 'guide') {
            $guide = $data['guide'];
            $title = $guide['article_title'];
            $summary = $guide['meta_description'];
            $body = $this->absoluteMarkdown($guide['markdown'], $canonical);
        } else {
            abort_if(! empty($data['isFuture']) || ! empty($data['shouldNoindexArchive']) || ! empty($data['shouldNoindexTaxonomy']) || ! empty($data['shouldNoindex']), 404);
            $category = $data['category'] ?? null;
            if ($category && $kind === 'listing' && ! isset($data['products'])) {
                $title = CategoryPageType::heading($category->pageType(), $category->name);
                $summary = $category->meta_description ?: CategoryPageType::intro($category->pageType(), $category);
            }
            if (isset($data['broadCategory'])) {
                $title = $data['broadCategory']['label'].' Software & Tools';
                $summary = $data['broadCategory']['description'] ?? $summary;
            }
            $body = $f->escape($data['intro'] ?? $data['pairMatchSummary'] ?? $data['pageIntro'] ?? '')."\n\n";
            if (parse_url($canonical, PHP_URL_PATH) === '/') {
                $hero = \App\Support\HomeHero::headline();
                $body .= '## '.$f->escape($hero['headline'].' '.$hero['highlight'])."\n\n".$f->escape(\App\Support\HomeHero::description())."\n\n";
                foreach (\App\Support\HomeHero::features() as $feature) {
                    $body .= '- '.$f->escape($feature)."\n";
                }
            }
            foreach (['promotedProducts', 'regularProducts', 'products', 'alternatives'] as $key) {
                if (isset($data[$key]) && is_iterable($data[$key])) {
                    $body .= $this->products($data[$key]);
                    if ($key === 'regularProducts' && isset($data['categoryPagination'])) {
                        $pagination = $data['categoryPagination'];
                        $body .= "\nPage ".$pagination['current_page']."\n";
                        foreach (['Previous' => $pagination['previous_url'], 'Next' => $pagination['next_url']] as $label => $url) {
                            if ($url) {
                                $body .= '- '.$f->link($label, $url)."\n";
                            }
                        }
                    } elseif ($key === 'regularProducts' && isset($data['broadCategory'])) {
                        $base = '/software/'.$data['broadCategory']['key'];
                        $body .= "\nPage ".$data['currentPage']."\n";
                        if ($data['currentPage'] > 1) {
                            $body .= '- '.$f->link('Previous', $data['currentPage'] === 2 ? $base : $base.'/page/'.($data['currentPage'] - 1))."\n";
                        }
                        if ($data['currentPage'] < $data['lastPage']) {
                            $body .= '- '.$f->link('Next', $base.'/page/'.($data['currentPage'] + 1))."\n";
                        }
                    } else {
                        $body .= $this->pagination($data[$key]);
                    }
                }
            }
            if (isset($data['alternatives'])) {
                $body .= "\n## Editorial snapshot\n\n".$f->escape($data['productEditorial']['limitations'][0] ?? 'Most people look for alternatives when they need a better pricing fit, a better workflow fit, or stronger support for their specific use case.')."\n\n";
                foreach (array_slice($data['productEditorial']['ideal_for'] ?? [], 0, 3) as $item) {
                    $body .= '- '.$f->escape($item)."\n";
                }
                $body .= "\n## Full ranked list\n";
                foreach ($data['alternatives'] as $alternative) {
                    if (! $this->visible($alternative)) {
                        continue;
                    }
                    $body .= "\n### ".$f->link($alternative->name, '/product/'.$alternative->slug)."\n\n";
                    $body .= $f->table([
                        'Category' => $alternative->primary_category_label,
                        'Pricing' => $alternative->pricing_label,
                        'Summary' => $alternative->decision_summary ?: $alternative->match_summary,
                        'Better fit if' => $alternative->better_for_text ?: 'You want a close category match with a different product approach.',
                        'Watch out if' => $alternative->watch_out_text ?: 'You need a near-identical replacement with no workflow changes.',
                        'Why it matches' => $alternative->match_summary,
                        'Best for' => $alternative->best_for_label,
                        'Pros' => implode(' · ', $alternative->pros_points ?? []),
                        'Tradeoffs' => implode(' · ', $alternative->limitations_points ?? []),
                    ]);
                }
                $body .= "\n## How we ranked these alternatives\n\nWe prioritize alternatives that overlap with ".$f->escape($data['product']->name)." in specific software category, use case, audience, pricing model, and product positioning.\n";
            }
            foreach (['productA', 'productB'] as $key) {
                if (isset($data[$key])) {
                    abort_unless($this->visible($data[$key]), 404);
                    $p = $data[$key];
                    $body .= "\n## ".$f->escape($p->name)."\n\n".$f->html($p->tagline, $canonical)."\n".$f->table([
                        'Pricing' => $p->categories->first(fn ($c) => $c->types->contains('name', 'Pricing'))?->name,
                        'Community votes' => number_format($p->votes_count),
                        'Built with' => $p->techStacks->take(3)->pluck('name')->implode(', '),
                        'Category' => $p->categories->filter(fn ($c) => $c->types->pluck('name')->intersect(['Software', 'Software Categories'])->isNotEmpty())->take(2)->pluck('name')->implode(', '),
                    ]);
                }
            }
            foreach ($data['faqItems'] ?? [] as $item) {
                $body .= "\n### ".$f->escape($item['question'])."\n\n".$f->escape($item['answer'])."\n";
            }
        }

        return $f->page($f->text($title), $f->text($summary ?: $label), $canonical, $body, $updated, $related);
    }

    public function visible(Product $product): bool
    {
        return $product->approved && $product->is_published && ! $product->generation_noindex && $product->generation_status !== 'draft'
            && (! $product->published_at || $product->published_at <= now());
    }

    private function products(iterable $products): string
    {
        $text = '';
        foreach ($products as $product) {
            if ($product instanceof Product && $this->visible($product)) {
                $text .= '- '.$this->format->link($product->name, '/product/'.$product->slug).' - '.$this->format->escape($product->product_page_tagline ?: $product->tagline)."\n";
            }
        }

        return $text;
    }

    private function articles(iterable $posts): string
    {
        $text = '';
        foreach ($posts as $post) {
            $text .= '- '.$this->format->link($post->title, '/articles/'.$post->slug).' - '.$this->format->escape($post->meta_description ?: Str::limit($this->format->text($post->content), 180))."\n";
        }

        return $text;
    }

    private function pagination(mixed $items): string
    {
        if (! $items instanceof Paginator) {
            return '';
        }
        $text = "\nPage ".$items->currentPage()."\n";
        foreach (['Previous' => $items->previousPageUrl(), 'Next' => $items->nextPageUrl()] as $label => $url) {
            if ($url) {
                $text .= '- '.$this->format->link($label, $url)."\n";
            }
        }

        return $text;
    }

    private function absoluteMarkdown(string $markdown, string $canonical): string
    {
        return preg_replace_callback('/(!?\[[^\]]*\])\(([^\s)]+)\)/', function ($match) use ($canonical) {
            $url = app(\App\Services\ProductPageMarkdown::class)->resolveUrl($canonical, $match[2]);

            return $url ? $match[1].'('.$url.')' : $match[1];
        }, $markdown) ?? $markdown;
    }
}
