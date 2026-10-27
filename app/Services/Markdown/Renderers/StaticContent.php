<?php

namespace App\Services\Markdown\Renderers;

use App\Services\Markdown\Format;
use App\Support\SiteIdentity;
use App\Support\SubmissionPolicy;

class StaticContent
{
    public function render(string $key, string $title, string $summary, string $canonical): array
    {
        $f = app(Format::class);
        $viewData = [];
        if ($key === 'get-the-badge') {
            $badge = \App\Models\Badge::first();
            $embed = app(\App\Services\BadgeService::class)->getEmbedData();
            $viewData = [
                'badgeTitle' => $badge?->title ?: 'Software on the Web badge',
                'badgeAltText' => trim($badge?->alt_text ?: 'Featured on Software on the Web'),
                'badgeImageUrl' => $embed['badge_image_url'], 'destinationUrl' => $embed['destination_url'],
                'defaultSnippet' => $embed['snippet'],
                'smallSnippet' => str_replace('width="200"', 'width="160"', $embed['snippet']),
                'largeSnippet' => str_replace('width="200"', 'width="260"', $embed['snippet']),
            ];
        }
        $body = is_file(resource_path('markdown/'.$key.'.blade.php'))
            ? view()->file(resource_path('markdown/'.$key.'.blade.php'), $viewData)->render() : '';
        if (in_array($key, ['faq', 'add-product'], true)) {
            foreach (SubmissionPolicy::sections() as $heading => $description) {
                $body .= "\n## ".$f->escape($heading)."\n\n".$f->escape($description)."\n";
            }
        }
        if ($key === 'newsletter') {
            $body = "Get curated product discoveries and practical software insights. No spam.\n\nSubscribe with your email address and optional first name. Consent is required. You can unsubscribe at any time.\n";
        }
        if ($key === 'launch') {
            $branding = app(\App\Support\LaunchReadinessBranding::class);
            $title = $branding->homepageH1();
            $summary = $branding->homepageMetaDescription();
            $body = "Run a lightweight homepage audit for technical, SEO, trust, and AI visibility signals before launch day.\n\nEnter a public homepage URL to run a check.\n";
            foreach (app(\App\Services\LaunchReadinessAuditService::class)->pendingReport()['categories'] as $category) {
                $body .= "\n## ".$f->escape($category['label'])."\n\n";
                foreach ($category['checks'] as $check) {
                    $body .= '### '.$f->escape($check['label'])."\n\n".$f->escape($check['description'])."\n\n";
                }
            }
        }

        $updated = null;
        if (preg_match('/(?:Last updated:|Effective and last updated:)\s*([^\n.]+)/i', $body, $match)) {
            $updated = \Illuminate\Support\Carbon::parse(trim($match[1]));
        }

        return $f->page($title, $summary, $canonical, $body, $updated, ['Home' => app(SiteIdentity::class)->url(), 'Terms' => app(SiteIdentity::class)->url('/legal')]);
    }
}
