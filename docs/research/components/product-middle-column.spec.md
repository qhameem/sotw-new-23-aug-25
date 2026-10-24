# Product middle column adaptation

Reference: https://www.scrolllaunch.com/products/seobeast. Live desktop/mobile DOM inspected 2026-10-24; full screenshots in docs/design-references/scrolllaunch-product-{desktop,mobile}.png.

## Structure and visual evidence
Reference desktop at 1440: main 737px; inner sections 673px with 32px padding. Mobile 390: main343px; sections311px with16px padding; no document overflow. Section labels12px/16px, weight600, uppercase,1.2px tracking. Body14px/22.75px; row titles15px semibold. White/near-white background, subtle gray boundaries, small colored section markers, 32px between groups. No scroll-switched panels observed. Main sections stay in document flow; gallery is horizontal.

## Adapted implementation
Preserve Software on the Web navigation/sidebars/brand/fonts and real product content/actions. Only product middle column changes. Existing Laravel/Blade/Tailwind implementation (no React scaffold). Header stays compact; one main cover screenshot before About; secondary media gallery after About, horizontal cards with existing enlargement and keyboard escape, video in gallery. No borrowed branding/assets or generated content.

Order: header/actions; cover; About; gallery; overview rows (Who is it for, explicit Problem/Solution/What makes it unique when present); Features; numbered Use cases; Submitted by (do not label submitter a maker); Details definition rows (pricing, audience, platforms, categories, tech stack, dates); remaining editorial detail; alternatives and comparison chips; resources; real FAQ; similar product rows; week navigation.

Use full taxonomy collections, no arbitrary two-item cap. Missing optional information hides section/row. Preserve unrecognized long-form editorial HTML and facts-cohort behavior; extract recognized headings without repeating content. No invented discussion/reviews, problem, solution, maker, pricing or uniqueness. Public HTML sanitized using existing OutboundLink policy. No external network calls or generation at render time.

Section containers: thin borders and consistent padding16px mobile/24px desktop. Section labels12px uppercase with1.2px tracking, colored marker and rule. Body14px with generous line-height. Overview icon tiles and numbered use-case rows; details rendered as dt/dd. Similar products simple horizontal rows, no tilted cards. Break long text and ensure flex/grid min-width0. Gallery alone scrolls horizontally, images object-contain.

Interactions: native in-page links; image enlarge by button, click outside/Close/Escape; existing Save/Visit/upvote actions and admin editing retained. FAQ native details/summary. No new auto animation or remote dependencies.

Schema independently improved in product-schema.spec.md. Verify real source-backed values, safe JSON-LD and sparse product cases. Build and desktop/mobile browser QA required.
