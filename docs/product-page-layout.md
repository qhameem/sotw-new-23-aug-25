# Product page structure

Adapted from the middle column of https://www.scrolllaunch.com/products/seobeast, inspected 2026-10-24. Existing site navigation, branding, and right sidebar remain in place.

## Sections

Product header/actions → cover image → About → gallery (multiple images/video only) → Overview → Features → numbered Use cases → Submitted by (non-admin submitters only) → Details → remaining editorial content → Alternatives → Compare → Resources → FAQ → Similar products → week navigation.

Sections depend on real record content. Problem, Solution, and What makes it unique appear only when the editorial description explicitly provides those headings. The submitter is not assumed to be the maker. No discussion or review UI is invented.

`ProductPageSections` extracts recognized editorial sections. Unknown sections remain as sanitized long-form content. Heading-first descriptions retain their section labels. The facts cohort continues to hide its legacy description. Product taxonomy links use all available audience/platform/pricing records instead of the former two-item caps. Media uses existing stored URLs; the first image is the cover and gallery images can be enlarged. No new migrations, content generation, remote assets, or runtime network calls.

## Structured data

`ProductStructuredData` supplies a connected graph for Organization, WebSite, WebPage, SoftwareApplication, BreadcrumbList, and visible FAQPage when present. Canonical entity IDs connect the page and application, website, publisher, breadcrumbs, and primary image. Dates use stored timestamps. Positive prices require a valid three-letter currency; default zero never implies free. No inferred creator, ratings, reviews, or image dimensions. HEX-safe JSON prevents record text from terminating a script block.

## Verification and deployment

- Feature checks cover section extraction, retained content, sparse records, facts cohort, taxonomy completeness, cover/gallery, schema relationships, visible FAQ, unknown pricing, and script injection.
- Existing agent access, admin preview, breadcrumb, research-link, and cache-isolation tests also pass.
- Browser checks: 1440px desktop, 768px tablet, 390px mobile; no document overflow. Image enlargement and closing verified. No browser console errors in the local preview.
- Reference/adapted screenshots and extraction specifications: `docs/design-references/` and `docs/research/components/`.
- Deploy using the normal release pipeline including `npm run build` (new product-page CSS Vite entry). No migration or environment changes.
