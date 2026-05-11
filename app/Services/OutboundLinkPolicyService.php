<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Article;
use App\Models\OutboundLinkOccurrence;
use App\Models\OutboundLinkRule;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DOMDocument;
use DOMElement;

class OutboundLinkPolicyService
{
    private const RULE_CACHE_KEY = 'outbound_link_rules.active.v1';

    private ?Collection $activeRules = null;

    public function relStringForUrl(?string $url, string $sourceType = 'system_view'): ?string
    {
        $tokens = $this->relTokensForUrl($url, $sourceType);

        return empty($tokens) ? null : implode(' ', $tokens);
    }

    public function relTokensForUrl(?string $url, string $sourceType = 'system_view'): array
    {
        if (! $this->isExternalUrl($url)) {
            return [];
        }

        $rule = $this->matchRule($url, $sourceType);

        if ($rule) {
            return $this->orderedUniqueTokens([
                $rule->rel_nofollow ? 'nofollow' : null,
                $rule->rel_ugc ? 'ugc' : null,
                $rule->rel_sponsored ? 'sponsored' : null,
                $rule->rel_noopener ? 'noopener' : null,
                $rule->rel_noreferrer ? 'noreferrer' : null,
            ]);
        }

        return $this->defaultTokensForSource($sourceType);
    }

    public function matchRule(?string $url, string $sourceType = 'system_view'): ?OutboundLinkRule
    {
        if (! $this->isExternalUrl($url)) {
            return null;
        }

        $normalizedUrl = $this->normalizeUrl($url);
        $host = $this->normalizeHost(parse_url($normalizedUrl ?? '', PHP_URL_HOST));
        $path = $this->normalizePath(parse_url($normalizedUrl ?? '', PHP_URL_PATH));

        foreach ($this->activeRules() as $rule) {
            if (! in_array($rule->source_scope, [OutboundLinkRule::SOURCE_SCOPE_ALL, $sourceType], true)) {
                continue;
            }

            if ($rule->match_type === OutboundLinkRule::MATCH_TYPE_EXACT_URL) {
                if ($this->normalizeUrl($rule->pattern) === $normalizedUrl) {
                    return $rule;
                }

                continue;
            }

            if ($rule->match_type === OutboundLinkRule::MATCH_TYPE_DOMAIN) {
                $ruleHost = $this->extractRuleHost($rule->pattern);

                if ($ruleHost !== '' && ($host === $ruleHost || Str::endsWith($host, '.' . $ruleHost))) {
                    return $rule;
                }

                continue;
            }

            if ($rule->match_type === OutboundLinkRule::MATCH_TYPE_DOMAIN_PATH_PREFIX) {
                [$ruleHost, $rulePath] = $this->extractRuleHostAndPath($rule->pattern);

                if ($ruleHost !== '' && ($host === $ruleHost || Str::endsWith($host, '.' . $ruleHost))) {
                    if ($rulePath === '' || Str::startsWith($path, $rulePath)) {
                        return $rule;
                    }
                }
            }
        }

        return null;
    }

    public function sanitizeHtml(?string $html, string $sourceType = 'article'): string
    {
        if (! is_string($html) || trim($html) === '') {
            return (string) $html;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('a') as $link) {
            if (! $link instanceof DOMElement || ! $link->hasAttribute('href')) {
                continue;
            }

            $href = trim((string) $link->getAttribute('href'));

            if (! $this->isExternalUrl($href)) {
                continue;
            }

            $rel = $this->relStringForUrl($href, $sourceType);

            if ($rel) {
                $link->setAttribute('rel', $rel);
            } else {
                $link->removeAttribute('rel');
            }
        }

        $renderedHtml = $dom->saveHTML() ?: $html;

        return preg_replace('/^<\?xml.+?\?>/i', '', $renderedHtml) ?? $renderedHtml;
    }

    public function rescanOccurrences(): void
    {
        if (! Schema::hasTable('outbound_link_occurrences')) {
            return;
        }

        OutboundLinkOccurrence::query()->delete();

        $now = now();

        Article::query()->select(['id', 'title', 'content'])->chunk(100, function ($articles) use ($now) {
            foreach ($articles as $article) {
                foreach ($this->extractHtmlOccurrences($article->content, 'article', $article->id, $article->title, route('admin.articles.posts.edit', $article->id)) as $payload) {
                    $this->persistOccurrence($payload, $now);
                }
            }
        });

        Product::query()->select([
            'id',
            'name',
            'description',
            'link',
            'maker_links',
            'pricing_page_url',
            'x_account',
        ])->chunk(100, function ($products) use ($now) {
            foreach ($products as $product) {
                $adminUrl = route('admin.products.edit', $product);

                foreach ($this->extractHtmlOccurrences($product->description, 'product_description', $product->id, $product->name, $adminUrl) as $payload) {
                    $this->persistOccurrence($payload, $now);
                }

                $this->persistUrlOccurrence($product->link, 'product_link', $product->id, $product->name, $adminUrl, 'Primary product link', null, $now);
                $this->persistUrlOccurrence($product->pricing_page_url, 'pricing_page', $product->id, $product->name, $adminUrl, 'Pricing page', null, $now);
                $this->persistUrlOccurrence(Product::xProfileUrl($product->x_account), 'product_social', $product->id, $product->name, $adminUrl, 'X profile', null, $now);

                foreach ((array) ($product->maker_links ?? []) as $makerLink) {
                    $this->persistUrlOccurrence($makerLink, 'maker_link', $product->id, $product->name, $adminUrl, 'Maker link', null, $now);
                }
            }
        });

        Ad::query()->select(['id', 'internal_name', 'target_url'])->chunk(100, function ($ads) use ($now) {
            foreach ($ads as $ad) {
                $this->persistUrlOccurrence(
                    $ad->target_url,
                    'ad',
                    $ad->id,
                    $ad->internal_name,
                    route('admin.ads.edit', $ad),
                    'Ad target',
                    null,
                    $now
                );
            }
        });

        foreach ($this->footerEmbedCodes() as $index => $code) {
            foreach ($this->extractHtmlOccurrences($code, 'footer_embed', $index + 1, 'Footer embed #' . ($index + 1), route('admin.settings.index')) as $payload) {
                $this->persistOccurrence($payload, $now);
            }
        }
    }

    public function clearRuleCache(): void
    {
        $this->activeRules = null;
        cache()->forget(self::RULE_CACHE_KEY);
    }

    public function isExternalUrl(?string $url): bool
    {
        if (! is_string($url)) {
            return false;
        }

        $url = trim($url);

        if ($url === '' || Str::startsWith($url, ['#', 'mailto:', 'tel:', 'javascript:'])) {
            return false;
        }

        if (Str::startsWith($url, '//')) {
            $url = 'https:' . $url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = $this->normalizeHost(parse_url($url, PHP_URL_HOST));
        $appHost = $this->normalizeHost(parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== '' && $host !== $appHost;
    }

    public function normalizeUrl(?string $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, '//')) {
            $url = 'https:' . $url;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = $this->normalizeHost($parts['host']);
        $path = $this->normalizePath($parts['path'] ?? '');

        $normalized = $scheme . '://' . $host;

        if (isset($parts['port']) && ! in_array([$scheme, $parts['port']], [['http', 80], ['https', 443]], true)) {
            $normalized .= ':' . $parts['port'];
        }

        $normalized .= $path;

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $query = collect($query)
                ->reject(fn ($_, $key) => str_starts_with(strtolower((string) $key), 'utm_'))
                ->all();

            if (! empty($query)) {
                ksort($query);
                $normalized .= '?' . http_build_query($query);
            }
        }

        return $normalized;
    }

    public function activeRules(): Collection
    {
        if ($this->activeRules !== null) {
            return $this->activeRules;
        }

        if (! Schema::hasTable('outbound_link_rules')) {
            return $this->activeRules = collect();
        }

        return $this->activeRules = cache()->remember(self::RULE_CACHE_KEY, now()->addMinutes(5), function () {
            return OutboundLinkRule::query()
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->orderByDesc('id')
                ->get();
        });
    }

    private function defaultTokensForSource(string $sourceType): array
    {
        return match ($sourceType) {
            'product_link', 'maker_link', 'product_description', 'product_social' => ['nofollow', 'ugc', 'noopener', 'noreferrer'],
            'ad' => ['nofollow', 'sponsored', 'noopener', 'noreferrer'],
            default => ['nofollow', 'noopener', 'noreferrer'],
        };
    }

    private function orderedUniqueTokens(array $tokens): array
    {
        $order = ['nofollow', 'ugc', 'sponsored', 'noopener', 'noreferrer'];
        $tokens = array_values(array_filter(array_unique($tokens)));

        usort($tokens, function (string $a, string $b) use ($order) {
            return array_search($a, $order, true) <=> array_search($b, $order, true);
        });

        return $tokens;
    }

    private function normalizeHost(?string $host): string
    {
        $host = strtolower(trim((string) $host));

        return preg_replace('/^www\./', '', $host) ?? '';
    }

    private function normalizePath(?string $path): string
    {
        $path = '/' . ltrim((string) $path, '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function extractRuleHost(string $pattern): string
    {
        if (Str::startsWith($pattern, ['http://', 'https://', '//'])) {
            return $this->normalizeHost(parse_url($pattern, PHP_URL_HOST));
        }

        $pattern = trim($pattern);
        $pattern = explode('/', $pattern, 2)[0];

        return $this->normalizeHost($pattern);
    }

    private function extractRuleHostAndPath(string $pattern): array
    {
        if (Str::startsWith($pattern, ['http://', 'https://', '//'])) {
            return [
                $this->normalizeHost(parse_url($pattern, PHP_URL_HOST)),
                $this->normalizePath(parse_url($pattern, PHP_URL_PATH)),
            ];
        }

        $pattern = trim($pattern);
        $segments = explode('/', $pattern, 2);
        $host = $this->normalizeHost($segments[0] ?? '');
        $path = isset($segments[1]) ? $this->normalizePath($segments[1]) : '';

        return [$host, $path];
    }

    private function extractHtmlOccurrences(?string $html, string $sourceType, int $sourceId, string $sourceTitle, string $adminUrl): array
    {
        if (! is_string($html) || trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $occurrences = [];

        foreach ($dom->getElementsByTagName('a') as $link) {
            if (! $link instanceof DOMElement || ! $link->hasAttribute('href')) {
                continue;
            }

            $href = trim((string) $link->getAttribute('href'));

            if (! $this->isExternalUrl($href)) {
                continue;
            }

            $occurrences[] = $this->buildOccurrencePayload(
                url: $href,
                sourceType: $sourceType,
                sourceId: $sourceId,
                sourceTitle: $sourceTitle,
                adminUrl: $adminUrl,
                anchorText: trim($link->textContent),
                detectedRel: trim((string) $link->getAttribute('rel'))
            );
        }

        return $occurrences;
    }

    private function persistUrlOccurrence(?string $url, string $sourceType, int $sourceId, string $sourceTitle, string $adminUrl, ?string $anchorText, ?string $detectedRel, $now): void
    {
        if (! $this->isExternalUrl($url)) {
            return;
        }

        $this->persistOccurrence(
            $this->buildOccurrencePayload($url, $sourceType, $sourceId, $sourceTitle, $adminUrl, $anchorText, $detectedRel),
            $now
        );
    }

    private function buildOccurrencePayload(?string $url, string $sourceType, int $sourceId, string $sourceTitle, string $adminUrl, ?string $anchorText, ?string $detectedRel): array
    {
        $normalizedUrl = $this->normalizeUrl($url);
        $domain = $this->normalizeHost(parse_url($normalizedUrl ?? '', PHP_URL_HOST));
        $path = $this->normalizePath(parse_url($normalizedUrl ?? '', PHP_URL_PATH));

        return [
            'occurrence_key' => sha1(implode('|', [$sourceType, $sourceId, $normalizedUrl, $anchorText])),
            'normalized_url' => $normalizedUrl,
            'original_url' => $url,
            'domain' => $domain,
            'path' => $path,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_title' => Str::limit($sourceTitle, 255, ''),
            'source_admin_url' => $adminUrl,
            'anchor_text' => $anchorText ? Str::limit($anchorText, 255, '') : null,
            'detected_rel' => $detectedRel ?: null,
        ];
    }

    private function persistOccurrence(array $payload, $now): void
    {
        if (blank($payload['normalized_url'] ?? null)) {
            return;
        }

        OutboundLinkOccurrence::query()->updateOrCreate(
            ['occurrence_key' => $payload['occurrence_key']],
            array_merge($payload, [
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'occurrence_count' => 1,
            ])
        );
    }

    private function footerEmbedCodes(): array
    {
        if (! Storage::disk('local')->exists('settings.json')) {
            return [];
        }

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);

        if (! is_array($settings)) {
            return [];
        }

        $codes = $settings['footer_badge_embed_codes'] ?? [];

        return is_array($codes) ? array_values(array_filter($codes)) : [];
    }
}
