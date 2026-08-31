# Website provider lookup

`/add-product` includes optional `hosting_provider` and `domain_registrar` fields (nullable strings, maximum 255 characters). Drafts, user/admin edits, approval and rejection preserve them. Separate proposed columns keep unapproved edits off live products; an empty proposed string represents removal.

The form automatically calls `POST /api/website-providers` after its URL changes. Manual values are never overwritten; late responses for old URLs are ignored. The lookup is independent of AI autofill and never blocks submission. A retry button is available.

Lookup uses Google DNS over HTTPS and RDAP endpoints from IANA's bootstrap lists. No API key, shell WHOIS utility, queue worker, or web-server-specific configuration is needed. The PHP process requires outbound HTTPS and a functioning CA certificate bundle.

- Registrar lookup walks subdomains only after a registry returns 404; unsupported registries and unavailable data remain blank.
- Hosting is inferred from known CNAME targets or recognized infrastructure owners. CDN/proxy evidence leaves hosting blank. Unknown IP owners are informational only. Neither nameserver ownership nor registrar identity is treated as hosting evidence.
- Results are cached per hostname for six hours; IANA bootstrap lists for one day. Requests are throttled to ten per minute per client. Each external request has a two-second connection timeout and four-second total timeout. Redirects are disabled.
- Detection is best effort. Resellers, CDNs, registration-service outages and absent RDAP support can prevent identification. Verify suggested values before submitting.
- Only the hostname is shared with DNS/registration services, never URL paths, query parameters or credentials.

## Deployment

Deploy the code and built Vite assets. Run the migration before serving the updated application:

```sh
php artisan migrate --path=database/migrations/2026_09_01_000000_add_provider_fields_to_products_table.php --force
```

No OpenLiteSpeed or aaPanel rewrite changes are required.

## Reference APIs

- [Google DNS JSON API](https://developers.google.com/speed/public-dns/docs/doh/json)
- [IANA RDAP bootstrap registries](https://data.iana.org/rdap/)
