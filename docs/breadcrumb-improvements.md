# Breadcrumb Improvements

- Date: 2026-05-02
- Scope: user-facing breadcrumb UX, breadcrumb accessibility, and breadcrumb structured data consistency.

## Changes

- Replaced the breadcrumb's site-logo shortcut with a home icon in the shared component.
- Updated the shared breadcrumb component to accept both `link` and `url` keys so existing templates and generated breadcrumbs behave consistently.
- Added `aria-current="page"` to the current breadcrumb item.
- Restored slot-based breadcrumb rendering support so existing `<x-breadcrumbs.item>` usage still works.
- Upgraded product-page breadcrumbs from `Home > Product` to `Home > Category > Product` when a primary category is available.
- Added visible breadcrumbs and `BreadcrumbList` JSON-LD to the categories index page.
- Added visible breadcrumbs and `BreadcrumbList` JSON-LD to individual category pages.
- Fixed invalid breadcrumb JSON-LD on the compare page by replacing malformed `@@context` and `@@type` fields with valid schema output.
- Follow-up: corrected breadcrumb category ranking so `Software Categories` are treated as primary breadcrumb categories ahead of `Best for` categories.

## Expected Outcome

- Users get clearer back-navigation paths on important discovery pages.
- Search engines receive more consistent breadcrumb signals on product, category, and comparison pages.
- The shared breadcrumb component is more robust across manually passed items and generated items.
- Product pages now prefer true software categories like `Screenshot Tools` over audience tags like `Content Creators` in the breadcrumb trail.
