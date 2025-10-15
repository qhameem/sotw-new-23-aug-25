# Category Fetching and Filtering Logic

This document outlines the method used to fetch and filter product categories for the product creation and editing forms.

## Overview

The category selection UI is divided into three sections: "Category", "Best for", and "Pricing Model". Each of these sections corresponds to a specific category type, which is defined in the `category_types.json` file.

## Data Flow

1.  **Category Type Definitions**: The `storage/app/category_types.json` file contains a mapping of category type IDs to type names. This file is the single source of truth for category type definitions.

    ```json
    [
        {
            "type_id": 1,
            "type_name": "Category"
        },
        {
            "type_id": 2,
            "type_name": "Pricing"
        },
        {
            "type_id": 3,
            "type_name": "Best for"
        }
    ]
    ```

2.  **Controller Logic**: The `create` and `edit` methods in the `ProductController` are responsible for fetching the category data. The logic is as follows:
    *   The `category_types.json` file is read to get the type IDs for "Category", "Pricing", and "Best for".
    *   The `category_types` database table is queried to get the category IDs associated with each type ID.
    *   The `categories` database table is queried to get the category names for the retrieved category IDs.
    *   The resulting category collections (`$regularCategories`, `$bestForCategories`, and `$pricingCategories`) are passed to the view.

3.  **Blade Components**: The category selection UI is rendered by three Blade components:
    *   `resources/views/components/products/components/category-selection.blade.php`
    *   `resources/views/components/products/components/best-for-selection.blade.php`
    *   `resources/views/components/products/components/pricing-model-selection.blade.php`

    Each component receives a collection of categories and renders the appropriate UI (a searchable dropdown or a list of checkboxes).

## Implementation Details

The `ProductController` uses the `Storage` facade to read the `category_types.json` file and the `DB` facade to query the `category_types` table. The `Category` model is used to query the `categories` table.

This approach ensures that the category selection UI is always in sync with the data in the database and the definitions in the `category_types.json` file.