# Ranking System Overhaul

## Goal

Build a fairer and more trustworthy ranking system for the main time-based listing pages by separating:

- manual community upvotes
- list exposure
- detail-page views
- outbound click intent
- promoted placement

This replaces the old pattern where ranking could be affected by a blended `votes_count + impressions` signal.

## Problems In The Previous System

1. `votes_count` was not purely manual upvotes.
2. Passive engagement could turn into votes.
3. Product detail page requests increased impressions on the server request path.
4. Product prefetching could distort impression-based ranking.
5. Weekly pages were not reliable historical leaderboards because current counters could keep reshaping old weeks.
6. Promoted placement and organic ranking semantics were too close together.

## What Was Implemented In This Slice

### 1. New Metric Tables

Two new tables were added:

- `product_week_stats`
- `product_launch_stats`

These track clean ranking signals without needing to reinterpret `votes_count`.

### 2. New Metric Services

`App\Services\ProductMetricsService`

Responsibilities:

- record list impressions
- record detail views
- record outbound clicks
- record manual upvotes
- keep week-level and launch-window stats updated

`App\Services\ProductRankingService`

Responsibilities:

- compute organic weekly ordering
- run a 12-hour launch exploration phase
- calculate weekly and exploration scores

### 3. Passive Auto-Upvotes Removed

The old product helper methods no longer convert:

- views into votes
- clicks into votes

They now only maintain the legacy raw counters.

### 4. Client-Side Detail View Tracking

The product detail page no longer increments impressions during the initial server request.

Instead, the frontend sends a delayed client-side beacon for:

- `product_detail`

This avoids counting prefetches and non-visible page loads as real detail views.

### 5. Client-Side List Impression Tracking

Product cards on list pages now send a batched impression event when:

- the card is at least 60% visible
- for about 800ms

This records:

- `home_list`
- `week_list`
- `date_list`
- `month_list`
- `year_list`
- `category_list`
- generic `product_list`

### 6. Ranking Rewrite For Main Time-Based Listings

The organic ordering for:

- home
- day
- week
- month
- year

now uses `ProductRankingService` instead of the old shuffle-plus-score approach.

Promoted products are also scoped to the requested time period on these pages in this slice.

### 7. Lightweight Ranking Explanation UI

The main listing view now renders a short explanation line so the ranking semantics are easier to trust before visible numeric ranks are added.

## Current Ranking Logic

### Launch Exploration Window

For the first 12 hours after publish:

1. products are treated as being in a launch cohort
2. launch products are sorted by:
   - lowest `list_impressions`
   - highest `manual_upvotes`
   - highest `outbound_clicks`
   - earliest publish time
3. launch products are woven into the organic order every 3 slots

This is a simple fairness-first exploration model rather than pure randomness.

### Weekly Score

For mature products, the current weekly score is:

```text
manual_upvotes * 10
+ sqrt(outbound_clicks) * 4
+ sqrt(detail_views) * 1.5
+ sqrt(list_impressions) * 0.5
```

This keeps manual upvotes as the strongest signal while heavily damping exposure metrics.

### Exploration Score

The current exploration score is:

```text
((manual_upvotes * 8) + (outbound_clicks * 3) + (detail_views * 2)) / max(1, list_impressions)
```

This is stored now, even though the current launch ordering primarily uses exposure balancing first.

## Files Added

- `app/Models/ProductWeekStat.php`
- `app/Models/ProductLaunchStat.php`
- `app/Services/ProductMetricsService.php`
- `app/Services/ProductRankingService.php`
- `database/migrations/2026_05_13_120000_create_product_week_stats_table.php`
- `database/migrations/2026_05_13_120100_create_product_launch_stats_table.php`
- `docs/ranking-system-overhaul.md`

## Files Updated

- `app/Models/Product.php`
- `app/Http/Controllers/ImpressionController.php`
- `app/Http/Controllers/ProductInteractionController.php`
- `app/Http/Controllers/Api/UpvoteController.php`
- `app/Http/Controllers/ProductController.php`
- `resources/js/app.js`
- `resources/views/products/show.blade.php`
- `resources/views/partials/products_list.blade.php`
- `resources/views/partials/_product_item.blade.php`
- `resources/views/partials/_promoted_product_item.blade.php`
- `resources/views/components/admin/product-list-item.blade.php`

## Important Notes

1. `votes_count` still exists for backward compatibility and UI continuity.
2. Historical data inside `votes_count` may still contain legacy blended behavior from before this overhaul.
3. In this slice, the main time-based listings now share the same ranking model.
4. Category pages and other non-time-based surfaces may still use legacy ordering until they are migrated.

## Recommended Next Steps

1. Separate promoted placement into its own scheduling model instead of using global promotion fields.
2. Consider removing the legacy system vote floor from `votes_count` once the UI is ready for a true manual-vote count.
3. Add an end-of-week finalize step if weekly archive ranks should stop changing after the week closes.
4. Add tests for:
   - launch cohort ordering
   - weekly score ordering
   - metric recording
   - promoted slot handling
