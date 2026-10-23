# Response performance and cache isolation

Measured locally on 23 October 2026 against the existing MySQL database. These are individual request observations, not a production speed guarantee or a Core Web Vitals assessment.

| Request | Before application time | After application time | Before queries | After queries |
| --- | ---: | ---: | ---: | ---: |
| Homepage, warm caches | 250.3 ms | 137.9 ms | 31 | 30 |
| Alternatives directory, recommendation caches populated | 1320.3 ms | 188.5 ms | 71 | 34 |

The original alternatives directory spent approximately 1044 ms in two nested category-count queries. The replacement groups eligible categories once in subqueries instead of counting peers repeatedly for each product. Recommendation cache entries are fetched in one batch. Cold recommendation rebuilding was also observed and is materially slower; source profiles are now calculated once per candidate pass rather than once per candidate.

## Changes

- Reuse versioned, ten-minute public archive-week data for navigation and date boundaries. Existing product-observer version invalidation applies to publication changes.
- Avoid querying personal to-do lists outside to-do routes and scheduled-product statistics outside the authorized approval page.
- Check outbound-rule table existence only when the existing rule cache rebuilds.
- Preserve per-request authentication, session state, CSRF tokens and live product visibility checks. No rendered page bodies or personal view data are added to shared caches.
- Retain Laravel's built-in cookie/session/CSRF middleware; remove redundant registrations and the additional CSRF validator.
- Measure all web routes using a single database listener and request-local counters. Counters are removed in a finally block; listeners do not accumulate across requests.

## Public HTML caching remains disabled

Guest HTML still includes session cookies and CSRF tokens. Authenticated HTML also includes user-specific navigation and controls. Full-page shared caching must remain disabled until that session-dependent state is separated from cacheable public content.

HTML, JSON, redirects and error responses receive:

- `Cache-Control: private, no-store, no-cache, max-age=0, must-revalidate`
- `X-LiteSpeed-Cache-Control: no-cache`
- `Vary: Cookie, Authorization` (existing Vary values retained)
- An expired Expires header; any Surrogate-Control override is removed.

Both project-root and public-root rewrite files bypass LiteSpeed full-page caching for application routes and direct PHP requests. Existing file caching for CSS, JavaScript, fonts and images is retained. Published product Open Graph image responses retain their existing explicit image cache policy.

Origin-response isolation cannot prove that an external proxy or an overridden virtual-host rule obeys the policy. Recheck live headers after deployment, including authenticated, logged-out, login and error responses. Never enable forced caching that ignores the origin's no-cache policy. Any previously cached personalized HTML must be purged before testing.

Official LiteSpeed behavior: https://docs.litespeedtech.com/lscache/devguide/controls/ (response headers and rewrite cache-control).

## Evidence and limits

Three anonymous production homepage requests before changes returned 200, private/no-cache headers and session cookies, without an X-LiteSpeed-Cache hit/miss header. Their application timings were 613.4, 212.7 and 189.7 ms. Full response transfer took 3.0–3.6 seconds, including network connection and body transfer; it was not a database-only measurement.

Local tests cover admin/member/guest HTML separation, distinct CSRF tokens, absence of another user's identity/admin controls, private response headers, authentication/error responses, and non-accumulating performance listeners. Existing authentication, outbound-policy, public discovery and image tests are also run. The authentication suite expects SESSION_LIFETIME=43200; the full regression run sets that variable for the test process only. The local .env session setting and production settings are not changed.

A final additional side-by-side local database benchmark was not executed because automatic approval review reached its usage limit. No approval bypass was attempted. Earlier successful local measurements are reported above.

No production deployment, cache-engine enablement, Redis installation or server configuration changes were performed by the agent.
