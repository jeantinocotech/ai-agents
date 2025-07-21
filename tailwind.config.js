import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gray: {
                    50:  '#f9fafb',
                    100: '#f3f4f6',
                    200: '#e5e7eb',
                    300: '#d1d5db',
                    400: '#828A8F',     // claro Mercedes
                    500: '#6b7280',
                    600: '#333F47',     // médio Mercedes
                    700: '#27343C',     // escuro Mercedes
                    800: '#181d20',
                    900: '#0B1F2A',     // mais escuro Mercedes
                },
                // (Opcional) para uso direto: bg-mercedes-dark, etc.
                mercedes: {
                    dark:   '#0B1F2A',
                    mid:    '#333F47',
                    light:  '#828A8F',
                }
            },
        },
    },

    plugins: [forms],
};
