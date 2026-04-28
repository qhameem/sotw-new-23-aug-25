# Alternatives Page Layout Fix

## Date

- 2026-04-28

## Issues Addressed

- Removed the duplicate top page title from the alternatives page.
- Kept the main page title as the single semantic `h1` inside the content flow.
- Fixed the main alternatives container so it no longer forces a white background that clashes with the surrounding page background.
- Added a scoped layout option so this page can hide the desktop sticky page header without changing other pages.
- Removed the breadcrumb component's extra left margin so the breadcrumb aligns with the heading and content cards.
- Matched the alternatives page horizontal padding to the top navigation container so the breadcrumb, title, and cards align with the site logo on desktop.
- Centralized the horizontal padding preset for all `pseo.*` pages in `layouts/app.blade.php`, so `alternatives`, `best-of`, `best-for`, `built-with`, `compare`, and `pricing-model` stay aligned without duplicating page-level padding declarations.
- Follow-up: aligned the main product detail page (`products/show.blade.php`) to the same desktop padding as the top navigation so product breadcrumbs and content line up with the logo too.

## Files Updated

- `resources/views/layouts/app.blade.php`
- `resources/views/components/main-content-layout.blade.php`
- `resources/views/components/page-header.blade.php`
- `resources/views/pseo/alternatives.blade.php`

## Notes

- The alternatives page now uses the in-content heading as the authoritative page title.
- The mobile header still keeps the lightweight top bar behavior, but without rendering a second visible title.
- Follow-up: the shared layout uses a class-based `MainContentLayout` component, so the new `hideDesktopPageHeader` prop also had to be added to `app/View/Components/MainContentLayout.php` for the flag to take effect.

## Possible Improvement

- The `best-of` and `compare` SEO pages use a very similar layout pattern, so it may be worth reviewing them for the same duplicate-header behavior and aligning all PSEO page templates consistently.
