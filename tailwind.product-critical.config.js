import homeCriticalConfig from './tailwind.home-critical.config.js';

export default {
    ...homeCriticalConfig,
    content: [
        ...homeCriticalConfig.content,
        './resources/views/products/show.blade.php',
        './resources/views/products/partials/**/*.blade.php',
        './resources/views/components/products/**/*.blade.php',
        './resources/views/components/{page-header,breadcrumbs,feedback-modal}.blade.php',
    ],
};
