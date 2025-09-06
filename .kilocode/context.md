# Project Context: Product Submission & Details

This document provides context for the AI assistant to quickly understand the key components of the product submission and display features.

## Feature: Product URL Autofill & Data Fetching

The primary feature is the automatic filling of the product submission form based on a user-provided URL. This has been expanded to include:
*   **Metadata Scraping**: Fetching the product's name, tagline, and description.
*   **Category Suggestion**: Suggesting relevant software categories and pricing models.
*   **Tech Stack Detection**: Identifying the technologies used to build the product.
*   **Logo & Media Fetching**: Finding and prioritizing the best-quality logo and fetching a relevant product image.

### Key Files:

*   **Product Form View**: `resources/views/products/create.blade.php`
    *   The main view for the submission form, containing the core Alpine.js component that manages all state and interactivity.
*   **Form Partial**: `resources/views/products/partials/_form.blade.php`
    *   This Blade partial contains the actual HTML structure of the form fields.
*   **Product Details View**: `resources/views/products/show.blade.php`
    *   The view for displaying the product details page.
*   **Sidebar Partial**: `resources/views/products/partials/_sidebar-info.blade.php`
    *   This Blade partial contains the sidebar for the product details page.
*   **Product Controller**: `app/Http/Controllers/ProductController.php`
    *   Handles the `store` and `update` logic for product submissions, including server-side processing like adding `rel="nofollow"` to links.
*   **API Controllers**:
    *   `app/Http/Controllers/Api/ProductMetaController.php`: Handles fetching basic metadata (name, description).
    *   `app/Http/Controllers/Api/TechStackController.php`: Handles the API request for tech stack detection.
*   **Services**:
    *   `app/Services/CategoryClassifier.php`: Contains the logic for the category suggestion algorithm.
    *   `app/Services/TechStackDetectorService.php`: Contains the logic for detecting technologies from a URL.
    *   `app/Services/FaviconExtractorService.php`: Contains the logic for finding and scoring potential logos, with a preference for SVGs.
*   **Database Migrations & Seeders**:
    *   Migrations for `categories` (adding keywords), `tech_stacks`, and the `product_tech_stack` pivot table.
    *   Seeders for `CategoryKeywordsSeeder` and `TechStackSeeder` to populate the necessary data.

### Current Algorithms & Logic:

*   **Category Classification**: A keyword-based algorithm that scores and ranks categories by comparing scraped text from the URL against a predefined list of keywords for each category.
*   **Tech Stack Detection**: A service that uses the BuiltWith API to detect technologies. It includes a fallback to the Wappalyzer API if the BuiltWith API key is not configured.
*   **Logo Fetching**: A service that scrapes the page for potential logos from `<link>`, `<meta>`, and `<img>` tags. It uses a scoring system to rank logos by quality (prioritizing keywords like "logo" and file types like SVG) and limits the selection to the top six.
*   **Link Handling**: All user-submitted links in the product description are automatically processed on the server to add a `rel="nofollow"` attribute for SEO purposes.

### UI/UX Enhancements:

*   **Numbered Sections & Completion Checkmarks**: The form is divided into numbered sections (e.g., "1 of 5") to guide the user. A green checkmark appears next to the section title when all required fields are completed.
*   **Quill.js Editor**: The rich text editor has been styled to match the site's theme, with a smaller font size for a cleaner look.
*   **"Fetch Data" Button**: A button next to the URL field allows users to manually trigger data fetching. Data is fetched *only* when this button is clicked. It displays a spinner while fetching and maintains a consistent size.
*   **Inline Status Updates**: The URL card provides real-time feedback during the data fetching process (e.g., "Fetching metadata...").
*   **Inline Error Messages**: If data fetching fails, an inline message appears in the URL card instead of a disruptive alert.
*   **Multiple Logo Selection**: The form displays multiple fetched logos, allowing the user to choose their preferred one.
*   **Consistent Remove Icons**: Both uploaded logos and fetched media have a consistent red cross icon for removal.
*   **Consolidated Media Section**: The fetched product image is displayed within the "Media and Branding" section for a more unified experience.
*   **Product Details Page Layout**: On the product details page, the video and product image are displayed in the same dimensions, with the video on the left and the image on the right. The tech stack is also displayed in the sidebar.