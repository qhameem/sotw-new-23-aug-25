# UI Style Guide

## Purpose

- This file is the single source of truth for shared UI styling guidance in this project.
- All new UI elements should follow this guide.
- Existing UI elements mentioned for redesign should also be updated to follow this guide.
- Styling should continue to use Tailwind CSS.
- This file defines *how* common UI elements should look and behave.

## Usage Rules

- Prefer shared visual patterns over one-off styling.
- Reuse existing Tailwind utility combinations where possible.
- When a new common pattern appears, add it to this file before reusing it widely.
- Avoid ad hoc spacing, radius, shadow, and color decisions in individual components.
- If a page needs a special treatment, define the base shared rule here first, then note page-specific exceptions.
- Do not use gradient backgrounds unless explicitly requested.

## Global Design Direction

### Brand Feel

- Visual style:
- Tone:
- Density:
- Overall contrast:
- Preferred look:
- Avoid:
- Gradient backgrounds unless explicitly requested

### Core Principles

- Consistency:
- Clarity:
- Scalability:
- Accessibility:
- Mobile behavior:

## Design Tokens

### Colors

- Primary:
- Secondary:
- Accent:
- Success:
- Warning:
- Danger:
- Neutral background:
- Neutral border:
- Neutral text:
- Muted text:

### Typography

- Primary font:
- Secondary font:
- Heading style:
- Body text style:
- Label style:
- Caption style:

### Spacing

- Section spacing:
- Card padding:
- Form field spacing:
- Button padding:
- Grid gap:

### Radius

- DaisyUI reference model:
- `selector` radius: `1rem` (`rounded-2xl`) for small selector-style elements like checkbox, toggle, badge.
- `field` radius: `0.25rem` (`rounded`) for medium field-style elements like button, input, select, tab.
- `box` radius: `0.5rem` (`rounded-lg`) for large box-style elements like card, modal, alert.
- Small radius:
- Medium radius:
- Large radius:
- Pill radius:

### Radius Mapping Reference

- Boxes: cards, modals, alerts -> DaisyUI `rounded-box` -> `var(--radius-box)` -> default `0.5rem`
- Fields: buttons, inputs, selects, tabs -> DaisyUI `rounded-field` -> `var(--radius-field)` -> default `0.25rem`
- Selectors: checkboxes, toggles, badges -> DaisyUI `rounded-selector` -> `var(--radius-selector)` -> default `1rem`
- Tailwind approximation:
- `0.25rem` -> `rounded`
- `0.5rem` -> `rounded-lg`
- `1rem` -> `rounded-2xl`
- Source reference:
- DaisyUI utilities: `https://daisyui.com/docs/utilities/`
- DaisyUI themes: `https://daisyui.com/docs/themes/`
- DaisyUI theme generator: `https://daisyui.com/theme-generator/`

### Shadows

- DaisyUI reference model:
- Regular cards in official examples usually use `shadow-sm`.
- Buttons use DaisyUI's built-in subtle depth shadow rather than a large Tailwind drop shadow.
- Modals use a much stronger elevated shadow for overlay separation.
- Subtle shadow: `shadow-sm`
- Standard shadow: `shadow-sm`
- Strong shadow: reserve for modal/dialog layers only
- Hover shadow: use one step stronger than resting state only when needed, typically `hover:shadow-md`
- Avoid:
- Large custom shadow values on normal cards and page sections
- Strong floating shadows on standard content surfaces
- Source reference:
- DaisyUI card docs: `https://daisyui.com/components/card/`
- DaisyUI utilities: `https://daisyui.com/docs/utilities/`
- DaisyUI button source CSS: `https://raw.githubusercontent.com/saadeghi/daisyui/refs/heads/master/packages/daisyui/src/components/button.css`
- DaisyUI modal source CSS: `https://raw.githubusercontent.com/saadeghi/daisyui/refs/heads/master/packages/daisyui/src/components/modal.css`

### Borders

- Default border:
- Strong border:
- Focus border:

## Layout Rules

### Page Containers

- Max width:
- Full-width pages:
- Default horizontal padding:
- Default vertical spacing:

### Grid Rules

- Desktop grid behavior:
- Tablet grid behavior:
- Mobile grid behavior:

### Section Structure

- Section heading spacing:
- Section body spacing:
- Divider usage:

## Common UI Elements

### Cards

#### Default Card

- Purpose:
- Background:
- Border:
- Radius: DaisyUI box-style baseline is `0.5rem` / `rounded-lg`
- Title text size: DaisyUI `.card-title` default is `1.125rem` / `18px`
- Body text size: DaisyUI `.card-body` default is `0.875rem` / `14px`
- Shadow: `shadow-sm`
- Padding:
- Hover behavior:

#### Card Text Size Reference

- DaisyUI default card text:
- Card title: `1.125rem` / `18px`
- Card body text: `0.875rem` / `14px`
- DaisyUI card size variants:
- `card-xs`: title `0.875rem` / `14px`, body `0.6875rem` / `11px`
- `card-sm`: title `1rem` / `16px`, body `0.75rem` / `12px`
- `card-md`: title `1.125rem` / `18px`, body `0.875rem` / `14px`
- `card-lg`: title `1.25rem` / `20px`, body `1rem` / `16px`
- `card-xl`: title `1.375rem` / `22px`, body `1.125rem` / `18px`
- Source reference:
- DaisyUI card docs: `https://daisyui.com/components/card/`
- DaisyUI card source CSS: `https://raw.githubusercontent.com/saadeghi/daisyui/refs/heads/master/packages/daisyui/src/components/card.css`

#### Compact Card

- Purpose:
- Height/density:
- Padding:
- Radius: default to box-style baseline unless intentionally flatter or rounder
- Shadow: default to `shadow-sm`
- Typical use cases:

#### Expandable Card

- Closed state:
- Open state:
- Trigger style:
- Transition style:
- Divider behavior:
- Radius: default to box-style baseline unless page-specific guidance overrides it
- Shadow: default to `shadow-sm`, optionally `hover:shadow-md` if the entire card is interactive

### Buttons

#### Primary Button

- Background:
- Text:
- Border:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`
- Padding:
- Hover:
- Active:
- Disabled:

#### Secondary Button

- Background:
- Text:
- Border:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`
- Hover:

#### Tertiary Button

- Use case:
- Visual style:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`
- Hover:

#### Destructive Button

- Background:
- Text:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`
- Hover:

#### Icon Button

- Size:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`
- Hover:

### Links

- Default link style:
- Hover style:
- External link style:
- Muted link style:

### Form Elements

#### Inputs

- Height:
- Background:
- Border:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`
- Text color:
- Placeholder style:
- Focus style:
- Error style:

#### Textareas

- Default min height:
- Resize behavior:
- Padding:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`

#### Selects

- Closed state:
- Focus state:
- Radius: DaisyUI field-style baseline is `0.25rem` / `rounded`

#### Checkboxes and Radios

- Size:
- Border:
- Selected state:
- Focus state:
- Radius:
- Checkbox/toggle/badge baseline: DaisyUI selector-style `1rem` / `rounded-2xl`
- Radios remain fully circular where applicable

#### Labels and Help Text

- Label style:
- Required marker:
- Help text:
- Error text:

### Badges and Status Pills

- DaisyUI base badge model:
- Radius: selector-style `1rem` / `rounded-2xl`
- Default font size: `0.875rem` / `14px`
- Default height: `calc(var(--size-selector) * 6)` -> default `1.5rem` / `24px`
- Default padding: `calc(height / 2 - border)`
- Default layout: inline-flex, centered, gap `0.5rem`

#### Badge Sizes

- `badge-xs`: `0.625rem` / `10px`, height `1rem` / `16px`
- `badge-sm`: `0.75rem` / `12px`, height `1.25rem` / `20px`
- `badge-md`: `0.875rem` / `14px`, height `1.5rem` / `24px`
- `badge-lg`: `1rem` / `16px`, height `1.75rem` / `28px`
- `badge-xl`: `1.125rem` / `18px`, height `2rem` / `32px`

#### Badge Color Variants

- Default badge:
- Neutral badge: `badge-neutral`
- Primary badge: `badge-primary`
- Secondary badge: `badge-secondary`
- Accent badge: `badge-accent`
- Info badge: `badge-info`
- Success badge: `badge-success`
- Warning badge: `badge-warning`
- Error badge: `badge-error`

#### Badge Style Variants

- Solid/default: filled badge using theme color
- Soft: `badge-soft`
- Outline: `badge-outline`
- Dash outline: `badge-dash`
- Ghost: `badge-ghost`

#### Badge Variant Rules

- Standard status badges should default to solid or soft styles.
- Outline badges are acceptable for lighter emphasis.
- Dash badges are for alternative outline treatment only, not default status display.
- Ghost badges are neutral/minimal and should be used sparingly.
- Neutral outline and neutral dash badges use dark text and should only be used on light backgrounds.

#### Badge Composition Patterns

- Empty badge:
- Icon badge:
- Badge inline with text:
- Badge inside a button:

#### Badge Project Rules

- Default status pills should use DaisyUI selector-style radius `rounded-2xl`.
- Default status size should start at DaisyUI `badge-md`.
- Use `badge-sm` only for dense tables or metadata rows.
- Use `badge-lg` or `badge-xl` only when explicitly needed for emphasis.
- Prefer soft badges for secondary metadata.
- Prefer solid badges for important status states.
- Avoid inventing one-off badge radii, padding, or font sizes.

#### Badge Source Reference

- DaisyUI badge docs: `https://daisyui.com/components/badge/`
- DaisyUI badge source CSS: `https://raw.githubusercontent.com/saadeghi/daisyui/refs/heads/master/packages/daisyui/src/components/badge.css`

### Tables and Lists

#### Table Style

- Header style:
- Row style:
- Hover row style:
- Cell padding:

#### List Item Style

- Spacing:
- Divider:
- Hover:

### Modals and Drawers

- Overlay:
- Panel background:
- Radius: modal baseline should start from DaisyUI box-style `0.5rem` / `rounded-lg`
- Shadow:
- Header:
- Footer actions:

### Empty States

- Icon treatment:
- Heading style:
- Description style:
- CTA style:

### Loading States

- Spinner style:
- Skeleton style:
- Disabled/loading button behavior:

## Interaction Rules

### Hover

- General hover philosophy:
- Elements that should hover:
- Elements that should not hover:

### Focus

- Focus ring style:
- Keyboard navigation expectations:

### Transitions

- Default duration:
- Default easing:
- Elements that should animate:
- Elements that should not animate:

## Responsive Rules

- Mobile-first requirement:
- Typography scaling:
- Card stacking behavior:
- Button wrapping behavior:
- Form layout behavior:

## Accessibility Rules

- Minimum contrast expectations:
- Focus visibility:
- Tap target minimum:
- Error messaging:
- Disabled state clarity:

## Tailwind Implementation Rules

- Preferred utility approach:
- When to extract reusable Blade/component classes:
- When to use `@apply`:
- When to avoid custom CSS:
- Naming rules for reusable component classes:

## Current Shared Patterns

### Approved Patterns

- Add approved shared patterns here as they are finalized.

### Deprecated Patterns

- Add patterns here that should no longer be used.

## Page-Specific Notes

### `/my-products`

- Notes:

### Other Pages

- Page:
- Notes:

## Change Log

- Date:
- Updated by:
- What changed:
