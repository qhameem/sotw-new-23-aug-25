# Add Product Screenshot Reliability Changes

- Date: 2026-04-27
- Route affected: `/add-product`
- Goal: replace brittle third-party screenshot fetching with a more reliable free approach that runs inside the app stack when possible.

## Findings

- The add-product autofill flow was using `thum.io` directly inside `ScreenshotService`.
- The product media fallback job was using a Microlink screenshot API URL instead of generating and storing a local screenshot file through the shared screenshot service.
- The repository already included `spatie/browsershot` and `puppeteer`, which made a local programmatic solution viable without adding paid infrastructure.

## Changes Made

- Rebuilt `app/Services/ScreenshotService.php`.
- New primary path: local screenshot capture with `Browsershot` + bundled `puppeteer`.
- Added reliability settings:
  - wait for `body`
  - wait for near-network-idle
  - short post-load delay
  - headless mode
  - HTTPS-ignore support
  - dialog dismissal
  - no-sandbox mode
- Kept `thum.io` only as a last-resort fallback.
- Improved the fallback request by using the encoded `?url=` form and validating that the response is actually an image before saving it.
- Added `captureToStorage()` so both preview screenshots and background jobs use the same storage-oriented capture path.
- Updated `app/Jobs/FetchOgImage.php` so screenshot fallback now stores a real local media file through `ScreenshotService`.
- Added relative `og:image` URL resolution in `FetchOgImage`.
- Added a focused unit test at `tests/Unit/FetchOgImageTest.php` for the new screenshot fallback behavior.

## Why This Should Be More Reliable

- It removes dependency on a third-party screenshot API as the main path.
- It uses the project’s already-installed headless browser stack, so rendering behavior is closer to a real browser.
- It still keeps a remote fallback in place for environments where local browser capture fails.
- It centralizes screenshot logic, which reduces drift between the submission preview path and the product-media fallback path.

## Verification Notes

- Confirmed the repo already contains:
  - `spatie/browsershot` in `composer.json`
  - `puppeteer` in `package.json`
- Confirmed Puppeteer resolves a local Chrome-for-Testing executable on this machine.
- Targeted PHPUnit verification was added for fallback record creation and no-op behavior on capture failure.

## Source Notes

- Browsershot docs: https://spatie.be/docs/browsershot/v4/usage/creating-images
- Puppeteer screenshot docs: https://pptr.dev/guides/screenshots
- Puppeteer network idle docs: https://pptr.dev/api/puppeteer.page.waitfornetworkidle
