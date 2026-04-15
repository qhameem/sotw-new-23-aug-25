# Voting Procedure

## Summary

Every product starts with `1` vote by default. That first vote is a system vote, similar to how Reddit gives a new post an initial score. In addition, products automatically gain votes from view activity: every `4` impressions adds `1` vote.

## Rules

- A newly created product is assigned `votes_count = 1`.
- The system vote is not stored in `user_product_upvotes`.
- User upvotes increase `votes_count` above the system floor.
- Removing a user upvote decreases `votes_count`, but never below `1`.
- Existing products that previously had `0` votes are migrated to `1`.
- Every `4` impressions on a product add `1` automatic vote to `votes_count`.

## Examples

- Brand new product with no user upvotes: `1`
- Product with 3 user upvotes: `4`
- Product with 1 user upvote, then that user removes it: back to `1`
- Existing product with `7` views and `1` vote becomes `2` votes after backfill (`+1` for the first 4 views)
- Product with no user upvotes and `4` views: `2`
- Product with no user upvotes and `8` views: `3`

## Implementation Notes

- Database default for `products.votes_count` is `1`.
- Application-level product creation also initializes `votes_count` to `1`.
- Upvote removal logic preserves the minimum value of `1`.
- Impression recording applies the `4 views => +1 vote` rule in both product-page and impression-API flows.
- A one-time migration backfills existing products by adding `floor(impressions / 4)` votes to current `votes_count`.
