# Stripe-Inspired Design Guide

## Purpose

This file defines the shared UI direction for Software on the Web.

We will use a Stripe-inspired product style across the project, with extra emphasis on admin and user dashboards.

This does **not** mean copying Stripe pixel-for-pixel or reusing their brand assets. It means following the same design qualities:

- calm
- precise
- premium
- data-friendly
- fast to scan
- accessibility-conscious

## Default Product Feel

Every interface should feel:

- professional instead of playful
- dense but never cramped
- polished without visual noise
- trustworthy and operational
- modern without looking trendy for its own sake

## Core Principles

### 1. Clarity first

- Prioritize comprehension over decoration.
- The user should understand page structure in a few seconds.
- Important actions must be obvious without shouting.

### 2. Strong hierarchy

- Use clear title, section, sub-section, and helper-text levels.
- Make page headers simple and disciplined.
- Let spacing and contrast create hierarchy before color does.

### 3. Restraint

- Use fewer colors, fewer shadows, and fewer decorative elements.
- Avoid loud gradients, glowing effects, oversized pills, and flashy cards in dashboard contexts.
- Visual confidence should come from alignment, spacing, typography, and consistency.

### 4. Data-first UI

- Dashboards should support scanning, filtering, comparing, and acting.
- Tables, stats, alerts, filters, and forms should feel operational.
- Information density is good when structure remains clear.

### 5. Accessibility and consistency

- Maintain strong contrast.
- Keep interaction patterns predictable.
- Reuse the same spacing, radius, border, and state rules across pages.

## Stripe-Inspired Visual Characteristics

These are the traits we should borrow most often.

### Typography

- Clean sans-serif typography.
- Strong headline clarity, but not oversized marketing-style headings inside dashboards.
- Body text should be compact, readable, and neutral.
- Use font weight changes sparingly and intentionally.

### Color

- Mostly neutral surfaces.
- White or near-white cards on soft gray page backgrounds.
- One disciplined primary accent color for actions, focus states, links, and key highlights.
- Success, warning, and danger colors should be muted and product-like, not neon.

### Spacing

- Consistent rhythm is more important than large spacing.
- Components should breathe, but dashboards should still feel information-rich.
- Use tight, repeatable spacing rules instead of custom spacing per screen.

### Surfaces

- Cards should be subtle.
- Prefer soft borders over heavy shadows.
- Use rounding, but keep it controlled and not overly pill-shaped.

### Motion

- Motion should be minimal and purposeful.
- Hover, focus, loading, and panel transitions should feel crisp and fast.
- Avoid dramatic motion in dashboard UI.

## Dashboard Rules

Whenever we build an admin panel or user dashboard, default to these rules.

### 1. Layout

- Use an application shell, not a marketing layout.
- Prefer a left sidebar for primary navigation.
- Keep the top bar slim and functional.
- Page content should start with a concise header row: title, short supporting text if needed, and actions on the right.

### 2. Navigation

- Sidebar items should be clear, short, and grouped by job-to-be-done.
- Active state should be obvious through background, border, or accent treatment.
- Avoid noisy icons everywhere. Only use icons when they improve scanning.

### 3. Cards and panels

- Use white or near-white panels on a soft neutral background.
- Panels should usually have:
  - subtle border
  - small-to-medium radius
  - minimal shadow or no shadow
- Titles inside cards should be short and operational.

### 4. Tables and lists

- Stripe-style dashboards rely heavily on structured lists and tables.
- Prefer compact rows with strong alignment.
- Make status, date, amount, owner, and actions easy to scan.
- Filters and search should sit close to the data they control.

### 5. Forms

- Labels should be explicit and always visible.
- Helper text should be short and useful.
- Validation should be calm and local to the field.
- Inputs should look lightweight, with subtle borders and clear focus rings.

### 6. Metrics and analytics

- Stat cards should be understated.
- Charts should support decisions, not act as decoration.
- Use small supporting context like trend, timeframe, or comparison.
- Keep labels legible and concise.

### 7. Buttons and actions

- One primary action per area should stand out.
- Secondary actions should be quieter.
- Destructive actions should be clear but not visually overwhelming.
- Avoid filling every action with strong color.

### Button sizing

- Default dashboard button height: `36px`
- Default dashboard button horizontal padding: `16px`
- Default dashboard button radius: `8px`
- Icon-only dashboard buttons should usually be `36px × 36px`
- Do not let button height drift from page to page in admin or user dashboards
- Use taller buttons only when there is a clear product reason, not by default

### Input sizing

- Default dashboard input and select height: `36px`
- Default dashboard input horizontal padding: `14px`
- Default dashboard input radius: `8px`
- Keep form controls compact and aligned with button height in dashboards

### 8. Empty, loading, and error states

- Empty states should be practical and instructional.
- Loading states should be lightweight and non-distracting.
- Error states should explain what happened and what the user can do next.

## Recommended UI Tokens For This Project

These are the defaults we should follow unless a page has a strong reason not to.

### Neutrals

- App background: soft gray or warm off-white
- Surface background: white
- Primary text: deep neutral
- Secondary text: muted neutral
- Borders: light neutral gray

### Accent

- Use the project primary color as the single main accent.
- Reserve the accent for:
  - primary buttons
  - active nav states
  - links
  - focus rings
  - selected filters

### Radius

- Default radius should feel modern but controlled.
- Prefer medium radii over sharp corners or fully rounded pills.
- Dashboard buttons and inputs should default to `8px` radius.

### Shadow

- Prefer almost invisible shadows.
- If a card needs separation, start with a border first.

## Implementation Rules For This Repo

The current codebase already exposes theme values through CSS variables and Tailwind configuration. We should build on that instead of inventing one-off styles.

### Primary implementation method

- Implement this design system primarily with Tailwind CSS.
- Use Tailwind utilities for layout, spacing, typography, borders, radius, shadows, states, and responsive behavior.
- Avoid building dashboard UI with large amounts of page-specific custom CSS.
- The goal is a consistent utility-first system, not handcrafted styling per page.

### Required approach

- Use shared design tokens first.
- Prefer CSS variables and Tailwind theme extensions over raw hex values.
- Keep dashboard components visually consistent between admin and user areas.
- If we introduce a new dashboard pattern once, we should reuse it elsewhere.

### When custom CSS is allowed

- Custom CSS is allowed for shared tokens and reusable primitives only.
- Prefer a very small shared layer for patterns that would otherwise repeat across many views.
- Do not use custom CSS for one-off visual experiments in dashboards.
- If a style can be expressed clearly with Tailwind utilities, prefer Tailwind.

### Good uses of custom CSS

- shared dashboard surface classes
- sidebar link states
- table shell styling
- form control primitives
- token mapping for colors, borders, radius, and shadows

### Bad uses of custom CSS

- page-specific button styling
- custom spacing tweaks for a single screen
- one-off card designs that bypass the shared system
- hard-coded hex colors when tokens already exist
- large Blade-local style blocks for dashboard UI

### Current token anchors

- `--font-family-sans`
- `--color-primary-500`
- `--color-primary-600`
- `--color-primary-700`
- `--color-primary-button-text`
- `--color-site-text`
- `--color-site-body-text`

### Future token additions we should consider

- `--color-surface`
- `--color-surface-muted`
- `--color-border-subtle`
- `--color-text-muted`
- `--color-success`
- `--color-warning`
- `--color-danger`
- `--shadow-card`
- `--radius-card`

### Suggested implementation pattern

- Tailwind handles most of the visual implementation.
- CSS variables define the design tokens.
- Tailwind theme extensions map tokens into utility classes.
- A small shared stylesheet or component layer may define reusable dashboard primitives such as:
  - `.dashboard-card`
  - `.dashboard-input`
  - `.dashboard-table`
  - `.dashboard-sidebar-link`

### Button implementation rule

- Shared dashboard button classes should define a fixed default height of `36px`.
- Do not size dashboard buttons with one-off vertical padding when the shared button class can define the height directly.

### Input implementation rule

- Shared dashboard input and select classes should define a fixed default height of `36px`.
- Do not size dashboard inputs with one-off vertical padding when the shared input class can define the height directly.

## Component Direction

### Page header

- Short title
- One-line supporting copy when needed
- Actions aligned right on desktop

### Sidebar

- Quiet background
- Clear section grouping
- Compact item height
- Strong active state

### Stat card

- Small label
- Prominent value
- Optional trend or comparison
- Minimal decoration

### Data table

- Strong column alignment
- Subtle row dividers
- Filters above
- Row actions at the far right

### Form section

- Group related inputs into cards or sections
- Keep labels, help text, and validation aligned
- Avoid long, visually noisy forms when sectioning would help

## What To Avoid

- Overusing gradients in dashboards
- Giant hero sections in authenticated areas
- Heavy shadows
- Too many accent colors
- Oversized rounded corners
- Center-aligned dashboard layouts
- Decorative icons on every control
- Card designs that look like marketing tiles instead of operational UI
- Low-contrast muted text
- Inconsistent button shapes and input styles

## Team Rule

From now on, any new admin dashboard or user dashboard should default to this Stripe-inspired system unless we explicitly decide otherwise for a specific area.

If a design decision is unclear, choose the option that is:

- simpler
- cleaner
- more structured
- easier to scan
- more consistent with a Stripe-style application shell

## Reference Notes

This guide is informed by Stripe's public dashboard and Stripe's own UI extension guidance, especially their emphasis on:

- consistent design tokens
- restrained custom styling
- accessibility
- sidebar-based operational workflows
- reusable UI patterns for data-heavy products
