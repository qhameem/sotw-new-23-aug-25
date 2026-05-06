import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/public.css',
                'resources/css/rich-content.css',
                'resources/css/todo-vendor.css',
                'resources/js/app.js',
                'resources/js/seo-manager.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        watch: {
            ignored: [
                '**/.git/**',
                '**/node_modules/**',
                '**/vendor/**',
                '**/public/logos/**',
                '**/storage/**',
            ],
        },
    },
    build: {
        rollupOptions: {
            output: {
            },
        },
        chunkSizeWarningLimit: 1000, // Adjust limit to silence warning if reasonable
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
