import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import theme from './tailwind-theme.json';

const fontFamilies = {};
if (theme.fontFamilies) {
    for (const font of theme.fontFamilies) {
        const fontSlug = font.toLowerCase().replace(/\s+/g, '-');
        fontFamilies[fontSlug] = [font, 'sans-serif'];
    }
}

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/flowbite/**/*.js' // Add Flowbite path
    ],

    safelist: [
        {
            pattern: /bg-(red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|neutral|stone)-(50|100|200|300|400|500|600|700|800|900|950)/,
        },
        // Keep other specific classes if needed, or add patterns for them too
        'ring-2',
        'ring-offset-2',
        'ring-indigo-500', // Keep for existing components if not all are updated at once
        'ring-primary-500', // Add new dynamic primary ring color
        'hover:ring-1',
        'hover:ring-gray-400',
    ],


    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--font-family-sans)', ...defaultTheme.fontFamily.sans],
                ...fontFamilies,
            },
            colors: {
                primary: {
                    // We use CSS variables to make these dynamic.
                    // Tailwind will generate classes like bg-primary-500, text-primary-500
                    // The actual color value comes from the :root definition in app.blade.php
                    500: 'var(--color-primary-500)',
                    600: 'var(--color-primary-600)', // For hover states
                    700: 'var(--color-primary-700)', // For active states
                },
                // You can extend other colors or keep existing customizations here
            },
            keyframes: {
                l7: {
                  '0%': { backgroundSize: 'calc(100%/3) 100%, calc(100%/3) 100%, calc(100%/3) 100%' },
                  '33%': { backgroundSize: 'calc(100%/3) 0%, calc(100%/3) 100%, calc(100%/3) 100%' },
                  '50%': { backgroundSize: 'calc(100%/3) 100%, calc(100%/3) 0%, calc(100%/3) 100%' },
                  '66%': { backgroundSize: 'calc(100%/3) 100%, calc(100%/3) 100%, calc(100%/3) 0%' },
                  '100%': { backgroundSize: 'calc(100%/3) 100%, calc(100%/3) 100%, calc(100%/3) 100%' },
                },
            },
            animation: {
                'dots-loader-anim': 'l7 1s infinite linear',
            },
        },
    },

    plugins: [
        forms,
        require('@tailwindcss/aspect-ratio'),
        require('flowbite/plugin'), // Add Flowbite plugin
        require('tailwind-scrollbar-hide'),
        function ({ addUtilities }) {
            addUtilities({
              '.custom-dots-loader-bg': {
                '--_g': 'no-repeat radial-gradient(circle closest-side, white 90%, transparent)',
                'background': 'var(--_g) 0% 50%, var(--_g) 50% 50%, var(--_g) 100% 50%',
                'background-size': 'calc(100%/3) 100%', // Initial state for background-size
              },
            })
        }
    ],
};
