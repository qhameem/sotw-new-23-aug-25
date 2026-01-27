# Product Edit Category Error Documentation

## Issue Description
When editing a product in the admin panel (`/admin/products/{product-id}/edit`) that already has 3 categories selected in each category type (regular, bestFor, pricing), an error message appears: "Maximum 3 categories allowed."

## Root Cause Analysis
The issue stems from the JavaScript validation in `resources/js/composables/useProductForm.js`:

1. **Validation Logic**: The `validateForm` function separately validates each category type:
   - Regular categories: max 3 allowed
   - BestFor categories: max 3 allowed  
   - Pricing categories: no explicit max limit

2. **Form Loading Logic**: In the `tryLoadInitialData` function, when loading existing product data:
   - All categories from the product are stored in `current_categories`
   - The code attempts to separate them by type (regular vs pricing)
   - If this separation fails, all categories may end up in the regular categories array

3. **Error Trigger**: If the separation logic fails and all categories (regular + pricing + bestFor) end up in the `form.categories` array, the validation will fail if there are more than 3 total categories.

## Code Locations
- **Primary**: `resources/js/composables/useProductForm.js` (lines 402-413 for validation, lines 790-816 for loading logic)
- **Validation**: `validateForm` function
- **Loading**: `tryLoadInitialData` function

## Solution Implemented
1. Updated error message to be more specific ("Maximum 3 regular categories allowed")
2. Improved category separation logic to handle edge cases
3. Enhanced fallback handling for category loading

## Symptoms
- Error appears when editing products with existing categories
- Affects admin users editing products
- Occurs during form validation before submission
- Prevents saving of legitimate product edits

## Business Rules
- Each category type (regular, bestFor, pricing) should allow up to 3 categories independently
- Having 3 regular + 3 bestFor + 3 pricing categories should be valid
- Total category count should not be limited to 3 across all types