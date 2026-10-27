# Agent route inventory

Registry-driven route coverage. Markdown responses exclude noindex variants and private records. Catalog pagination walks canonical listing pages; the full document has a 500,000-byte budget.

| Route | Renderer / data source | Markdown | Full content |
| --- | --- | --- | --- |
| `/` (home) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Yes, if indexable |
| `/about` (about) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/faq` (faq) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/newsletter` (newsletter.index) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/add-product` (products.create) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/get-the-badge` (badges.index) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/premium-spot-details` (premium-spot.details) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/software-review` (software-review) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/topics` (topics.index) | CategoryNavigationService | Public, published, indexable variants | Yes, if indexable |
| `/categories` (categories.index) | CategoryNavigationService | Public, published, indexable variants | Yes, if indexable |
| `/changelog` (changelog.index) | ChangelogController; Changelog records | Public, published, indexable variants | Yes, if indexable |
| `/tools/{todo-slug}` (todolists.index) | Static Markdown template / shared public configuration | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/tools/{toolSlug}` (launch-readiness.index) | ToolSettings; public copy; LaunchReadinessBranding/audit catalog | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/tools/{toolSlug}/{guideSlug}` (launch-readiness.guides.show) | LaunchReadinessGuideLibrary | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/tools/{toolSlug}/history` (launch-readiness.history) | LaunchReadinessController; publicly saved scans | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/articles` (articles.index) | ArticleController; ArticleDiscoveryService and published queries | Public, published, indexable variants | Yes, if indexable |
| `/articles/category/{articleCategory}` (articles.category) | ArticleController; ArticleDiscoveryService and published queries | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/articles/tag/{articleTag}` (articles.tag) | ArticleController; ArticleDiscoveryService and published queries | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/articles/{article}` (articles.show) | ArticleController; published article content and related records | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/alternatives` (alternatives.index) | AlternativesDirectoryController; ProductResearchService | Public, published, indexable variants | Yes, if indexable |
| `/product/{product}` (products.show) | ProductController::publicPageView; ProductPageSections; ProductDetailData | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/topics/{category}` (topics.category) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/category/{category}` (categories.show) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/category/{category}/page/{page}` (categories.show.page) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | No; first page included |
| `/use-case/{category}` (use-cases.show) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/use-case/{category}/page/{page}` (use-cases.show.page) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | No; first page included |
| `/best-for/{category}` (best-for.show) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/best-for/{category}/page/{page}` (best-for.show.page) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | No; first page included |
| `/platform/{category}` (platforms.show) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/platform/{category}/page/{page}` (platforms.show.page) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | No; first page included |
| `/software/{group}` (software-groups.show) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/software/{group}/page/{page}` (software-groups.page) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | No; first page included |
| `/week/{year}/{week}` (products.byWeek) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/monthly/{year}/{month}` (products.byMonth) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/yearly/{year}` (products.byYear) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/best/{category}` (pseo.best) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/best/{category}/for/{bestfor}` (pseo.bestFor) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/alternatives/{product}` (pseo.alternatives) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/built-with/{techstack}` (pseo.builtWith) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/compare/{params}` (pseo.compare) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/software/{pricing}` (pseo.pricing) | Existing controller view data; product/category/ranking/editorial services | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/collections/{owner}/{collectionSlug}` (collections.show) | ProductCollectionController; public collection/items | Public, published, indexable variants | Up to 50 candidates per route family; only eligible pages |
| `/legal` (legal) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |
| `/refund-policy` (refund-policy) | Static Markdown template / shared public configuration | Public, published, indexable variants | Yes, if indexable |

## Explicit exclusions

| Route | Reason |
| --- | --- |
| `/api/user` | API or administration endpoint. |
| `/api/notifications` | API or administration endpoint. |
| `/api/ga-stats` | API or administration endpoint. |
| `/api/analytics/total-sessions` | API or administration endpoint. |
| `/api/deferred-assets` | API or administration endpoint. |
| `/api/search` | API or administration endpoint. |
| `/api/search/defaults` | API or administration endpoint. |
| `/api/navigation/categories` | API or administration endpoint. |
| `/api/ai-model-options` | API or administration endpoint. |
| `/api/seo/pages` | API or administration endpoint. |
| `/api/seo/meta/{page_id}` | API or administration endpoint. |
| `/api/seo/generation-prompts` | API or administration endpoint. |
| `/api/get-cached-logos` | API or administration endpoint. |
| `/api/tech-stack/detect` | API or administration endpoint. |
| `/api/sidebar-search` | API or administration endpoint. |
| `/api/categories` | API or administration endpoint. |
| `/api/tech-stacks` | API or administration endpoint. |
| `/api/badge-snippet-preview` | API or administration endpoint. |
| `/api/badge-snippet/{product}` | API or administration endpoint. |
| `/up` | Health check. |
| `/product-reviews/create` | Review submission form. |
| `/search-modal-content` | UI fragment. |
| `/auth/login-modal-content` | Authentication fragment. |
| `/ads/{ad}/click` | Click tracking. |
| `/ads/{ad}/impression` | Impression tracking. |
| `/product/{product}/click` | Outbound tracking redirect. |
| `/product/{product}/og-image.jpg` | Image asset. |
| `/site.webmanifest` | Web manifest. |
| `/llms.txt` | Machine-readable endpoint. |
| `/llms-full.txt` | Machine-readable endpoint. |
| `/ai.txt` | Machine-readable endpoint. |
| `/robots.txt` | Machine-readable endpoint. |
| `/api/md/_catalog` | API or administration endpoint. |
| `/api/md/{path?}` | API or administration endpoint. |
| `/product/{slug}/document.{format}` | Machine-readable endpoint. |
| `/{key}.txt` | Ownership verification. |
| `/indexnow/{key}.txt` | Ownership verification. |
| `/thank-you` | Transaction state. |
| `/dashboard` | Authenticated content. |
| `/profile` | Authenticated content. |
| `/my/collections` | Authenticated content. |
| `/my-products` | Authenticated content. |
| `/submission-success/{product}` | Authenticated content. |
| `/products/{product}/edit` | Authenticated content. |
| `/subscribe` | Authenticated content. |
| `/articles/create` | Authenticated content. |
| `/articles/{article}/edit` | Authenticated content. |
| `/articles/{article}/preview` | Authenticated content. |
| `/my-articles` | Authenticated content. |
| `/promote/success` | Authenticated content. |
| `/stripe/paid-submission/success` | Authenticated content. |
| `/stripe/paid-submission/confirmation/{checkout}` | Authenticated content. |
| `/stripe/paid-submission/cancel/{checkout}` | Authenticated content. |
| `/product/{product}/claim` | Authenticated content. |
| `/stripe/cancel` | Checkout state. |
| `/stripe/product-review/success` | Checkout state. |
| `/admin/categories` | API or administration endpoint. |
| `/admin/categories/create` | API or administration endpoint. |
| `/admin/categories/{category}` | API or administration endpoint. |
| `/admin/categories/{category}/edit` | API or administration endpoint. |
| `/admin/errors` | API or administration endpoint. |
| `/admin/search-history` | API or administration endpoint. |
| `/admin/products/test-pending-edits` | API or administration endpoint. |
| `/admin/products/pending-edits` | API or administration endpoint. |
| `/admin/products/{product}/review-edits` | API or administration endpoint. |
| `/admin/product-claims` | API or administration endpoint. |
| `/admin/products/autocomplete` | API or administration endpoint. |
| `/admin/users-search-ajax` | API or administration endpoint. |
| `/admin/products` | API or administration endpoint. |
| `/admin/products/create` | API or administration endpoint. |
| `/admin/products/{product}` | API or administration endpoint. |
| `/admin/products/{product}/edit` | API or administration endpoint. |
| `/admin/tech-stacks` | API or administration endpoint. |
| `/admin/tech-stacks/create` | API or administration endpoint. |
| `/admin/tech-stacks/{tech_stack}` | API or administration endpoint. |
| `/admin/tech-stacks/{tech_stack}/edit` | API or administration endpoint. |
| `/admin/category-types` | API or administration endpoint. |
| `/admin/category-types/create` | API or administration endpoint. |
| `/admin/category-types/{category_type}` | API or administration endpoint. |
| `/admin/category-types/{category_type}/edit` | API or administration endpoint. |
| `/admin/advertising/detect-audience` | API or administration endpoint. |
| `/admin/advertising` | API or administration endpoint. |
| `/admin/advertising/create` | API or administration endpoint. |
| `/admin/ads` | API or administration endpoint. |
| `/admin/ads/create` | API or administration endpoint. |
| `/admin/ads/{ad}` | API or administration endpoint. |
| `/admin/ads/{ad}/edit` | API or administration endpoint. |
| `/admin/ad-zones` | API or administration endpoint. |
| `/admin/ad-zones/create` | API or administration endpoint. |
| `/admin/ad-zones/{ad_zone}` | API or administration endpoint. |
| `/admin/ad-zones/{ad_zone}/edit` | API or administration endpoint. |
| `/admin/code-snippets` | API or administration endpoint. |
| `/admin/code-snippets/create` | API or administration endpoint. |
| `/admin/code-snippets/{code_snippet}` | API or administration endpoint. |
| `/admin/code-snippets/{code_snippet}/edit` | API or administration endpoint. |
| `/admin/product-approvals` | API or administration endpoint. |
| `/admin/product-submission-drafts` | API or administration endpoint. |
| `/admin/product-approvals/{product}/preview` | API or administration endpoint. |
| `/admin/custom-category-submissions` | API or administration endpoint. |
| `/admin/theme` | API or administration endpoint. |
| `/admin/articles/posts` | API or administration endpoint. |
| `/admin/articles/posts/create` | API or administration endpoint. |
| `/admin/articles/posts/{post}/edit` | API or administration endpoint. |
| `/admin/articles/categories` | API or administration endpoint. |
| `/admin/articles/categories/create` | API or administration endpoint. |
| `/admin/articles/categories/{category}/edit` | API or administration endpoint. |
| `/admin/articles/tags` | API or administration endpoint. |
| `/admin/articles/tags/create` | API or administration endpoint. |
| `/admin/articles/tags/{tag}/edit` | API or administration endpoint. |
| `/admin/settings` | API or administration endpoint. |
| `/admin/settings/product-description-templates` | API or administration endpoint. |
| `/admin/settings/email-templates` | API or administration endpoint. |
| `/admin/settings/screenshot-providers` | API or administration endpoint. |
| `/admin/settings/ai-provider-status` | API or administration endpoint. |
| `/admin/settings/outbound-links/rules` | API or administration endpoint. |
| `/admin/settings/outbound-links/discovered` | API or administration endpoint. |
| `/admin/seo` | API or administration endpoint. |
| `/admin/users` | API or administration endpoint. |
| `/admin/users/{user}` | API or administration endpoint. |
| `/admin/premium-products` | API or administration endpoint. |
| `/admin/product-reviews` | API or administration endpoint. |
| `/admin/badges` | API or administration endpoint. |
| `/admin/badges/create` | API or administration endpoint. |
| `/admin/product-discovery` | API or administration endpoint. |
| `/api/product-meta` | API or administration endpoint. |
| `/check-product-url` | URL validation endpoint. |
| `/fetch-url-data` | Extraction endpoint. |
| `/products/dates` | JSON calendar data. |
| `/date/{date}` | Daily archive redirects to its weekly archive. |
| `/week` | Legacy or current-period redirect. |
| `/monthly` | Legacy or current-period redirect. |
| `/yearly` | Legacy or current-period redirect. |
| `/products/search` | Interactive search response. |
| `/test-notification` | Authenticated content. |
| `/articles/search` | Interactive search response. |
| `/articles/feed` | XML feed. |
| `/auth/google` | Authentication redirect. |
| `/auth/google/callback` | Authentication callback. |
| `/signup` | Authentication. |
| `/register` | Authentication. |
| `/login` | Authentication. |
| `/forgot-password` | Authentication. |
| `/reset-password/{token}` | Authentication. |
| `/auth/magic-link/{magic_link}` | Authentication token. |
| `/verify-email` | Authenticated content. |
| `/verify-email/{id}/{hash}` | Authenticated content. |
| `/confirm-password` | Authenticated content. |
| `/complete-profile` | Authenticated content. |
| `/alternatives/outrank` | Legacy or current-period redirect. |
| `/alternatives/firecrawl` | Legacy or current-period redirect. |
| `/compare/firecrawl-vs-paste-50` | Legacy or current-period redirect. |
| `/compare/firecrawl-vs-realarm` | Legacy or current-period redirect. |
| `/compare/firecrawl-vs-soapah` | Legacy or current-period redirect. |
| `/compare/formatjsononline-free-json-formatter-validator-tool-vs-firecrawl` | Legacy or current-period redirect. |
| `/compare/firecrawl-vs-paradigm` | Legacy or current-period redirect. |
| `/compare/snap-shots-vs-firecrawl` | Legacy or current-period redirect. |
| `/compare/htmlwind-vs-firecrawl` | Legacy or current-period redirect. |
| `/compare/conflictracker-vs-firecrawl` | Legacy or current-period redirect. |
| `/compare/firecrawl-vs-devicelab` | Legacy or current-period redirect. |
| `/compare/odycjs-vs-firecrawl` | Legacy or current-period redirect. |
| `/compare/firecrawl-vs-bonsai` | Legacy or current-period redirect. |
| `/{product_name}` | Legacy or current-period redirect. |
| `/fast-track` | Legacy or current-period redirect. |
| `/fast-track-approval` | Legacy or current-period redirect. |
| `/premium-spot` | User-specific product selection; controller requires a signed-in user. |
| `/premium-spot/success` | Checkout state. |
| `/admin/email-logs` | API or administration endpoint. |
| `/free-todo-list-tool/{path?}` | Legacy or current-period redirect. |
| `/tools/{toolSlug}/{todoList}/export` | User to do list export. |
| `/tools/{toolSlug}/results/{toolScan}` | Token-linked user data; never publish through discovery. |
| `/tools/{toolSlug}/login` | Tool authentication. |
| `/tools/{toolSlug}/auth/google` | Tool authentication. |
| `/tools/{toolSlug}/auth/google/callback` | Tool authentication. |
| `/tools/{toolSlug}/dashboard` | Authenticated content. |
| `/tools/{toolSlug}/settings` | Authenticated content. |
| `/broadcasting/auth` | Broadcast authorization. |
| `/storage/{path}` | Stored file delivery. |
