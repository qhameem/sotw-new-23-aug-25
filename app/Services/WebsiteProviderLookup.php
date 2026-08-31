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

        return Cache::remember('website-providers:v1:'.hash('sha256', $host), now()->addHours(6), function () use ($host) {
            $result = ['hosting_provider' => null, 'domain_registrar' => null, 'hosting_note' => null];
            // Fail independently: unavailable registration data must not prevent DNS lookup.
            try {
                $result['domain_registrar'] = $this->registrar($host);
            } catch (Throwable $e) {
                report($e);
            }
            try {
                [$result['hosting_provider'], $result['hosting_note']] = $this->hosting($host);
            } catch (Throwable $e) {
                report($e);
            }

            return $result;
        });
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
        $providers = [
            'vercel-dns.com' => 'Vercel', 'vercel.app' => 'Vercel',
            'netlify.app' => 'Netlify', 'netlify.com' => 'Netlify',
            'github.io' => 'GitHub Pages', 'herokudns.com' => 'Heroku',
            'azurewebsites.net' => 'Microsoft Azure', 'onrender.com' => 'Render',
            'fly.dev' => 'Fly.io', 'wpengine.com' => 'WP Engine',
            'myshopify.com' => 'Shopify', 'webflow.io' => 'Webflow',
        ];
        $edges = ['cloudflare.net', 'cdn.cloudflare.net', 'cloudfront.net', 'fastly.net', 'akamaiedge.net', 'edgekey.net'];
        $answers = [];
        foreach (['A', 'AAAA'] as $type) {
            $response = $this->request('https://dns.google/resolve', ['name' => $host, 'type' => $type]);
            if ($response->successful()) {
                $records = $response->json('Answer');
                $answers = array_merge($answers, is_array($records) ? $records : []);
            }
        }
        foreach ($answers as $record) {
            if (($record['type'] ?? null) !== 5) {
                continue;
            }
            $target = strtolower(rtrim($record['data'] ?? '', '.'));
            foreach ($edges as $suffix) {
                if ($this->matchesHost($target, $suffix)) {
                    return [null, 'A CDN/proxy was detected; the origin hosting provider is hidden.'];
                }
            }
            foreach ($providers as $suffix => $provider) {
                if ($this->matchesHost($target, $suffix)) {
                    return [$provider, 'Inferred from the website DNS target. Please verify.'];
                }
            }
        }
        foreach ($answers as $record) {
            $ip = $record['data'] ?? '';
            if (! in_array($record['type'] ?? null, [1, 28], true)
                || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                continue;
            }
            $base = $this->ipEndpoint($ip);
            if (! $base) {
                continue;
            }
            $response = $this->request($base.'ip/'.rawurlencode($ip));
            $data = $response->successful() ? $response->json() : null;
            if (! is_array($data)) {
                return [null, null];
            }
            $owner = null;
            foreach ($data['entities'] ?? [] as $entity) {
                if (in_array('registrant', $entity['roles'] ?? [], true)) {
                    $owner = $this->entityName($entity);
                    break;
                }
            }
            $network = (string) ($data['name'] ?? '');
            if (preg_match('/cloudflare|cloudfront|fastly|akamai|incapsula|imperva/i', ($owner ?? '').' '.$network)) {
                return [null, 'A CDN/proxy was detected; the origin hosting provider is hidden.'];
            }
            foreach (['amazon' => 'Amazon Web Services', 'digitalocean' => 'DigitalOcean', 'hetzner' => 'Hetzner', 'ovh' => 'OVHcloud', 'linode' => 'Akamai Cloud (Linode)', 'vultr' => 'Vultr', 'hostinger' => 'Hostinger'] as $match => $provider) {
                if (str_contains(strtolower($owner ?? ''), $match)) {
                    return [$provider, 'Inferred from IP ownership; a reseller or proxy may hide the actual host. Please verify.'];
                }
            }

            // Network names and IP owners are evidence, not proof of a hosting provider.
            return [null, $owner ? 'IP network owner: '.$owner.'. Hosting provider could not be confirmed.' : null];
        }

        return [null, null];
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
