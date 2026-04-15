# Product Comparison and Alternatives System

This document explains how product-to-product comparisons and alternatives are generated, how admins can curate them manually, and how SEO quality is protected.

## Overview

The system is hybrid:

1. Manual curation comes first (if present).
2. Algorithmic matching fills remaining slots.
3. Low-confidence pages are marked `noindex,follow`.

The core logic lives in:

- `app/Services/RelatedProductService.php`

The main consumers are:

- Product page sidebar: `app/Http/Controllers/ProductController.php`
- Alternatives page: `app/Http/Controllers/PseoController.php`
- Compare page validation/noindex: `app/Http/Controllers/PseoController.php`
- Compare URL generation for sitemap: `app/Console/Commands/GenerateSitemap.php`

## Data Model

Two JSON columns are used on `products`:

- `comparison_product_ids`
- `alternative_product_ids`

Added by migration:

- `database/migrations/2026_04_14_130000_add_related_product_overrides_to_products_table.php`

`Product` model casts:

- `comparison_product_ids` => `array`
- `alternative_product_ids` => `array`

## Matching Signals and Score

`RelatedProductService::calculateMatch()` scores candidates using multiple signals:

- Shared software categories (primary)
- Shared "Best for" categories
- Shared pricing categories
- Shared tech stacks
- Text similarity (Jaccard over tokens from name/tagline/page tagline/description)
- Broad-category penalty
- Small popularity bonus

### Score details (current weights)

- Shared software categories:
  - `42 + 16 * (extra shared software categories)`, max `70`
- Shared best-for:
  - `8` each, max `20`
- Shared pricing:
  - `5` each, max `10`
- Shared tech stack:
  - `12` each, max `24`
- Text similarity:
  - `round(jaccard * 40)`
- Broad category penalty:
  - `-10` each broad shared software category, max `-20`
- Popularity bonus:
  - up to `+6` via `log10(votes + impressions + 1)`

Broad categories currently penalized:

- `ai & machine learning`
- `developer tools`
- `design`
- `marketing`
- `productivity`
- `saas`

## Qualification Rules

### Comparison qualification

A pair qualifies for comparison if all are true:

- Not a broad-only overlap case
- Score `>= 60`
- And:
  - shared software categories `>= 2`, or
  - shared software categories `>= 1` plus one of:
    - text similarity `>= 0.14`
    - shared tech stack `>= 1`
    - shared best-for `>= 1`

### Alternative qualification

A candidate qualifies as an alternative if all are true:

- Not a broad-only overlap case
- Score `>= 42`
- And one of:
  - shared software categories `>= 1`
  - shared tech stack `>= 1`
  - text similarity `>= 0.16`

## Manual Overrides (Admin)

Manual overrides are always prioritized above algorithmic matches.

### Where admins edit

- Go to `/admin/products/{id}/edit`
- In **Additional Info** -> admin **Save Changes** card:
  - `Curated Comparisons`
  - `Curated Alternatives`

Frontend file:

- `resources/js/components/product-submit/LaunchChecklistForm.vue`

### Allowed input formats

Each field accepts comma/newline/whitespace-separated tokens:

- Product ID (`123`)
- Product slug (`ai-agent-flow`)
- Full URL (`https://softwareontheweb.com/product/ai-agent-flow`)

### Parsing behavior

Parsing is done in:

- `App\Http\Controllers\Admin\ProductController::resolveRelatedProductOverrides()`

Rules:

- Unknown tokens are ignored.
- The current product itself is removed if entered.
- Duplicates are removed.
- Input order is preserved.
- Maximum stored entries: `30`.

## Runtime Behavior

### Product sidebar (`/product/{slug}`)

- `ProductController::showProductPage()` uses:
  - `RelatedProductService::getComparisons($product, 3)`
- Sidebar view displays up to 2 links and a short match reason.

### Alternatives page (`/alternatives/{product}`)

- Uses `getAlternatives($product, 15)`
- Adds short reason text per result.
- `robots` set to `noindex,follow` when:
  - fewer than 3 alternatives, or
  - no manual alternatives and top score `< 55`

### Compare page (`/compare/{a}-vs-{b}`)

- Pair is rescored with `scorePair()`
- If pair is not curated and does not qualify as comparison:
  - `robots` is `noindex,follow`

## SEO and Sitemap Guardrails

Sitemap compare generation now uses qualified comparisons only:

- `GenerateSitemap` calls `getComparisons($product, 3)`

This avoids indexing low-quality, weakly related comparison URLs.

## Taxonomy Compatibility

Software-type checks accept both historical and current naming:

- `Software`
- `Software Categories`
- `Category`

This is handled in:

- `RelatedProductService::SOFTWARE_TYPE_NAMES`
- `PseoController::hasSoftwareType()`

## Operational Notes

1. Run migrations before using manual overrides:
   - `php artisan migrate`
2. If you change weights/thresholds, review:
   - Sidebar relevance quality
   - Alternatives page `noindex` rate
   - Number of generated compare URLs in sitemap

