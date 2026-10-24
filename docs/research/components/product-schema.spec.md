# Product schema adaptation

Reference: https://www.scrolllaunch.com/products/seobeast, inspected 2026-10-24 via live browser DOM.
Reference JSON-LD: duplicate Organization and WebSite; WebPage with canonical URL, publisher, dates, primaryImageOfPage, mainEntity Thing, keywords, author; separate BreadcrumbList. No SoftwareApplication or product ratings for this page.

Adaptation requirements (existing Laravel records, not competitor data):
- Retain SoftwareApplication (more specific than Thing), canonical WebPage, Organization and BreadcrumbList; add WebSite relationship with stable IDs.
- Link mainEntity/mainEntityOfPage, breadcrumbs and publisher consistently.
- Existing visible FAQ only; no invented rating/reviews, founder, author, use-case, pricing, or image dimensions. Submitter is not necessarily creator; do not assert creator/author from user_id.
- Known positive price only as existing implementation; do not infer free from default zero.
- Enrich real public software properties only if backed by visible existing data. Reuse seoImageUrls for real images.
- Safe JSON encoding to prevent closing script injection from description/name/FAQ; decoded JSON must roundtrip.
- No external requests or new dependencies; no layout/route/controller changes.
- Preserve visibility guard in caller. Add meaningful schema-focused regression tests for graph links, actual FAQ, unknown price, hostile text.

Target: resources/views/products/partials/_json-ld-product.blade.php; optional service to centralize graph and tests/Feature/ProductStructuredDataTest.php. Do not edit products/show.blade.php. Run focused tests and Pint on new PHP files.
