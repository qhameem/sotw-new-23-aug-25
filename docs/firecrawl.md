# Firecrawl fallback

Set these values in the deployment environment:

```dotenv
FIRECRAWL_ENABLED=true
FIRECRAWL_API_KEY=your-key
```

Refresh Laravel's configuration cache after changing environment values. No database migration or additional server process is required.

ProductSourceCollector retains ordinary HTTP fetching. Firecrawl is used for sparse or blocked homepage/supporting-page content, selected HTTP failures, and supporting-page connection failures. Missing supporting pages (404) do not trigger paid requests. Homepage HTTP failures reach the collector as empty HTML through the existing submission pipeline.

Each collection permits at most two fallback attempts. Requests use the hosted v2 scrape endpoint, Markdown and HTML formats, main content only, and no automatic retries. Default provider timeout: 20 seconds. Existing extraction remains synchronous; a fallback can add latency. No discovery crawler, refresh scheduler, or publishing behavior is introduced.

Successful responses are cached for 1,440 minutes with requested source URL and retrieval timestamp. Failed responses are cached for five minutes. Cache locks suppress concurrent duplicate requests. Use a shared Laravel cache store for multiple application servers. No API credentials or provider response bodies are logged.

Optional settings: FIRECRAWL_TIMEOUT (5–30 seconds), FIRECRAWL_CACHE_MINUTES, FIRECRAWL_MIN_CHARACTERS (default 300). Short content and common browser challenge phrases trigger fallback. The existing source-context budget and fact validation remain in effect. Provider failure retains ordinary content.

Verification:

```sh
vendor/bin/pest tests/Unit/FirecrawlServiceTest.php tests/Unit/ProductPageMarkdownTest.php tests/Unit/ListingGenerationTest.php
```

Live provider connectivity and content quality require a configured API key; automated tests fake HTTP responses and consume no credits.
