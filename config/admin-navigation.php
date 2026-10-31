<?php

return [
    'primary' => [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'patterns' => ['admin.dashboard', 'dashboard']],
        ['route' => 'admin.product-approvals.index', 'label' => 'Product approvals', 'icon' => 'grid', 'patterns' => ['admin.product-approvals.*', 'admin.products.pending-edits.*']],
        ['route' => 'admin.products.index', 'label' => 'Products', 'icon' => 'file', 'patterns' => ['admin.products.*']],
        ['route' => 'admin.users.index', 'label' => 'Users', 'icon' => 'users', 'patterns' => ['admin.users.*']],
        ['route' => 'admin.product-claims.index', 'label' => 'Product claims', 'icon' => 'check', 'patterns' => ['admin.product-claims.*']],
        ['route' => 'admin.categories.index', 'label' => 'Categories', 'icon' => 'grid', 'patterns' => ['admin.categories.*', 'admin.category-types.*', 'admin.custom-category-submissions.*']],
        ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'settings', 'patterns' => ['admin.settings.*', 'admin.product-description-templates.*']],
    ],
    'additional' => [
        ['route' => 'admin.product-submission-drafts.index', 'label' => 'Unsubmitted products', 'icon' => 'file'],
        ['route' => 'admin.product-discovery.index', 'label' => 'Product discovery', 'icon' => 'search'],
        ['route' => 'admin.tech-stacks.index', 'label' => 'Tech stacks', 'icon' => 'grid'],
        ['route' => 'admin.premium-products.index', 'label' => 'Premium products', 'icon' => 'grid'],
        ['route' => 'admin.product-reviews.index', 'label' => 'Product reviews', 'icon' => 'check'],
        ['route' => 'admin.articles.posts.index', 'label' => 'Articles', 'icon' => 'file'],
        ['route' => 'admin.advertising.index', 'label' => 'Advertising', 'icon' => 'grid'],
        ['route' => 'admin.search-history.index', 'label' => 'Search history', 'icon' => 'search'],
        ['route' => 'admin.email-logs.index', 'label' => 'Email logs', 'icon' => 'mail'],
        ['route' => 'admin.seo.index', 'label' => 'SEO', 'icon' => 'search'],
    ],
];
