# Product Claim Implementation

Date: 2026-05-03

## Summary

Implemented a product-claim workflow so users can request ownership of existing products and admins can approve the final assignment.

## What Was Added

1. A new `product_claims` table to store:
   - claimant
   - product
   - proof type
   - proof details
   - message to admin
   - automatic verified-email-domain match signal
   - review status and reviewer metadata

2. User claim flow:
   - `GET /product/{product:slug}/claim`
   - `POST /product/{product:slug}/claim`
   - `DELETE /product/{product:slug}/claim`

3. Admin review flow:
   - `GET /admin/product-claims`
   - `POST /admin/product-claims/{productClaim}/approve`
   - `POST /admin/product-claims/{productClaim}/reject`

4. Notifications:
   - admin gets notified when a claim is submitted
   - claimant gets notified when a claim is approved
   - claimant gets notified when a claim is rejected

## Behavior

1. Claiming requires:
   - logged-in user
   - verified email
   - completed profile

2. Users do not get edit access until admin approves the claim.

3. When a claim is approved:
   - `products.user_id` is reassigned to the claimant
   - other pending claims for the same product are automatically rejected
   - the public product page will show the approved owner as the submitter

4. Existing edit moderation remains unchanged:
   - approved product edits still go through the existing pending-edits review flow

## UI Changes

1. Public product page:
   - label changed from `Publisher` to `Submitted by`
   - added ownership/claim callout in the sidebar

2. Claim page:
   - proof type selection
   - proof details textarea
   - message to admin textarea
   - automatic verified-email-domain match explanation
   - pending-claim state with cancel action

3. Admin:
   - added a `Product Claims` moderation page
   - added navigation links to that page

## Proof Types Included

1. Verified email domain match
2. DNS TXT record
3. HTML file or meta tag
4. Official website page
5. Official social profile
6. Search Console / hosting dashboard
7. Other proof

## Files Added

1. `database/migrations/2026_05_03_120000_create_product_claims_table.php`
2. `app/Models/ProductClaim.php`
3. `app/Http/Controllers/ProductClaimController.php`
4. `app/Http/Controllers/Admin/ProductClaimController.php`
5. `app/Notifications/ProductClaimSubmitted.php`
6. `app/Notifications/ProductClaimApproved.php`
7. `app/Notifications/ProductClaimRejected.php`
8. `resources/views/products/claim.blade.php`
9. `resources/views/admin/product_claims/index.blade.php`
10. `tests/Feature/ProductClaimTest.php`

## Files Updated

1. `app/Models/Product.php`
2. `app/Models/User.php`
3. `app/Http/Controllers/ProductController.php`
4. `routes/web.php`
5. `resources/views/products/partials/_sidebar-info.blade.php`
6. `resources/views/components/user-dropdown.blade.php`
7. `resources/views/partials/_right-sidebar-usermenu.blade.php`

## Verification

Ran:

```bash
php artisan config:clear
php artisan test tests/Feature/ProductClaimTest.php
```

Result:

1. `verified user can submit a product claim`
2. `unverified user cannot submit a product claim`
3. `admin can approve claim and reassign product`

All passed.

## Notes

1. The current app does not have a separate `username` column, so the public page continues to display the user `name`.
2. Manual admin assignment is handled from `/admin/products`.
