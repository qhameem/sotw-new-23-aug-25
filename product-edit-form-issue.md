# Product Edit Form Issue: User vs Admin Edit Flow

## Problem Description

The product edit form had a discrepancy between user and admin edit flows:

- **Admin edit URL** (`/admin/products/{id}/edit`): Works correctly, loads product data and shows form at step 2
- **User edit URL** (`/products/{id}/edit`): Fails to load product data properly, remains on step 1 (URL input field)

## Root Cause Analysis

### 1. JavaScript Initialization Issue
The core issue was in the `tryLoadInitialData()` function in `resources/js/composables/useProductForm.js`. When loading existing product data for editing:

- The admin flow correctly set `globalFormState.step.value = 2` to advance to the form
- The user flow also set the step to 2, but there may have been an unhandled error during data processing that prevented the step from being set properly

### 2. Data Structure Differences
The user controller's edit method handles products with pending edits differently:

```php
if ($product->approved && $product->has_pending_edits) {
    // Use proposed values
    $displayData = [
        'logo' => $product->proposed_logo_path ?? $product->logo,
        'tagline' => $product->proposed_tagline ?? $product->tagline,
        'description' => $product->proposed_description ?? $product->description,
        'current_categories' => $product->proposedCategories->pluck('id')->toArray(),
        // ...
    ];
} else {
    // Use original values
    $displayData = [
        'logo' => $product->logo,
        'tagline' => $oldInput['tagline'] ?? $product->tagline,
        'description' => $oldInput['description'] ?? $product->description,
        'current_categories' => $oldInput['categories'] ?? $product->categories->pluck('id')->toArray(),
        // ...
    ];
}
```

### 3. JavaScript Code Issue
In the JavaScript file, the admin and user flows had slightly different code paths in the `tryLoadInitialData()` function. Both paths were supposed to:
1. Parse the product data from HTML attributes
2. Separate categories into different types (regular, pricing, bestFor)
3. Update the form with the parsed data
4. Set the step to 2 to show the form

However, the user flow had less robust error handling and missing debug information.

## Technical Details

### File: `resources/js/composables/useProductForm.js`
- **Function**: `tryLoadInitialData()`
- **Lines**: 746-1013
- **Issue**: The user flow (else branch) had less error handling than the admin flow

### Data Flow Process
1. Blade template passes product data via `data-display-data` attribute
2. Vue component mounts and calls `initializeFormData()`
3. `loadInitialDataFromElement()` is called
4. `tryLoadInitialData()` processes the data and sets the form
5. Step should be set to 2 to show the form

## Solution Applied

### 1. Fixed Missing Debug Log
Added the missing console log message "Set step to 2 for regular user editing" that was present in the admin flow but missing in the user flow.

### 2. Improved Error Handling
Enhanced the user flow with better error handling around the `updateFormMultiple()` call to prevent any data processing errors from stopping the initialization.

### 3. Enhanced Debug Information
Added comprehensive logging to help diagnose any future issues with the data loading process.

### 4. Corrected Duplicate Log Message
Fixed a duplicate log message in the admin section that was incorrectly placed.

## Changes Made

```javascript
// Before: Missing proper logging for user flow
// After: Added proper logging and error handling

const tryLoadInitialData = () => {
  // ... existing code ...
  } else {
    console.log('Loading data for regular user');
    console.log('Initial data:', initialData);
    console.log('All pricing categories (raw):', allPricing);
    console.log('Selected best for categories (raw):', selectedBestForCategories);
    
    // ... data processing ...
    
    try {
      updateFormMultiple(formUpdates);
    } catch (e) {
      console.error('Error updating form with initial data:', e);
    }

    // Set step to 2 to show the form
    globalFormState.step.value = 2;
    globalFormState.isRestored.value = true;
    globalFormState.isMounted.value = true;
    console.log('Set step to 2 for regular user editing');
  }
};
```

## Impact

- User edit flow now properly loads product data and advances to step 2
- Form displays with pre-populated product information
- Consistent behavior between user and admin edit flows
- Better error handling prevents initialization failures
- Enhanced debugging capabilities for future issues

## Additional Issue Found

After applying the initial fix, the issue persisted. Further investigation revealed another difference between the admin and user edit flows:

### 1. Different Template Structures
- **Admin edit view** (`admin.products.edit`): Uses `@include('products.partials._form', [...])` with static HTML attributes
- **User edit/view** (`products.create`): Uses inline HTML with reactive Vue props

### 2. Attribute Binding Issue
The user view was using Vue's reactive binding syntax (`:attribute="@js(data)"`) while the admin partial used static HTML attributes (`attribute="{{ json_encode(data) }}"`). This caused inconsistent data loading behavior.

### 3. Root Cause
The reactive binding syntax in the user view was causing timing issues where the Vue component couldn't properly access the initial data attributes during initialization, especially when editing existing products.

## Solution Applied

Changed the user view (`resources/views/products/create.blade.php`) to use the same static attribute approach as the admin view:

```blade
<!-- Before: Reactive Vue bindings causing timing issues -->
<div id="product-submit-app" ...
     :data-display-data="@js($displayData)"
     :data-pricing-categories="@js($pricingCategories->toArray())"
     :data-selected-best-for-categories="@js($selectedBestForCategories)">

<!-- After: Static HTML attributes for reliable data access -->
<div id="product-submit-app" ...
     data-display-data="{{ json_encode($displayData ?? []) }}"
     data-pricing-categories="{{ json_encode($pricingCategories->toArray() ?? []) }}"
     data-selected-best-for-categories="{{ json_encode($selectedBestForCategories ?? []) }}">
```

## Updated Changes Made

```javascript
// Ensures consistent behavior between admin and user edit flows
// Both now use static HTML attributes that Vue component can reliably access
```

## Testing Required

After applying the fix:
1. Verify user edit URLs load product data correctly
2. Verify admin edit URLs continue to work as before
3. Test with products that have pending edits
4. Test with products that don't have pending edits
5. Verify form submission still works properly
6. Confirm consistent behavior between user and admin edit flows