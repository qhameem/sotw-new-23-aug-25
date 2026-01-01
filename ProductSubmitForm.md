# Product Submission Form Layout Optimization

## Current State
The product submission form on the `/add-product` page has been optimized for better usability on both desktop and mobile devices.

> [!NOTE]
> **Update**: The page now uses a **Responsive Dedicated Layout** (100% mobile, 150% desktop), a **Sticky Vertical Sidebar**, and **Readable Field Widths**.

## Structure
The form has 3 tabs:
- Main info
- Images and media
- Launch (Launch checklist)

## Layout Changes

### 1. Vertical Sidebar (Desktop)
- **Desktop**: Navigation tabs are moved to the left side in a sticky sidebar.
- **Mobile**: Navigation tabs stack at the top (or scroll horizontally) to fit smaller screens.

### 2. Product Details Form (Main Info Tab)
- Group related fields together in columns on desktop.
- **Readability**: Fields are constrained to `max-w-4xl`.

### 3. Scroll Indicator
- A floating badge that appears when the user can scroll down to see more content.

### 4. Dedicated Responsive Layout
- **New Layout File**: `resources/views/layouts/submission.blade.php`
- **Responsive Width**:
  - **Mobile**: `w-full` (Standard 100% width).
  - **Desktop**: `w-[150%]` (Super wide with horizontal scrolling).

## Files
- `resources/views/layouts/submission.blade.php`: The new dedicated layout.
- `resources/views/products/create.blade.php`: Extends `layouts.submission`.
- `resources/js/components/product-submit/FormWizard.vue`: Handles the layout logic (Sidebar vs Top tabs).
- `resources/js/components/product-submit/ProductDetailsForm.vue`: Form fields for step 1.
- `resources/js/components/product-submit/ScrollIndicator.vue`: The floating scroll helper.

## Benefits
- **Zero Conflicts**: Dedicated layout ensures the 150% width doesn't break other pages.
- **Maximum Space**: Utilizing 150% of the screen width provides maximum canvas space on desktop.
- **Mobile Friendly**: Reverts to standard behavior on mobile for best UX.