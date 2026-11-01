# SEO audit fixes — 1 November 2026

1. Pagination
   - All broad-category paginator links use `/software/{group}/page/{page}`.
   - Legacy `?page=N` requests permanently redirect to the corresponding path. `/page/1` redirects to the base URL; malformed page arrays return 404.
   - Regression coverage checks numbered links, redirects, and a different page-2 product set.

2. Duplicate comparisons
   - `ComparisonUrl` centralizes deterministic string sorting of product slugs.
   - Reversed pairs redirect permanently after both products are validated. Self comparisons return 404.
   - Product research links, alternatives links, breadcrumbs, canonical tags, and comparison sitemaps use the same ordering.

3. Weekly sitemap conflicts
   - Preserve existing weekly `noindex, follow` policy.
   - Remove weekly archives from sitemap generation. Monthly and yearly archives remain included.
   - Historical weekly pages reference themselves canonically instead of unrelated homepage content.
   - Comparison sitemap generation also excludes pairs failing relevance/content eligibility.
   - Existing deployed XML files require regeneration; editing generator code does not update them automatically.

4. Comparison and alternatives quality
   - Comparison pages show stored summaries, features, intended users, limitations, pricing models, known amounts, and platform details. Unknown values are labeled “Not listed”.
   - Replace the unsupported “Which is Better?” promise with “Features, Pricing and Fit”.
   - Index eligibility now requires both products to provide at least two listed features and audience or limitation detail, in addition to relevance or explicit curation. Curation alone cannot bypass the content requirement.
   - Broad AI/automation/category overlap requires strong textual positioning overlap; audience and pricing overlap alone no longer rescue weak matches.
   - Remove repeated generic replacement warnings. Alternative feature highlights use structured product features where available.
   - Recommendation cache namespace advances to v3 so previous matching results are not reused.
   - This improves use of existing facts; it does not create independently researched prices, feature tests, or reviews.

5. Software offers and rich results
   - `ProductOffer` supplies one price/currency interpretation for product details, comparisons, alternative tables, and JSON-LD.
   - Explicit zero prices in `facts_json.price_amount` are supported. A database-default zero still does not establish a free plan.
   - Category-only Offer objects are removed from alternatives schema. Unknown/invalid prices produce no offer.
   - Genuine rating/review data is unavailable in the current product schema. Existing ProductReview records are review-service requests, not published rated reviews. No ratings, reviews, or prices were fabricated.
   - Software rich-result eligibility therefore remains conditional on genuine, visible offer and review data. Missing reviews do not require removing otherwise useful descriptive SoftwareApplication markup.
   - Requirements: https://developers.google.com/search/docs/appearance/structured-data/software-app

6. Metadata
   - Shared layout provides a canonical fallback, removes tracking parameters, and retains valid numbered query pages.
   - About page receives a specific description.
   - Overlong generated product titles use a complete category/name fallback instead of cutting the tagline mid-phrase. Manually configured SEO titles remain authoritative.
   - Configured homepage titles override the generated default. Metadata memoization is request-local, avoiding stale static metadata across requests in long-lived workers.

7. Performance and cache policy
   - Narrow recommendation candidate queries to shared software categories. This avoids fetching pricing-only/audience-only/tech-only candidates that cannot pass the matcher and can crowd out relevant products under the 250-candidate cap.
   - Memoize editorial parsing by description hash within a scoped service. Reuse related-product service preloads within the request.
   - Preserve existing public-visibility checks and early termination of research-link generation.
   - Full-page shared caching remains disabled: guest HTML contains session/CSRF state and authenticated HTML contains personal controls. Enabling shared HTML caching was not a safe fix for this application.
   - The live audit already exposed Server-Timing: homepage app 569.7 ms / DB 309.4 ms (44 queries); StoryShort app 246.4 ms / DB 122.5 ms (30 queries); StoryShort alternatives app 1388.2 ms / DB 256.2 ms (21 queries). These were single pre-change observations, not a benchmark or field Core Web Vitals.
   - No production timing improvement is claimed before deployment and repeated measurement. Server configuration was not changed.

8. Validation
   - 55 focused tests pass (286 assertions): broad-category pagination, comparison eligibility/canonicalization, sitemap generation, metadata fallbacks, configured homepage metadata, offer/schema behavior, contextual research links, product page structure, cache isolation, performance instrumentation, related-product scoring, and editorial parsing.
   - PublicProductDiscoveryTest adds 17 passing tests and two failures: 72 passing tests overall, two failures. Both older failing checks (weekly navigation and an exact FAQ CSS-class selector) were reproduced against unmodified HEAD in an isolated snapshot. They are not silently treated as passing.
   - PHP formatting and whitespace checks run on changed files.

9. Deployment and verification
   - Changes are local and uncommitted. The existing production workflow deploys tracked files when master is pushed; it does not execute Artisan or regenerate sitemap XML.
   - After deployment, clear compiled application/view caches, regenerate core and comparison sitemaps with `php artisan sitemap:generate --with-pseo`, and regenerate alternatives with `php artisan sitemap:generate-alternatives`. Recommendation rebuilding may be slow with a large product inventory; run outside peak traffic.
   - Check a legacy page query redirects to the correct path, reversed comparisons redirect once, weekly URLs are absent from the regenerated archive sitemap, and thin comparisons are absent from the comparison sitemap.
   - Recheck raw metadata and offers against visible content. Use Google's Rich Results Test for eligible products and Search Console URL Inspection for Google-selected canonicals.
   - Repeat anonymous and authenticated header checks, then compare Server-Timing/TTFB under the same warm/cold conditions. Use field data or PageSpeed separately for Core Web Vitals.
