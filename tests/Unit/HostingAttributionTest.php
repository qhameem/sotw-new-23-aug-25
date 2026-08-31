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

    public function test_matching_rdap_and_asn_infer_contabo_but_either_alone_is_unknown(): void
    {
        $rdap = $this->signal('rdap', 'Contabo');
        $asn = $this->signal('asn', 'Contabo');
        $this->assertNull(HostingAttribution::resolve('example.com', [$rdap])['hosting_provider']);
        $this->assertNull(HostingAttribution::resolve('example.com', [$asn])['hosting_provider']);
        $result = HostingAttribution::resolve('example.com', [$rdap, $asn]);
        $this->assertSame('Contabo', $result['hosting_provider']);
        $this->assertSame('inferred', $result['hosting_details']['status']);
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

    public function test_headers_alone_and_conflicting_networks_are_not_enough(): void
    {
        $this->assertNull(HostingAttribution::resolve('example.com', [$this->signal('http', 'Vercel', 'platform')])['hosting_provider']);
        $result = HostingAttribution::resolve('example.com', [$this->signal('rdap', 'Contabo'), $this->signal('asn', 'Hetzner')]);
        $this->assertNull($result['hosting_provider']);
        $this->assertStringContainsString('Conflicting', $result['hosting_note']);
    }

    public function test_platform_cname_takes_precedence_over_underlying_infrastructure(): void
    {
        $result = HostingAttribution::resolve('example.com', [
            $this->signal('cname', 'Vercel', 'platform'), $this->signal('rdap', 'Amazon Web Services'), $this->signal('asn', 'Amazon Web Services'),
        ]);
        $this->assertSame('Vercel', $result['hosting_provider']);
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
