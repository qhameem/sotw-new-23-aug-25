# Agent access verification

2026-10-27. Isolated SQLite database: 3 product fixtures, 1 category, 1 article. HTTPS flag set in local PHP router to satisfy existing HTTPS middleware. No production database used. `/about` replaces the example `/pricing`, which this application does not define.

## Suffix headers

```text
HTTP/1.0 200 OK
Host: 127.0.0.1:3000
Connection: close
X-Powered-By: PHP/8.4.27
Content-Type: text/markdown; charset=utf-8
X-Markdown-Tokens: 753
X-Robots-Tag: noindex, follow
Content-Location: http://127.0.0.1:3000/about
Link: <http://127.0.0.1:3000/about>; rel="canonical", <http://127.0.0.1:3000/llms.txt>; rel="describedby", <http://127.0.0.1:3000/llms-full.txt>; rel="describedby", <http://127.0.0.1:3000/ai.txt>; rel="describedby"
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, HEAD, OPTIONS
Access-Control-Allow-Headers: Accept, Content-Type
Cache-Control: public, s-maxage=3600, stale-while-revalidate=86400
Vary: Accept
X-Content-Type-Options: nosniff
Date: Tue, 27 Oct 2026 04:39:32 GMT
```

## Negotiated Markdown

```text
# About

> About the directory and its curation.

Canonical: http://127.0.0.1:3000/about

## Discover Better Digital Tools, Every Day

**Software On The Web** is a curated platform for discovering standout digital products— built for makers, seekers, and anyone passionate about innovative software. We handpick tools through weekly curation, combining editorial discoveries with products submitted by makers.

We’re not a firehose of launches. We’re a growing library of quality.

## Our Mission

Our mission is simple: **to connect people with the most useful, inspiring, and well-made digital products on the web**. We help people discover software that solves real problems. No noise. Just the tools that matter.

## Quality Over Quantity

Our team manually reviews every tool, including submissions automatically approved through badge verification or paid checkout. Automatic approval reserves a launch; our team may still withhold or remove a product that is unsuitable.

We handpick software for its purpose, usefulness, and quality as part of our weekly curation.

## A Lasting Collection

Listings are permanent, subject to suitability review and our Terms of Use. For badge listings, the badge must remain live on your website. If it is removed, our system automatically removes the listing from public view. Paid listings have no badge requirement.

## Built for Creators and Discoverers

**Software On The Web** serves two communities:
```

## Ordinary HTML headers

```text
HTTP/1.1 200 OK
Host: 127.0.0.1:3000
Connection: close
X-Powered-By: PHP/8.4.27
Content-Type: text/html; charset=UTF-8
Cache-Control: max-age=0, must-revalidate, no-cache, no-store, private
Date: Tue, 27 Oct 2026 04:39:32 GMT
Server-Timing: app;dur=35.0, db;dur=11.5;desc="5 queries"
X-LiteSpeed-Cache-Control: no-cache
Vary: Cookie
Vary: Authorization
Vary: Accept
Expires: Thu, 01 Jan 1970 00:00:00 GMT
Link: <http://127.0.0.1:3000/llms.txt>; rel="describedby", <http://127.0.0.1:3000/llms-full.txt>; rel="describedby", <http://127.0.0.1:3000/ai.txt>; rel="describedby"
Set-Cookie: XSRF-TOKEN=eyJpdiI6ImlNQU1hNkx4WFBrblZJeWNCelZZZEE9PSIsInZhbHVlIjoiOW9OdHJTRmh6em02Zm4wS2VUd1pTY0phM21zdVZGOWZSZlBMNm1yVTBMMjhXS1NRek1hMGw5Y0MwUXdYeERqY01jQmRUbnZZa0pLOUt0Q0tTVFVidXVXYXc0YVFVL1A2Z1YwRHBoV205WDc0NTdId25uQlpuN2pvdnB5M1JKUWIiLCJtYWMiOiIzMGVhMTVhYjg0YzU5NThhNTMzNzkxNDA2NDRhYmU3MGNkZmExZjVhNThkNzZhYmVmMjYwNGVhOTZlNmI0ZjFkIiwidGFnIjoiIn0%3D; expires=Tue, 27 Oct 2026 06:39:32 GMT; Max-Age=7200; path=/; domain=software-on-the-web-lara-new.test; samesite=lax
Set-Cookie: laravel_session=eyJpdiI6ImFhZGl4OWdpQVpaT09lSXNUSHk2NUE9PSIsInZhbHVlIjoiR0dzSm9ERjZzaHpxaDRnTGFWS1FETndwWjhBNlVaV25zUSs2R0lhUEFmUDJCSTBuSDUxeEloSGlKVkliVXNLcTV5ZTJqNkZhRzNzd2JiZ0psVlZSbmF1cXNYcXAyT0xvWTBpYzJQWllleklkSSttNVFVQVV5ZHdYbThQRFZvMXYiLCJtYWMiOiJmYzJhZjY0OTI3ZmI2YjRjODIzZDRjNzIxOGY0ZTk4NzgwYmFlNzMzZDBmMjFjMjlhOWYzN2RkZjUzMWIxYzkxIiwidGFnIjoiIn0%3D; expires=Tue, 27 Oct 2026 06:39:32 GMT; Max-Age=7200; path=/; domain=software-on-the-web-lara-new.test; httponly; samesite=lax
```

## Wildcard homepage headers

```text
HTTP/1.1 200 OK
Host: 127.0.0.1:3000
Connection: close
X-Powered-By: PHP/8.4.27
Content-Type: text/html; charset=UTF-8
Cache-Control: max-age=0, must-revalidate, no-cache, no-store, private
Date: Tue, 27 Oct 2026 04:39:32 GMT
Server-Timing: app;dur=26.3, db;dur=5.9;desc="13 queries"
X-LiteSpeed-Cache-Control: no-cache
Vary: Cookie
Vary: Authorization
Vary: Accept
Expires: Thu, 01 Jan 1970 00:00:00 GMT
Link: <http://127.0.0.1:3000/llms.txt>; rel="describedby", <http://127.0.0.1:3000/llms-full.txt>; rel="describedby", <http://127.0.0.1:3000/ai.txt>; rel="describedby"
Set-Cookie: XSRF-TOKEN=eyJpdiI6ImpYYm0xTXJqTWptSnRyYmltMUV5Rnc9PSIsInZhbHVlIjoiaDdoUTZJWTl1SlNQeGVOSTFueGNvcGl5MWIvN2lGTUtBalpVVVpxYjNRL3g0a25NNURsT2NDcisrUTQzODhKKytSUW56OGZWTndzMEVtbFZJMkRZWVN3UG96MURydEhaMFJHbFpuZmo4VWZTLzF3RGU0d1VvN3BSVnZyZ04wdUYiLCJtYWMiOiJhNzQ3MjY5ODY1Y2IzMmVmNmI1Mjc2ZjA3ZmZhZmZkMGFlOTM4OTcxMDljOGRlZDVkZmU5MDJhNmI2YmQ1ODBkIiwidGFnIjoiIn0%3D; expires=Tue, 27 Oct 2026 06:39:32 GMT; Max-Age=7200; path=/; domain=software-on-the-web-lara-new.test; samesite=lax
Set-Cookie: laravel_session=eyJpdiI6IktyTEFoT0M1YmJUQTluWHlUNW1NRnc9PSIsInZhbHVlIjoiQXBYZWhVenBsSkpoa2J4MHF4VVJOcTVBajlRa255QUR0OG1OUGRqQTRBdmNPZmducUs5TG9xbFBMV2sxb2JZU0QvUnhYcG5tbk5HR1lKcVptVUlNODJHUUJxS3NQODlnWjBidHBYQjYvVlIwVjdBVlZ4ZHovT0FKaU9tb2l0alYiLCJtYWMiOiI0ZDU3MTczM2M2NzQ2ZjBmYzUyYjNkZWQ4NDNkZTI2NzliNWUzZjUxZTIyMjkyYTEwNGM2NTc0ZmU2ZTEzOWZjIiwidGFnIjoiIn0%3D; expires=Tue, 27 Oct 2026 06:39:32 GMT; Max-Age=7200; path=/; domain=software-on-the-web-lara-new.test; httponly; samesite=lax
```

## Missing Markdown

```text
# Not found

> No public markdown document is available.

Canonical: http://127.0.0.1:3000/does-not-exist



HTTP status: 404
```

## Discovery index

```text
# Laravel

> A curated directory for discovering software, product information, alternatives, and comparisons.

Browsing is free. Makers can submit products using badge or paid launch options. Contact: hello@softwareontheweb.com.

<!-- keep in sync with robots.txt and ai.txt -->

Dynamic route families are limited to 50 candidates each; use the catalog and listing pagination for the rest.

## Core pages
- [Best of Week 43 of 2026 \| Laravel](http://127.0.0.1:3000/index.md): Discover new SaaS tools and software launches every week. Browse curated products across AI, productivity, design, and developer tools on Software on the Web.
- [About](http://127.0.0.1:3000/about.md): About the directory and its curation.
- [FAQ](http://127.0.0.1:3000/faq.md): Answers about discovery and product submissions.
- [Newsletter](http://127.0.0.1:3000/newsletter.md): Curated software discoveries by email.
- [Submit a product](http://127.0.0.1:3000/add-product.md): Product submission and launch conditions.
- [Badge instructions](http://127.0.0.1:3000/get-the-badge.md): How to install and verify a listing badge.
- [Premium Spot details](http://127.0.0.1:3000/premium-spot-details.md): Premium placement terms and features.
- [Software reviews](http://127.0.0.1:3000/software-review.md): Information about the software review service.
- [Topics](http://127.0.0.1:3000/topics.md): Browse software topics, use cases, audiences, and platforms.
- [Topics](http://127.0.0.1:3000/categories.md): Browse software topics, use cases, audiences, and platforms.
- [Changelog](http://127.0.0.1:3000/changelog.md): Product updates and releases.

## Docs / Guides
- [To do list](http://127.0.0.1:3000/tools/todo-list-app.md): Free to do list features and instructions.
- [Is your site ready for launch?](http://127.0.0.1:3000/tools/launch-readiness-checker.md): Use this free launch readiness checker to audit your site before launch. Check SEO, trust, technical issues, and AI visibility in minutes.
- [Title Tag: Write Search Titles That Explain the Page Fast](http://127.0.0.1:3000/tools/launch-readiness-checker/title-tag.md): Learn what a title tag does, why it matters, and how to write cleaner titles before launching a page.
- [Meta Description: Write Better Snippets for Search Results](http://127.0.0.1:3000/tools/launch-readiness-checker/meta-description.md): Understand meta descriptions, snippet length, and how to write clearer search-result copy before launch.
- [Canonical URL: Tell Crawlers Which Version Should Count](http://127.0.0.1:3000/tools/launch-readiness-checker/canonical-url.md): Learn when to add canonical tags, what they signal, and how they reduce duplicate URL confusion.
- [Favicon: Give the Site a Recognizable Browser Identity](http://127.0.0.1:3000/tools/launch-readiness-checker/favicon.md): Why favicons matter, how to declare them, and what to ship before a website launch.
- [Viewport Meta: Tell Mobile Browsers How to Size the Page](http://127.0.0.1:3000/tools/launch-readiness-checker/viewport-meta.md): Understand the viewport meta tag and why responsive pages need it before launch.
- [HTML Lang: Declare the Primary Language of the Document](http://127.0.0.1:3000/tools/launch-readiness-checker/html-lang.md): Why the HTML lang attribute matters for accessibility, translation, and page interpretation.
- [H1 Tag: Give the Page One Clear Main Heading](http://127.0.0.1:3000/tools/launch-readiness-checker/h1-tag.md): What an H1 does, how many to use, and how to write one that clarifies the page instantly.
- [Heading Hierarchy: Structure the Page So It Scans Cleanly](http://127.0.0.1:3000/tools/launch-readiness-checker/heading-hierarchy.md): Learn how H1, H2, and H3 structure helps users, crawlers, and accessibility tools understand a page.
- [Image Alt Text: Describe Meaningful Images Without Noise](http://127.0.0.1:3000/tools/launch-readiness-checker/image-alt-text.md): Why alt text matters, when to leave it empty, and how to write better descriptions for meaningful images.
- [Page Status: Make Sure the URL Actually Loads Cleanly](http://127.0.0.1:3000/tools/launch-readiness-checker/page-status.md): Understand why a successful HTTP response matters and what to verify before launching a public page.
- [HTTPS: Serve the Final Page Over an Encrypted Connection](http://127.0.0.1:3000/tools/launch-readiness-checker/https.md): Why HTTPS matters for trust, security, and launch readiness, plus what to verify on the final URL.
- [HTTPS Redirect: Push Old HTTP Requests to the Secure Version](http://127.0.0.1:3000/tools/launch-readiness-checker/https-redirect.md): Learn why HTTP-to-HTTPS redirects matter and how to keep one clean public version of every page.
- [Indexability: Do Not Accidentally Hide the Page From Search](http://127.0.0.1:3000/tools/launch-readiness-checker/indexability.md): Understand noindex signals, robots directives, and when a page is accidentally blocking itself from discovery.
- [Security Headers: Add a Basic Response Hardening Layer](http://127.0.0.1:3000/tools/launch-readiness-checker/security-headers.md): What common security headers do and why launch pages should ship with a basic hardening baseline.
```

## Full content size

```text
97179 bytes
```

## AI policy

```text
# ai.txt - v1.1.1
# keep in sync with robots.txt and llms.txt

[identity]
name: Laravel
url: http://127.0.0.1:3000
description: A curated directory for discovering software, product information, alternatives, and comparisons.
contact: hello@softwareontheweb.com

[permissions]
allowed: summarise, quote with attribution, cite, index for search and answers

[restrictions]
no fabricated quotes
no fake reviews or ratings
no presenting our content as the model's own
no training on private user-generated data

[attribution]
cite: Laravel + canonical HTML URL

[scope]
included: public, published, indexable pages listed in http://127.0.0.1:3000/api/md/_catalog
excluded: /admin, /api, /dashboard, /profile, /my, /my-products, /my-articles, /auth, /login, /signup, /register, /forgot-password, /reset-password, /complete-profile, /confirm-password, /verify-email, /email, /logout, /stripe, /subscribe, /submission-success, /promote, /thank-you, /premium-spot, /product-reviews, /test-notification, /products/*/edit, /product/*/claim, /articles/create, /articles/*/edit, /articles/*/preview, /tools/*/results, /tools/*/dashboard, /tools/*/settings, /tools/*/login, /tools/*/auth, /tools/*/*/export

[contact]
email: hello@softwareontheweb.com

Last updated: 2026-10-27
```

## Catalog

```text
{"items":[{"title":"Best of Week 43 of 2026 | Laravel","description":"Discover new SaaS tools and software launches every week. Browse curated products across AI, productivity, design, and developer tools on Software on the Web.","canonical":"http://127.0.0.1:3000/","markdown":"http://127.0.0.1:3000/index.md"},{"title":"About","description":"About the directory and its curation.","canonical":"http://127.0.0.1:3000/about","markdown":"http://127.0.0.1:3000/about.md"},{"title":"FAQ","description":"Answers about discovery and product submissions.","canonical":"http://127.0.0.1:3000/faq","markdown":"http://127.0.0.1:3000/faq.md"}],"next":"http://127.0.0.1:3000/api/md/_catalog?offset=3&limit=3"}
```

## Robots

```text
# keep in sync with llms.txt and ai.txt
# Agent discovery: http://127.0.0.1:3000/llms.txt
# AI usage policy: http://127.0.0.1:3000/ai.txt

User-agent: AhrefsBot
Disallow: /

User-agent: SemrushBot
Disallow: /

User-agent: SERankingBacklinksBot
Disallow: /

User-agent: Bytespider
Disallow: /

User-agent: TikTokSpider
Disallow: /

User-agent: OAI-SearchBot
Disallow: /admin
Disallow: /api
Disallow: /dashboard
Disallow: /profile
Disallow: /my
Disallow: /my-products
Disallow: /my-articles
Disallow: /auth
Disallow: /login
Disallow: /signup
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /complete-profile
Disallow: /confirm-password
Disallow: /verify-email
Disallow: /email
Disallow: /logout
Disallow: /stripe
Disallow: /subscribe
Disallow: /submission-success
Disallow: /promote
Disallow: /thank-you
Disallow: /premium-spot
Disallow: /product-reviews
Disallow: /test-notification
Disallow: /products/*/edit
Disallow: /product/*/claim
Disallow: /articles/create
Disallow: /articles/*/edit
Disallow: /articles/*/preview
Disallow: /tools/*/results
Disallow: /tools/*/dashboard
Disallow: /tools/*/settings
Disallow: /tools/*/login
Disallow: /tools/*/auth
Disallow: /tools/*/*/export
Allow: /api/md/

User-agent: ChatGPT-User
Disallow: /admin
Disallow: /api
Disallow: /dashboard
Disallow: /profile
Disallow: /my
Disallow: /my-products
Disallow: /my-articles
Disallow: /auth
Disallow: /login
Disallow: /signup
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /complete-profile
Disallow: /confirm-password
Disallow: /verify-email
Disallow: /email
Disallow: /logout
Disallow: /stripe
Disallow: /subscribe
Disallow: /submission-success
Disallow: /promote
Disallow: /thank-you
Disallow: /premium-spot
Disallow: /product-reviews
Disallow: /test-notification
Disallow: /products/*/edit
Disallow: /product/*/claim
Disallow: /articles/create
Disallow: /articles/*/edit
Disallow: /articles/*/preview
Disallow: /tools/*/results
Disallow: /tools/*/dashboard
Disallow: /tools/*/settings
Disallow: /tools/*/login
Disallow: /tools/*/auth
Disallow: /tools/*/*/export
Allow: /api/md/

User-agent: Claude-SearchBot
Disallow: /admin
Disallow: /api
Disallow: /dashboard
Disallow: /profile
Disallow: /my
Disallow: /my-products
Disallow: /my-articles
Disallow: /auth
Disallow: /login
Disallow: /signup
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /complete-profile
Disallow: /confirm-password
Disallow: /verify-email
Disallow: /email
Disallow: /logout
Disallow: /stripe
Disallow: /subscribe
Disallow: /submission-success
Disallow: /promote
Disallow: /thank-you
Disallow: /premium-spot
Disallow: /product-reviews
Disallow: /test-notification
Disallow: /products/*/edit
Disallow: /product/*/claim
Disallow: /articles/create
Disallow: /articles/*/edit
Disallow: /articles/*/preview
Disallow: /tools/*/results
Disallow: /tools/*/dashboard
Disallow: /tools/*/settings
Disallow: /tools/*/login
Disallow: /tools/*/auth
Disallow: /tools/*/*/export
Allow: /api/md/

User-agent: Claude-User
Disallow: /admin
Disallow: /api
Disallow: /dashboard
Disallow: /profile
Disallow: /my
Disallow: /my-products
Disallow: /my-articles
Disallow: /auth
Disallow: /login
Disallow: /signup
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /complete-profile
Disallow: /confirm-password
Disallow: /verify-email
Disallow: /email
Disallow: /logout
Disallow: /stripe
Disallow: /subscribe
Disallow: /submission-success
Disallow: /promote
Disallow: /thank-you
Disallow: /premium-spot
Disallow: /product-reviews
Disallow: /test-notification
Disallow: /products/*/edit
Disallow: /product/*/claim
Disallow: /articles/create
Disallow: /articles/*/edit
Disallow: /articles/*/preview
Disallow: /tools/*/results
Disallow: /tools/*/dashboard
Disallow: /tools/*/settings
Disallow: /tools/*/login
Disallow: /tools/*/auth
Disallow: /tools/*/*/export
Allow: /api/md/

User-agent: PerplexityBot
Disallow: /admin
Disallow: /api
Disallow: /dashboard
Disallow: /profile
Disallow: /my
Disallow: /my-products
Disallow: /my-articles
Disallow: /auth
Disallow: /login
Disallow: /signup
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /complete-profile
Disallow: /confirm-password
Disallow: /verify-email
Disallow: /email
Disallow: /logout
Disallow: /stripe
Disallow: /subscribe
Disallow: /submission-success
Disallow: /promote
Disallow: /thank-you
Disallow: /premium-spot
Disallow: /product-reviews
Disallow: /test-notification
Disallow: /products/*/edit
Disallow: /product/*/claim
Disallow: /articles/create
Disallow: /articles/*/edit
Disallow: /articles/*/preview
Disallow: /tools/*/results
Disallow: /tools/*/dashboard
Disallow: /tools/*/settings
Disallow: /tools/*/login
Disallow: /tools/*/auth
Disallow: /tools/*/*/export
Allow: /api/md/

User-agent: Perplexity-User
Disallow: /admin
Disallow: /api
Disallow: /dashboard
Disallow: /profile
Disallow: /my
Disallow: /my-products
Disallow: /my-articles
Disallow: /auth
Disallow: /login
Disallow: /signup
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /complete-profile
Disallow: /confirm-password
Disallow: /verify-email
Disallow: /email
Disallow: /logout
Disallow: /stripe
Disallow: /subscribe
Disallow: /submission-success
Disallow: /promote
Disallow: /thank-you
Disallow: /premium-spot
Disallow: /product-reviews
Disallow: /test-notification
Disallow: /products/*/edit
Disallow: /product/*/claim
Disallow: /articles/create
Disallow: /articles/*/edit
Disallow: /articles/*/preview
Disallow: /tools/*/results
Disallow: /tools/*/dashboard
Disallow: /tools/*/settings
Disallow: /tools/*/login
Disallow: /tools/*/auth
Disallow: /tools/*/*/export
Allow: /api/md/

User-agent: *
Disallow: /admin
Disallow: /api
Disallow: /dashboard
Disallow: /profile
Disallow: /my
Disallow: /my-products
Disallow: /my-articles
Disallow: /auth
Disallow: /login
Disallow: /signup
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /complete-profile
Disallow: /confirm-password
Disallow: /verify-email
Disallow: /email
Disallow: /logout
Disallow: /stripe
Disallow: /subscribe
Disallow: /submission-success
Disallow: /promote
Disallow: /thank-you
Disallow: /premium-spot
Disallow: /product-reviews
Disallow: /test-notification
Disallow: /products/*/edit
Disallow: /product/*/claim
Disallow: /articles/create
Disallow: /articles/*/edit
Disallow: /articles/*/preview
Disallow: /tools/*/results
Disallow: /tools/*/dashboard
Disallow: /tools/*/settings
Disallow: /tools/*/login
Disallow: /tools/*/auth
Disallow: /tools/*/*/export
Allow: /api/md/

Sitemap: http://127.0.0.1:3000/sitemap.xml
```

## Homepage signals

```text
<meta name="ai-summary" content="Find new SaaS tools every week. Browse curated picks across AI, productivity, developer tools, and design software on Software on the Web.">
JSON-LD script count: 3
```

## Product

```text
# Agent test product

> Agent test product: Sint fuga tempore rem alias et officiis enim. Best for Agent test category.

Canonical: http://127.0.0.1:3000/product/agent-test-product
Last updated: 2026-10-27

## About


## Features

- Verified fixture content.

## Submitted by

Adalberto Rogahn

## Details

| Field | Value |
| --- | --- |
| Platforms | Web |
| Categories | Agent test category |
```

## Article

```text
# Agent test article

> DetailsVerified article fixture.

Canonical: http://127.0.0.1:3000/articles/agent-test-article
Last updated: 2026-10-27

DetailsVerified article fixture.

## Details

Verified article fixture.

## Related
- [Home](http://127.0.0.1:3000/)
```

## Category

```text
# Agent test category Software

> Explore Agent test category software, compare published products, and find the right option for your needs.

Canonical: http://127.0.0.1:3000/category/agent-test-category

Explore Agent test category software, compare published products, and find the right option for your needs.

- [Sanford, Stanton and Armstrong](http://127.0.0.1:3000/product/optio-neque-natus-fuga-sit-dolore-eius-et) - Et asperiores libero voluptas.
- [Schumm, Hauck and Wiegand](http://127.0.0.1:3000/product/labore-repudiandae-dolorum-quos-vel-iste-nobis-aspernatur-illo) - Sapiente quia deserunt dolorem enim qui dolor.
- [Agent test product](http://127.0.0.1:3000/product/agent-test-product) - Sint fuga tempore rem alias et officiis enim.

Page 1

## Related
- [Home](http://127.0.0.1:3000/)
- [Topics](http://127.0.0.1:3000/topics)
```

## Private path

```text
# Not found

> No public markdown document is available.

Canonical: http://127.0.0.1:3000/admin/products



HTTP status: 404
```

## HEAD

```text
HTTP/1.0 200 OK
Host: 127.0.0.1:3000
Connection: close
X-Powered-By: PHP/8.4.27
Content-Type: text/markdown; charset=utf-8
X-Markdown-Tokens: 753
X-Robots-Tag: noindex, follow
Content-Location: http://127.0.0.1:3000/about
Link: <http://127.0.0.1:3000/about>; rel="canonical", <http://127.0.0.1:3000/llms.txt>; rel="describedby", <http://127.0.0.1:3000/llms-full.txt>; rel="describedby", <http://127.0.0.1:3000/ai.txt>; rel="describedby"
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, HEAD, OPTIONS
Access-Control-Allow-Headers: Accept, Content-Type
Cache-Control: public, s-maxage=3600, stale-while-revalidate=86400
Vary: Accept
X-Content-Type-Options: nosniff
Date: Tue, 27 Oct 2026 04:39:35 GMT
```

## Preflight

```text
HTTP/1.0 204 No Content
Host: 127.0.0.1:3000
Connection: close
X-Powered-By: PHP/8.4.27
Content-Type: text/markdown; charset=utf-8
X-Markdown-Tokens: 753
X-Robots-Tag: noindex, follow
Content-Location: http://127.0.0.1:3000/about
Link: <http://127.0.0.1:3000/about>; rel="canonical", <http://127.0.0.1:3000/llms.txt>; rel="describedby", <http://127.0.0.1:3000/llms-full.txt>; rel="describedby", <http://127.0.0.1:3000/ai.txt>; rel="describedby"
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, HEAD, OPTIONS
Access-Control-Allow-Headers: Accept, Content-Type
Cache-Control: public, s-maxage=3600, stale-while-revalidate=86400
Vary: Accept
X-Content-Type-Options: nosniff
Date: Tue, 27 Oct 2026 04:39:35 GMT
```
