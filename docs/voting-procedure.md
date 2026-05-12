# Voting Procedure

## Summary

Every product starts with `1` vote by default. That first vote is a system vote, similar to how Reddit gives a new post an initial score. In addition, products can automatically gain votes from activity during their first two weeks: every `4` impressions adds `1` vote, and every `2` outbound product link clicks adds `1` vote. After that two-week window, only manual upvote button clicks can add new votes.

## Rules

- A newly created product is assigned `votes_count = 1`.
- The system vote is not stored in `user_product_upvotes`.
- User upvotes increase `votes_count` above the system floor.
- Removing a user upvote decreases `votes_count`, but never below `1`.
- Existing products that previously had `0` votes are migrated to `1`.
- Every `4` impressions on a product add `1` automatic vote to `votes_count`, but only during the first `14` days after `published_at`.
- Every `2` tracked outbound product link clicks add `1` automatic vote to `votes_count`, but only during the first `14` days after `published_at`.
- If `published_at` is missing, the `14` day passive-auto-upvote window falls back to `created_at`.
- After the passive auto-upvote window closes, impressions and outbound clicks may still be tracked, but they no longer increase `votes_count`.
- Manual upvote button clicks can still increase `votes_count` after the passive auto-upvote window closes.

## Examples

- Brand new product with no user upvotes: `1`
- Product with 3 user upvotes: `4`
- Product with 1 user upvote, then that user removes it: back to `1`
- Existing product with `7` views and `1` vote becomes `2` votes after backfill (`+1` for the first 4 views)
- Product with no user upvotes and `4` views: `2`
- Product with no user upvotes and `8` views: `3`
- Product with no user upvotes and `2` outbound link clicks: `2`
- Product older than two weeks with `4` new views: vote total does not increase from those views
- Product older than two weeks with `2` new outbound link clicks: vote total does not increase from those clicks
- Product older than two weeks can still gain votes from a manual upvote button click

## Implementation Notes

- Database default for `products.votes_count` is `1`.
- Application-level product creation also initializes `votes_count` to `1`.
- Upvote removal logic preserves the minimum value of `1`.
- Impression recording applies the `4 views => +1 vote` rule in both product-page and impression-API flows, but only inside the first `14` days from `published_at` or `created_at`.
- Product click tracking applies the `2 outbound clicks => +1 vote` rule in both listing-card and product-details click flows, but only inside the first `14` days from `published_at` or `created_at`.
- A one-time migration backfills existing products by adding `floor(impressions / 4)` votes to current `votes_count`.
