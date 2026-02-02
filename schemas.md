# Schema Markup Documentation

This document outlines the structured data schemas currently implemented on the Software on the Web platform across different page types.

## Home Page Schemas

### WebSite Schema
Located in: `resources/views/layouts/app.blade.php` and `resources/views/layouts/submission.blade.php`

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Software on the Web",
  "alternateName": ["Softwareontheweb"],
  "url": "https://softwareontheweb.com"
}
```

### ItemList Schema
Located in: `resources/views/partials/products_list_with_pagination.blade.php`

Used for organizing products in paginated lists:

```json
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "url": "product_url"
    }
  ]
}
```

### Product Schema (Microdata)
Located in: `resources/views/partials/products_list.blade.php`

Implemented using microdata attributes throughout product listings:
- `itemscope itemtype="https://schema.org/Product"`
- Includes properties like name, description, offers (with price and availability), etc.

## Product Page Schemas

### Product Schema (Microdata)
Located in: `resources/views/products/show.blade.php` (via header partials)

Uses microdata attributes:
- `itemscope itemtype="https://schema.org/Product"`
- Properties include:
  - `itemprop="image"` for product logo/image
  - `itemprop="name"` for product name
  - `itemprop="description"` for product description
  - `itemprop="offers"` with nested Offer schema
  - `itemprop="price"` and `itemprop="priceCurrency"` for pricing
  - `itemprop="availability"` for stock status

### Offer Schema (Nested in Product)
Located in: `resources/views/partials/products_list.blade.php`

```html
<div itemprop="offers" itemscope itemtype="https://schema.org/Offer">
  <meta itemprop="priceCurrency" content="USD" />
  Price: <span itemprop="price" content="0.00">Free</span>
  <link itemprop="availability" href="https://schema.org/InStock" />
</div>
```

## Category Page Schemas

### WebSite Schema
Located in: `resources/views/layouts/app.blade.php` and `resources/views/layouts/submission.blade.php`

Same implementation as the home page:
```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Software on the Web",
  "alternateName": ["Softwareontheweb"],
  "url": "https://softwareontheweb.com"
}
```

### ItemList Schema (for Category Products)
Located in: `resources/views/partials/products_list_with_pagination.blade.php`

When displaying products within a category, the same ItemList schema is used as on the home page.

### Product Schema (Microdata for Category Listings)
Located in: `resources/views/partials/products_list.blade.php`

Used for individual product items within category pages with the same microdata attributes as described above.

## Schema Implementation Notes

- The site uses a hybrid approach combining JSON-LD for site-wide schemas and microdata for product-specific information
- The WebSite schema is consistently applied across all pages via the main layout files
- Product schemas use microdata attributes for better integration with existing HTML structure
- Pricing information is dynamically displayed based on product pricing type (free vs paid)
- Availability information is standardized using the `https://schema.org/InStock` URL