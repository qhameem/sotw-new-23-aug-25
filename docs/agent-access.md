# Agent access

## URLs

- `/index.md`, `/<public-path>.md`: Markdown alternates.
- `/<public-path>` with `Accept: text/markdown`: Markdown only when its q-value exceeds HTML's. Missing Accept and wildcard-only requests remain HTML.
- `/api/md/<public-path>`: always Markdown, including errors. `/api/md/` renders the homepage.
- `/api/md/_catalog?offset=0&limit=50`: paginated JSON catalog; follow `next`. Up to 100 entries/request; scanning invalid/noindex candidates is bounded to 200/request.
- `/llms.txt`, `/llms-full.txt`, `/ai.txt`, `/robots.txt`: generated discovery/policy documents.
- Existing `/product/<slug>/document.md` aliases the complete product document; `.json` retains schema version 1.

No `/pricing` route exists. Verification uses `/about`.

## Architecture and source of truth

`Registry::definitions()` maps Laravel route names/patterns to renderers, discovery groups and catalog enumeration. HTML route binding and controllers load data through `PublicPages\PageData`; no HTTP fetches or rendered-page scraping. The loader temporarily installs a guest request, session and auth manager, then restores the original context in `finally`. Markdown does not run route middleware or render HTML views.

Product HTML and Markdown share `ProductController::publicPageView`, `ProductPageSections`, and `ProductDetailData`. Rich text stored in database records uses the existing DOM converter, enhanced for code fences and inline formatting. Only explicitly public fields are serialized.

Static Markdown templates are transcribed from Blade **source** by `scripts/sync-agent-static.php`; they never scrape rendered pages. Original HTML templates remain unchanged visually. Their source hashes are checked in tests. When editing static copy, run `php scripts/sync-agent-static.php` and review the resulting Markdown. Submission conditions, launch pricing, tool branding and guide contents continue to use existing shared sources.

Identity uses existing `app.name`, `app.url`, theme logo, plus centralized description/contact/social fields in `config/agent_access.php`. Organization JSON-LD, llms.txt and ai.txt share these values. Local `APP_NAME` currently resolves to `Laravel`; deployment must set it to the public site name. Existing Premium Spot details contain differing prices ($149 and $157); Markdown preserves that source copy rather than choosing a new price.

## Add a page

Register its existing Laravel route name in `Registry::definitions()`:

```php
'new-page' => ['static:new-page', 'Page title', 'Factual summary.', 'Core pages'],
```

Provide its renderer/template. For a custom data-driven renderer, use its class as the first value and an optional enumerator as the fifth value:

```php
'guides.show' => [
    GuideRenderer::class, 'Guide', 'Published guides.', 'Docs / Guides',
    fn () => Guide::published()->cursor()->map(fn ($guide) => '/guides/'.$guide->slug),
],
```

The custom renderer implements `render(array $controllerViewData, string $canonical): array`, returning `Format::page(...)`. Controllers must return an unrendered Blade `View`; extract view construction first if a controller eagerly renders a response. This one registry registration feeds negotiation, alternate links, catalog and discovery. Authenticated/private routes stay excluded. Public routes intentionally omitted require a reason in `config/agent_access.php`. The route coverage test fails on unclassified GET routes.

## Privacy and caching

- Reject missing, unapproved, unpublished, future-published, draft and noindex records. Reject private collections even for authenticated owners.
- Omit auth/admin/editor/preview/checkout/account pages, tool results with tokens, exports and session to do lists. `/premium-spot` selects the signed-in user's products and is excluded; `/premium-spot-details` is public.
- Tool landing dispatch resolves the existing duplicate route registration while keeping configured URLs and legacy redirects.
- Public Markdown bodies cache for one hour. Discovery caches for one day, with a shared 500,000-byte budget and candidate limits. Every full-content page uses the registry.
- Content-model save/delete events rotate a cache version. Bulk SQL/pivot-only changes and settings-file changes should run `php artisan agent:refresh` afterward. Stable cache keys replace stale documents, avoiding abandoned files after version changes.
- Public cache headers apply only to anonymous machine endpoints intercepted before Laravel sessions. Existing HTML/LiteSpeed page-cache protections remain intact. No user cookies are added to machine responses.
- Robots and ai.txt share configured private paths. Search/user-fetch bots are explicitly permitted; existing training-bot behavior is retained pending owner choices. Robots is advisory, not authorization.

## Deployment: aaPanel / OpenLiteSpeed

Deploy the deletion of `public/robots.txt`; Laravel now generates it. A leftover static robots file would bypass Laravel. Keep the existing front-controller rewrite so unknown `.md` URLs reach Laravel. No Next.js/edge rules or Node production server are required.

Use the normal Laravel deployment cache rebuild, then `php artisan agent:refresh` to invalidate documents and warm llms.txt/full. Ensure `APP_URL` is the canonical HTTPS origin and `APP_NAME` is the public brand. Existing Laravel scheduler refreshes discovery daily at 05:00 in the application timezone. No commit, push or deployment was performed.

Existing LiteSpeed rules bypass shared full-page caching for dynamic routes. That protection remains; document bodies use Laravel's cache independently. Configure CDN/LSCache caching of anonymous responses separately if desired, respecting `Vary: Accept` and never caching Set-Cookie responses.

## Verification

See [route inventory](agent-route-inventory.md) and [actual curl output](agent-access-verification.md).

Targeted feature tests cover q-values, suffix/direct routes, HTML defaults, HEAD/OPTIONS/CORS, 404s, real Markdown route preservation, public product/article/category data, collection withdrawal, tool session privacy, canonical pagination, identity/discovery and route completeness. Existing product structure/JSON-LD and category/tool redirect tests are also run. New PHP is checked with Pint; all changed PHP is syntax checked, Blade views compiled and Vite production build run.

MCP discovery skipped: this application does not establish an MCP server. OpenAPI omitted: no existing specification found. Search pages, redirects, feeds, fragments and infrastructure are explicitly excluded.

## Results and existing failures

- 43 targeted tests passed (304 assertions).
- The broader `PublicProductDiscoveryTest` suite has three failures: previous-week link expectation, weekly canonical markup expectation, and FAQ `<details class="group">` expectation. An isolated archive of untouched `HEAD`, using the same PHP 8.4.27 binary, reproduced all three: 19 tests, 65 assertions, 3 failures. These assertions were not changed.
- Existing `/articles/feed` references a missing `articles.feed` view. Markdown negotiation excludes this XML endpoint; its existing failure is unchanged.
- Pint passed for all new PHP; changed PHP syntax, Blade compilation and Vite build passed.
- No database migrations or new dependencies added.

Standards referenced: [llms.txt](https://llmstxt.org/), [AI usage guidance](https://www.ai-visibility.org.uk/specifications/ai-txt/). The requested bracketed ai.txt sections are retained.

## Files added or changed

- `app/Console/Commands/RefreshAgentDocuments.php`
- `app/Http/Controllers/AgentAccessController.php`
- `app/Http/Controllers/MarkdownController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/PublicToolController.php`
- `app/Http/Middleware/AgentDiscovery.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/Markdown/CacheVersion.php`
- `app/Services/Markdown/Discovery.php`
- `app/Services/Markdown/Format.php`
- `app/Services/Markdown/Http.php`
- `app/Services/Markdown/Registry.php`
- `app/Services/Markdown/Renderers/Content.php`
- `app/Services/Markdown/Renderers/StaticContent.php`
- `app/Services/ProductDetailData.php`
- `app/Services/ProductPageMarkdown.php`
- `app/Services/ProductStructuredData.php`
- `app/Services/PublicPages/PageData.php`
- `app/Support/SiteIdentity.php`
- `bootstrap/app.php`
- `config/agent_access.php`
- `docs/agent-access-verification.md`
- `docs/agent-access.md`
- `docs/agent-route-inventory.md`
- `public/robots.txt` (removed; dynamic route replaces it)
- `resources/markdown/about.blade.php`
- `resources/markdown/faq.blade.php`
- `resources/markdown/get-the-badge.blade.php`
- `resources/markdown/legal.blade.php`
- `resources/markdown/premium-spot-details.blade.php`
- `resources/markdown/refund-policy.blade.php`
- `resources/markdown/software-review.blade.php`
- `resources/markdown/sources.json`
- `resources/markdown/todo.blade.php`
- `resources/views/components/footer.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/launch-readiness.blade.php`
- `resources/views/layouts/submission.blade.php`
- `resources/views/layouts/todolist.blade.php`
- `resources/views/partials/agent-head.blade.php`
- `resources/views/products/partials/_structured-details.blade.php`
- `resources/views/products/show.blade.php`
- `routes/console.php`
- `routes/web.php`
- `scripts/sync-agent-static.php`
- `tests/Feature/AgentAccess/MarkdownTest.php`
- `tests/Feature/AgentAccessTest.php`
