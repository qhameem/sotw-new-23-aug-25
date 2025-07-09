# Blog Files Index

This document lists all the files related to the display and management of blog posts in the application.

## User-Facing Display

These files are responsible for how blog posts are presented to the end-users.

*   [`resources/views/blog/index.blade.php`](resources/views/blog/index.blade.php): Main blog page, likely lists all posts.
*   [`resources/views/blog/show.blade.php`](resources/views/blog/show.blade.php): Shows a single blog post.
*   [`resources/views/blog/category.blade.php`](resources/views/blog/category.blade.php): Shows posts in a specific category.
*   [`resources/views/blog/tag.blade.php`](resources/views/blog/tag.blade.php): Shows posts with a specific tag.
*   [`resources/views/blog/search.blade.php`](resources/views/blog/search.blade.php): Shows search results for blog posts.
*   [`resources/views/blog/partials/_post_card.blade.php`](resources/views/blog/partials/_post_card.blade.php): A partial view for displaying a single post card, likely used in index, category, tag, and search pages.

## Admin-Facing Display and Management

These files are used for managing blog posts, categories, and tags from the admin panel.

*   [`resources/views/admin/blog/posts/index.blade.php`](resources/views/admin/blog/posts/index.blade.php): Admin page to list all blog posts.
*   [`resources/views/admin/blog/posts/create.blade.php`](resources/views/admin/blog/posts/create.blade.php): Admin page to create a new blog post.
*   [`resources/views/admin/blog/posts/edit.blade.php`](resources/views/admin/blog/posts/edit.blade.php): Admin page to edit an existing blog post.
*   [`resources/views/admin/blog/categories/index.blade.php`](resources/views/admin/blog/categories/index.blade.php): Admin page to list all blog categories.
*   [`resources/views/admin/blog/categories/create.blade.php`](resources/views/admin/blog/categories/create.blade.php): Admin page to create a new blog category.
*   [`resources/views/admin/blog/categories/edit.blade.php`](resources/views/admin/blog/categories/edit.blade.php): Admin page to edit an existing blog category.
*   [`resources/views/admin/blog/tags/index.blade.php`](resources/views/admin/blog/tags/index.blade.php): Admin page to list all blog tags.
*   [`resources/views/admin/blog/tags/edit.blade.php`](resources/views/admin/blog/tags/edit.blade.php): Admin page to edit an existing blog tag.

## Controllers

These files contain the logic for handling requests related to the blog.

*   [`app/Http/Controllers/BlogController.php`](app/Http/Controllers/BlogController.php): Handles the logic for the user-facing blog pages.
*   [`app/Http/Controllers/Admin/BlogPostController.php`](app/Http/Controllers/Admin/BlogPostController.php): Handles the logic for the admin-facing blog post management.
*   [`app/Http/Controllers/Admin/BlogCategoryController.php`](app/Http/Controllers/Admin/BlogCategoryController.php): Handles the logic for the admin-facing blog category management.
*   [`app/Http/Controllers/Admin/BlogTagController.php`](app/Http/Controllers/Admin/BlogTagController.php): Handles the logic for the admin-facing blog tag management.

## Models

These files define the data structure and relationships for the blog-related database tables.

*   [`app/Models/BlogPost.php`](app/Models/BlogPost.php): The Eloquent model for the `blog_posts` table.
*   [`app/Models/BlogCategory.php`](app/Models/BlogCategory.php): The Eloquent model for the `blog_categories` table.
*   [`app/Models/BlogTag.php`](app/Models/BlogTag.php): The Eloquent model for the `blog_tags` table.