# Categories Documentation

## Database Structure

### Categories Table
The `categories` table contains all categories (both product and pricing categories).

| Column | Type | Nullable | Default | Key | Extra |
|--------|------|----------|---------|-----|-------|
| id | bigint unsigned | NO | NULL | PRI | auto_increment |
| name | varchar(255) | NO | NULL | | |
| slug | varchar(255) | NO | NULL | | |
| description | text | YES | NULL | | |
| created_at | timestamp | YES | NULL | | |
| updated_at | timestamp | YES | NULL | |
| meta_description | text | YES | NULL | | |
| keywords | longtext | YES | NULL | | |

### Category Types Table
The `category_types` table links categories to their types, allowing categorization of categories.

| Column | Type | Nullable | Default | Key | Foreign Key |
|--------|------|----------|---------|-----|-------------|
| id | bigint unsigned | NO | NULL | PRI | |
| category_id | bigint unsigned | NO | NULL | | categories(id) |
| type_id | bigint unsigned | NO | NULL | | types(id) |
| created_at | timestamp | YES | NULL | | |
| updated_at | timestamp | YES | NULL | | |

### Types Table
The `types` table defines the different category types.

| Column | Type | Nullable | Default | Key |
|--------|------|----------|---------|-----|
| id | bigint unsigned | NO | NULL | PRI |
| name | varchar(255) | NO | NULL | |
| description | text | YES | NULL | |

## Category Types

- Type ID 2: Pricing categories
- Other type IDs: Product categories

## How Categories Work

1. **Product Categories**: These are standard categories that products can be associated with. They appear in the "Top Categories" list on the homepage sidebar.

2. **Pricing Categories**: These are special categories with type ID 2 that are used for pricing plans or tiers. These should not appear in the "Top Categories" list.

3. **Relationships**: 
   - Categories and Types are related through the `category_types` pivot table
   - Products are related to Categories through a many-to-many relationship
   - When displaying top categories, we exclude any category that has a relationship with type ID 2

4. **Display Logic**:
   - The "Top Categories" sidebar component only shows product categories (not pricing categories)
   - The "Pricing Categories" sidebar component only shows pricing categories (type 2)
   - Categories are ordered by number of associated products (descending) and then alphabetically
   - The top 6 categories are displayed in the Top Categories list with their product counts
   - The top 10 categories are displayed in the Pricing Categories list with their product counts
   - The Pricing Categories list has a maximum height with overflow scrolling to prevent sidebar overflow

5. **Caching**:
   - The top categories are cached for performance reasons (default: 1 hour)
   - The pricing categories are cached for performance reasons (default: 1 hour)
   - The cache TTL can be configured via the `performance.top_categories_cache_ttl` and `performance.pricing_categories_cache_ttl` config values
   - The maximum number of categories displayed can be configured via the `performance.max_top_categories_display` and `performance.max_pricing_categories_display` config values