<?php

return [
    // Verified registry endpoints for TLDs absent from IANA's RDAP bootstrap.
    'rdap_fallbacks' => [
        'io' => 'https://rdap.identitydigital.services/rdap/',
    ],
    'platform_domains' => [
        'vercel-dns.com' => 'Vercel', 'vercel.app' => 'Vercel',
        'netlify.app' => 'Netlify', 'netlify.com' => 'Netlify',
        'github.io' => 'GitHub Pages', 'herokudns.com' => 'Heroku',
        'azurewebsites.net' => 'Microsoft Azure', 'onrender.com' => 'Render',
        'fly.dev' => 'Fly.io', 'wpengine.com' => 'WP Engine',
        'myshopify.com' => 'Shopify', 'webflow.io' => 'Webflow',
        'pages.dev' => 'Cloudflare Pages',
    ],
    'cdn_domains' => [
        'cloudflare.net' => 'Cloudflare', 'cloudfront.net' => 'Amazon CloudFront',
        'fastly.net' => 'Fastly', 'akamaiedge.net' => 'Akamai', 'edgekey.net' => 'Akamai',
    ],
    'network_names' => [
        'amazon' => 'Amazon Web Services', 'digitalocean' => 'DigitalOcean',
        'hetzner' => 'Hetzner', 'ovh' => 'OVHcloud', 'linode' => 'Akamai Cloud (Linode)',
        'vultr' => 'Vultr', 'choopa' => 'Vultr', 'hostinger' => 'Hostinger',
        'contabo' => 'Contabo', 'microsoft' => 'Microsoft Azure', 'google' => 'Google Cloud',
    ],
    'cdn_names' => [
        'cloudflare' => 'Cloudflare', 'cloudfront' => 'Amazon CloudFront',
        'fastly' => 'Fastly', 'akamai' => 'Akamai', 'incapsula' => 'Imperva', 'imperva' => 'Imperva',
    ],
    'ptr_domains' => [
        'contaboserver.net' => 'Contabo', 'your-server.de' => 'Hetzner',
        'digitalocean.com' => 'DigitalOcean', 'vultrusercontent.com' => 'Vultr',
        'amazonaws.com' => 'Amazon Web Services',
    ],
];
