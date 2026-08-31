<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class WebsiteProviderLookup
{
    public function lookup(string $url): array
    {
        $parts = parse_url($url);
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])
            || ! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || ! str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_IP)
            || preg_match('/\.(local|internal|localhost|test|invalid|example|lan|home)$/', $host)) {
            throw new InvalidArgumentException('Enter a public website URL with a domain name.');
        }

        $key = 'website-providers:v3:'.hash('sha256', $host);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }
        $result = (function () use ($host) {
            $result = array_merge(\App\Support\HostingAttribution::resolve($host, []), ['domain_registrar' => null]);
            // Fail independently: unavailable registration data must not prevent DNS lookup.
            try {
                $result['domain_registrar'] = $this->registrar($host);
            } catch (Throwable $e) {
                report($e);
            }
            try {
                $result = array_merge($result, $this->hosting($host));
            } catch (Throwable $e) {
                report($e);
            }

            return $result;
        })();
        Cache::put($key, $result, $result['hosting_provider'] ? now()->addHours(6) : now()->addMinutes(10));

        return $result;
    }

    private function registrar(string $host): ?string
    {
        $labels = explode('.', $host);
        $tld = end($labels);
        $base = null;
        foreach ($this->bootstrap('dns') as [$suffixes, $urls]) {
            if (in_array($tld, $suffixes, true)) {
                $base = $this->httpsEndpoint($urls);
                break;
            }
        }
        if (! $base) {
            return null;
        }
        // Start at the full hostname; never assume a two-label registrable domain.
        for ($attempt = 0; count($labels) >= 2 && $attempt < 5; $attempt++, array_shift($labels)) {
            $domain = implode('.', $labels);
            $response = $this->request($base.'domain/'.rawurlencode($domain));
            if ($response->status() === 404) {
                continue;
            }
            if (! $response->successful()) {
                return null;
            }
            $data = $response->json();
            if (! is_array($data) || strcasecmp($data['ldhName'] ?? '', $domain) !== 0) {
                return null;
            }
            foreach ($data['entities'] ?? [] as $entity) {
                if (in_array('registrar', $entity['roles'] ?? [], true)) {
                    return $this->entityName($entity);
                }
            }

            return null;
        }

        return null;
    }

    private function hosting(string $host): array
    {
        $evidence = [];
        $answers = [];
        foreach (['A', 'AAAA'] as $type) {
            $records = $this->attempt(fn () => $this->request('https://dns.google/resolve', ['name' => $host, 'type' => $type])->json('Answer'));
            $answers = array_merge($answers, is_array($records) ? $records : []);
        }
        foreach ($answers as $record) {
            if (($record['type'] ?? null) === 5) {
                $target = strtolower(rtrim($record['data'] ?? '', '.'));
                $provider = $this->domainProvider($target, 'cdn_domains');
                $this->addEvidence($evidence, 'cname', $target, $provider ?: $this->domainProvider($target, 'platform_domains'), $provider ? 'cdn' : 'platform');
            }
        }
        $ips = array_values(array_unique(array_column(array_filter($answers, fn ($record) => in_array($record['type'] ?? null, [1, 28], true)), 'data')));
        $publicIps = array_values(array_filter($ips, fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)));
        // Bound work while sampling both address families when available.
        $sample = [];
        foreach ($publicIps as $ip) {
            $sample[str_contains($ip, ':') ? 'ipv6' : 'ipv4'] ??= $ip;
        }
        foreach ($sample as $ip) {
            $data = $this->attempt(function () use ($ip) {
                $base = $this->ipEndpoint($ip);

                return $base ? $this->request($base.'ip/'.rawurlencode($ip))->json() : null;
            });
            if (is_array($data)) {
                $names = [(string) ($data['name'] ?? '')];
                foreach ($data['entities'] ?? [] as $entity) {
                    if (in_array('registrant', $entity['roles'] ?? [], true)) {
                        $names[] = $this->entityName($entity) ?? '';
                    }
                }
                $name = trim(implode(' ', array_unique($names)));
                $this->networkEvidence($evidence, 'rdap', $ip.' — '.$name, $name);
            }
            $network = $this->attempt(fn () => $this->request('https://stat.ripe.net/data/network-info/data.json', ['resource' => $ip])->json('data'));
            foreach (array_slice($network['asns'] ?? [], 0, 1) as $asn) {
                if (! ctype_digit((string) $asn)) {
                    continue;
                }
                $holder = $this->attempt(fn () => $this->request('https://stat.ripe.net/data/as-overview/data.json', ['resource' => 'AS'.$asn])->json('data.holder'));
                if (is_string($holder)) {
                    $this->networkEvidence($evidence, 'asn', 'AS'.$asn.' — '.$holder, $holder);
                }
            }
            $reverse = str_contains($ip, ':')
                ? implode('.', str_split(strrev(bin2hex(inet_pton($ip))))).'.ip6.arpa'
                : implode('.', array_reverse(explode('.', $ip))).'.in-addr.arpa';
            $ptrs = $this->attempt(fn () => $this->request('https://dns.google/resolve', ['name' => $reverse, 'type' => 'PTR'])->json('Answer'));
            foreach (array_slice(is_array($ptrs) ? $ptrs : [], 0, 2) as $ptr) {
                if (($ptr['type'] ?? null) === 12) {
                    $target = strtolower(rtrim($ptr['data'] ?? '', '.'));
                    $cdn = $this->domainProvider($target, 'cdn_domains');
                    $this->addEvidence($evidence, 'ptr', $target, $cdn ?: $this->domainProvider($target, 'ptr_domains'), $cdn ? 'cdn' : 'infrastructure');
                }
            }
        }
        // Never connect to private/mixed-address targets or follow origin redirects.
        if ($publicIps !== [] && count($ips) === count($publicIps) && function_exists('curl_init')) {
            $headers = $this->attempt(fn () => $this->originHeaders($host, $publicIps[0]));
            if ($headers instanceof \Illuminate\Http\Client\Response) {
                foreach (['x-vercel-id' => 'Vercel', 'x-nf-request-id' => 'Netlify'] as $header => $provider) {
                    if ($headers->header($header)) {
                        $this->addEvidence($evidence, 'http', $header.' present', $provider, 'platform');
                    }
                }
                $server = $headers->header('server');
                $cdn = $this->nameProvider($server, 'cdn_names');
                if ($headers->header('cf-ray')) {
                    $cdn = 'Cloudflare';
                } elseif ($headers->header('x-amz-cf-id')) {
                    $cdn = 'Amazon CloudFront';
                }
                if ($cdn) {
                    $this->addEvidence($evidence, 'http', 'CDN response headers: '.$cdn, $cdn, 'cdn');
                }
            }
        }

        return \App\Support\HostingAttribution::resolve($host, array_values($evidence));
    }

    private function originHeaders(string $host, string $ip): \Illuminate\Http\Client\Response
    {
        $address = str_contains($ip, ':') ? '['.$ip.']' : $ip;

        return Http::connectTimeout(2)->timeout(4)->withoutRedirecting()
            ->withOptions(['proxy' => '', 'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$address]]])
            ->head('https://'.$host.'/');
    }

    private function attempt(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function networkEvidence(array &$evidence, string $type, string $value, string $name): void
    {
        $cdn = $this->nameProvider($name, 'cdn_names');
        // Linode is cloud compute; an Akamai name alone remains CDN evidence.
        $provider = $this->nameProvider($name, 'network_names');
        if ($provider === 'Akamai Cloud (Linode)') {
            $cdn = null;
        }
        $this->addEvidence($evidence, $type, $value, $cdn ?: $provider, $cdn ? 'cdn' : 'infrastructure');
    }

    private function addEvidence(array &$evidence, string $type, string $value, ?string $provider, string $role): void
    {
        if (trim($value) !== '') {
            $evidence[$type.':'.$value] = ['type' => $type, 'value' => mb_substr($value, 0, 500), 'provider' => $provider, 'role' => $role];
        }
    }

    private function domainProvider(string $host, string $map): ?string
    {
        if ($map === 'platform_domains' && preg_match('/(^|\.)vercel-dns-[0-9]+\.com$/', $host)) {
            return 'Vercel';
        }
        foreach (config('website_providers.'.$map, []) as $suffix => $provider) {
            if ($this->matchesHost($host, $suffix)) {
                return $provider;
            }
        }

        return null;
    }

    private function nameProvider(string $name, string $map): ?string
    {
        foreach (config('website_providers.'.$map, []) as $match => $provider) {
            if (preg_match('/(?<![a-z])'.preg_quote($match, '/').'(?![a-z])/i', $name)) {
                return $provider;
            }
        }
        // Common RDAP network labels concatenate the company and NET.
        if ($map === 'cdn_names' && preg_match('/cloudflarenet/i', $name)) {
            return 'Cloudflare';
        }

        return null;
    }

    private function ipEndpoint(string $ip): ?string
    {
        $address = inet_pton($ip);
        $best = -1;
        $endpoint = null;
        foreach ($this->bootstrap(strlen($address) === 4 ? 'ipv4' : 'ipv6') as [$ranges, $urls]) {
            foreach ($ranges as $range) {
                [$network, $bits] = explode('/', $range);
                $packed = inet_pton($network);
                $bits = (int) $bits;
                if ($packed === false || strlen($packed) !== strlen($address) || $bits <= $best) {
                    continue;
                }
                $bytes = intdiv($bits, 8);
                $remaining = $bits % 8;
                if (substr($address, 0, $bytes) === substr($packed, 0, $bytes)
                    && (! $remaining || ((ord($address[$bytes]) ^ ord($packed[$bytes])) & (255 << (8 - $remaining))) === 0)) {
                    $endpoint = $this->httpsEndpoint($urls);
                    $best = $bits;
                }
            }
        }

        return $endpoint;
    }

    private function bootstrap(string $type): array
    {
        return Cache::remember('website-providers:bootstrap:'.$type, now()->addDay(), function () use ($type) {
            return $this->request('https://data.iana.org/rdap/'.$type.'.json')->throw()->json('services') ?? [];
        });
    }

    private function httpsEndpoint(array $urls): ?string
    {
        foreach ($urls as $url) {
            if (str_starts_with($url, 'https://')) {
                return rtrim($url, '/').'/';
            }
        }

        return null;
    }

    private function request(string $url, array $query = []): \Illuminate\Http\Client\Response
    {
        // Only fixed DNS/IANA services and IANA-listed registries are contacted.
        // Disable redirects so a registry cannot redirect requests to private services.
        return Http::acceptJson()->connectTimeout(2)->timeout(4)->withoutRedirecting()->get($url, $query);
    }

    private function entityName(array $entity): ?string
    {
        foreach ($entity['vcardArray'][1] ?? [] as $property) {
            if (($property[0] ?? null) === 'fn' && is_string($property[3] ?? null)) {
                $name = trim($property[3]);

                return $name !== '' ? mb_substr($name, 0, 255) : null;
            }
        }

        return null;
    }

    private function matchesHost(string $host, string $suffix): bool
    {
        return $host === $suffix || str_ends_with($host, '.'.$suffix);
    }
}
