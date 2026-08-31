<?php

namespace Tests\Unit;

use App\Support\HostingAttribution;
use Tests\TestCase;

class HostingAttributionTest extends TestCase
{
    private function signal(string $type, string $provider, string $role = 'infrastructure'): array
    {
        return compact('type', 'provider', 'role') + ['value' => $provider];
    }

    public function test_matching_rdap_and_asn_score_higher_than_either_alone(): void
    {
        $rdap = $this->signal('rdap', 'Contabo');
        $asn = $this->signal('asn', 'Contabo');
        $this->assertSame(45, HostingAttribution::resolve('example.com', [$rdap])['hosting_details']['confidence']);
        $this->assertSame(30, HostingAttribution::resolve('example.com', [$asn])['hosting_details']['confidence']);
        $result = HostingAttribution::resolve('example.com', [$rdap, $asn]);
        $this->assertSame('Contabo', $result['hosting_provider']);
        $this->assertSame('inferred', $result['hosting_details']['status']);
        $this->assertSame(75, $result['hosting_details']['confidence']);
    }

    public function test_cdn_evidence_keeps_origin_unknown_even_with_matching_network_signals(): void
    {
        $result = HostingAttribution::resolve('example.com', [
            $this->signal('rdap', 'Contabo'), $this->signal('asn', 'Contabo'), $this->signal('http', 'Cloudflare', 'cdn'),
        ]);
        $this->assertNull($result['hosting_provider']);
        $this->assertSame(['Cloudflare'], $result['hosting_details']['cdn_providers']);
        $this->assertSame('unknown', $result['hosting_details']['status']);
    }

    public function test_headers_alone_are_insufficient_and_conflicts_pick_the_stronger_candidate(): void
    {
        $this->assertNull(HostingAttribution::resolve('example.com', [$this->signal('http', 'Vercel', 'platform')])['hosting_provider']);
        $result = HostingAttribution::resolve('example.com', [$this->signal('rdap', 'Contabo'), $this->signal('asn', 'Hetzner')]);
        $this->assertSame('Contabo', $result['hosting_provider']);
        $this->assertSame(45, $result['hosting_details']['confidence']);
        $this->assertSame('low', $result['hosting_details']['confidence_label']);
    }

    public function test_platform_cname_takes_precedence_over_underlying_infrastructure(): void
    {
        $result = HostingAttribution::resolve('example.com', [
            $this->signal('cname', 'Vercel', 'platform'), $this->signal('rdap', 'Amazon Web Services'), $this->signal('asn', 'Amazon Web Services'),
        ]);
        $this->assertSame('Vercel', $result['hosting_provider']);
    }

    public function test_matching_signals_win_over_another_candidate_and_duplicates_do_not_inflate_scores(): void
    {
        $evidence = [
            $this->signal('rdap', 'Contabo'), $this->signal('asn', 'Contabo'),
            $this->signal('rdap', 'Hetzner'), $this->signal('rdap', 'Hetzner'),
        ];
        $result = HostingAttribution::resolve('example.com', $evidence);
        $this->assertSame('Contabo', $result['hosting_provider']);
        $this->assertSame(75, $result['hosting_details']['confidence']);
        $this->assertSame(45, $result['hosting_details']['candidates'][1]['confidence']);
        $this->assertSame($result, HostingAttribution::resolve('example.com', $evidence));
    }

    public function test_tied_candidates_remain_unknown(): void
    {
        $result = HostingAttribution::resolve('example.com', [$this->signal('rdap', 'Contabo'), $this->signal('rdap', 'Hetzner')]);
        $this->assertNull($result['hosting_provider']);
        $this->assertNull($result['hosting_details']['confidence']);
    }

    public function test_saved_confidence_is_recomputed_and_manual_values_have_no_detection_score(): void
    {
        $details = HostingAttribution::resolve('example.com', [$this->signal('cname', 'Vercel', 'platform')])['hosting_details'];
        $details['confidence'] = 100;
        $saved = HostingAttribution::normalize('Vercel', $details, 'https://example.com');
        $this->assertSame(90, $saved['confidence']);
        $this->assertSame('high', $saved['confidence_label']);
        $this->assertNull(HostingAttribution::normalize('Manual host', $details, 'https://example.com')['confidence']);
        $this->assertNull(HostingAttribution::normalize(null, $details, 'https://example.com')['confidence']);
        $this->assertNull(HostingAttribution::normalize('Vercel', $details, 'https://other.com')['confidence']);
    }

    public function test_manual_changes_and_clearing_update_attribution_without_discarding_cdn_evidence(): void
    {
        $details = ['status' => 'inferred', 'provider' => 'Contabo', 'host' => 'example.com', 'cdn_providers' => ['Cloudflare']];
        $manual = HostingAttribution::normalize('My reseller', $details, 'https://example.com');
        $this->assertSame('user_provided', $manual['status']);
        $this->assertSame(['Cloudflare'], $manual['cdn_providers']);
        $this->assertSame('unknown', HostingAttribution::normalize(null, $details, 'https://example.com')['status']);
        $this->assertSame([], HostingAttribution::normalize('My reseller', $details, 'https://other.com')['cdn_providers']);
    }
}
