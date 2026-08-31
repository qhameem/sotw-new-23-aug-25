<?php

namespace Tests\Unit;

use App\Services\WebsiteProviderLookup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteProviderLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_subdomain_lookup_uses_registry_response_and_caches_by_host(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => [[['uk'], ['https://registry.example/']]]]),
            'https://registry.example/domain/www.sample.co.uk' => Http::response([], 404),
            'https://registry.example/domain/sample.co.uk' => Http::response([
                'ldhName' => 'sample.co.uk',
                'entities' => [['roles' => ['registrar'], 'vcardArray' => ['vcard', [['fn', [], 'text', 'Example Registrar']]]]],
            ]),
            'https://dns.google/resolve*' => Http::response(['Answer' => [['type' => 5, 'data' => 'sample.vercel-dns.com.']]]),
        ]);
        $service = app(WebsiteProviderLookup::class);
        $result = $service->lookup('https://www.sample.co.uk/path');
        $this->assertSame('Example Registrar', $result['domain_registrar']);
        $this->assertSame('Vercel', $result['hosting_provider']);
        $this->assertSame($result, $service->lookup('https://WWW.sample.co.uk/other'));
        Http::assertSentCount(5);
    }

    public function test_cloudflare_is_not_reported_as_origin_hosting(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => []]),
            'https://dns.google/resolve*' => Http::response(['Answer' => [['type' => 1, 'data' => '104.16.0.1']]]),
            'https://data.iana.org/rdap/ipv4.json' => Http::response(['services' => [[['104.0.0.0/8'], ['https://registry.example/']]]]),
            'https://registry.example/ip/104.16.0.1' => Http::response(['name' => 'CLOUDFLARENET']),
        ]);
        $result = app(WebsiteProviderLookup::class)->lookup('https://sample.com');
        $this->assertNull($result['hosting_provider']);
        $this->assertStringContainsString('CDN', $result['hosting_note']);
    }

    public function test_failures_and_private_addresses_leave_fields_optional(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => [[['com'], ['https://registry.example/']]]]),
            'https://registry.example/*' => Http::response([], 503),
            'https://dns.google/resolve*' => Http::response(['Answer' => [['type' => 1, 'data' => '127.0.0.1']]]),
        ]);
        $result = app(WebsiteProviderLookup::class)->lookup('https://sample.com');
        $this->assertNull($result['hosting_provider']);
        $this->assertNull($result['domain_registrar']);
        Http::assertSentCount(4);
    }

    public function test_dns_provider_suffix_must_match_a_label_boundary(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => []]),
            'https://dns.google/resolve*' => Http::response(['Answer' => [['type' => 5, 'data' => 'fakevercel-dns.com.']]]),
        ]);
        $this->assertNull(app(WebsiteProviderLookup::class)->lookup('https://sample.com')['hosting_provider']);
    }

    public function test_lookup_combines_contabo_rdap_asn_and_ptr_with_safe_header_request(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => []]),
            'https://dns.google/resolve*' => function ($request) {
                return Http::response(['Answer' => $request['type'] === 'PTR'
                    ? [['type' => 12, 'data' => 'vmi123.contaboserver.net.']]
                    : [['type' => 1, 'data' => '8.8.8.8']]]);
            },
            'https://data.iana.org/rdap/ipv4.json' => Http::response(['services' => [[['8.0.0.0/8'], ['https://registry.example/']]]]),
            'https://registry.example/ip/8.8.8.8' => Http::response([
                'entities' => [['roles' => ['registrant'], 'vcardArray' => ['vcard', [['fn', [], 'text', 'MNT-CONTABO']]]]],
            ]),
            'https://stat.ripe.net/data/network-info/data.json*' => Http::response(['data' => ['asns' => [51167]]]),
            'https://stat.ripe.net/data/as-overview/data.json*' => Http::response(['data' => ['holder' => 'CONTABO Contabo GmbH']]),
            'https://sample.com/' => Http::response('', 200),
        ]);
        $result = app(WebsiteProviderLookup::class)->lookup('https://sample.com/private/path?token=not-shared');
        $this->assertSame('Contabo', $result['hosting_provider']);
        $this->assertSame('inferred', $result['hosting_details']['status']);
        $this->assertCount(3, $result['hosting_details']['evidence']);
        Http::assertSent(fn ($request) => $request->url() === 'https://sample.com/' && $request->method() === 'HEAD');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'not-shared'));
    }

    public function test_mixed_public_and_private_dns_never_contacts_the_origin(): void
    {
        Http::fake([
            'https://data.iana.org/rdap/dns.json' => Http::response(['services' => []]),
            'https://data.iana.org/rdap/ipv4.json' => Http::response(['services' => []]),
            'https://dns.google/resolve*' => Http::response(['Answer' => [
                ['type' => 1, 'data' => '8.8.8.8'], ['type' => 1, 'data' => '127.0.0.1'],
            ]]),
            'https://stat.ripe.net/*' => Http::response(['data' => []]),
        ]);
        app(WebsiteProviderLookup::class)->lookup('https://sample.com');
        Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'https://sample.com'));
    }

    public function test_lookup_endpoint_rejects_private_and_non_http_urls_without_network_requests(): void
    {
        Http::fake();
        foreach (['http://127.0.0.1', 'http://localhost', 'https://site.internal', 'ftp://sample.com', 'https://user:secret@sample.com'] as $url) {
            $this->postJson('/api/website-providers', ['url' => $url])->assertUnprocessable();
        }
        Http::assertNothingSent();
    }
}
