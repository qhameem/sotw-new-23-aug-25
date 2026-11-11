import { sharedTailwindConfig } from './tailwind.shared.js';

export default {
    ...sharedTailwindConfig,
    content: [
        './resources/views/home.blade.php',
        './resources/views/layouts/app.blade.php',
        './resources/views/components/{main-content-layout,top-bar,application-logo,add-product-button,feedback-button,category-navigation-row,user-dropdown,week-header,product-category-tags,launch-readiness-sidebar-link,header-stats,top-categories,footer,mobile-nav-item}.blade.php',
        './resources/views/partials/{products_list,product-upvote-button,_homepage-about-card,_right-sidebar,_sidebar-ads,render_ad_block,_mobile-footer-menu,_newsletter-signup-form}.blade.php',
        './resources/views/products/partials/_browse.blade.php',
    ],
};
