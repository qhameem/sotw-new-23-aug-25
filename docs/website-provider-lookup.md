# Website provider lookup

`/add-product` includes optional `hosting_provider` and `domain_registrar` fields (nullable strings, maximum 255 characters). Drafts, user/admin edits, approval and rejection preserve them. Separate proposed columns keep unapproved edits off live products; an empty proposed string represents removal.

The form automatically calls `POST /api/website-providers` after its URL changes. Manual values are never overwritten; late responses for old URLs are ignored. The lookup is independent of AI autofill and never blocks submission. A retry button is available.

Lookup combines Google DNS over HTTPS, RDAP endpoints from IANA's bootstrap lists, RIPEstat routing/ASN records, reverse DNS and public website response headers. No API key, shell WHOIS utility, queue worker, or web-server-specific configuration is needed. The PHP process requires outbound HTTPS and a functioning CA certificate bundle.

- Registrar lookup walks subdomains only after a registry returns 404; unsupported registries and unavailable data remain blank.
- Hosting is inferred from a provider-specific CNAME, or matching IP registrant and ASN/PTR evidence. Provider-specific headers are supporting clues, never sufficient alone. Conflicting signals remain unknown. CDN/proxy evidence leaves hosting blank. Unknown IP owners are informational only. Neither nameserver ownership nor registrar identity is treated as hosting evidence.
- Inferred hosting results are cached per hostname for six hours; unknown hosting results for ten minutes; IANA bootstrap lists for one day. Requests are throttled to ten per minute per client. Each external request has a two-second connection timeout and four-second total timeout. Redirects are disabled.
- Detection is best effort. Resellers, CDNs, registration-service outages and absent RDAP support can prevent identification. Verify suggested values before submitting.
- Domain names/IP addresses are shared with DNS/registration/RIPEstat services, never URL paths, query parameters or credentials. Website header checks use HTTPS HEAD at `/`, pin a public DNS IP, reject mixed public/private targets, disable proxies and redirects, and keep TLS verification enabled. At most one address per family is sampled; this is not an exhaustive network scan.
- `hosting_details` stores status (`inferred`, `user_provided`, `unknown`), hostname, candidate provider, evidence and separate CDN names. `proposed_hosting_details` follows edit moderation. Manual input becomes user-provided; clearing a value becomes unknown. Historical values without attribution remain unknown in the UI. No result is labelled verified.
- Provider mappings live in `config/website_providers.php`. Unsupported providers remain unknown until mappings are added. External services can fail independently without blocking submission.

## Deployment

Deploy the code and built Vite assets. Run the migration before serving the updated application:

```sh
php artisan migrate --path=database/migrations/2026_09_01_000000_add_provider_fields_to_products_table.php --force
php artisan migrate --path=database/migrations/2026_09_01_000001_add_hosting_details_to_products_table.php --force
```

No OpenLiteSpeed or aaPanel rewrite changes are required.

## Reference APIs

- [Google DNS JSON API](https://developers.google.com/speed/public-dns/docs/doh/json)
- [IANA RDAP bootstrap registries](https://data.iana.org/rdap/)

- [RIPEstat Network Info](https://stat.ripe.net/docs/data-api/api-endpoints/network-info.html)
