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
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                /* Neutral scale mapped from saffhire.com (#0a1628, #34322d, #858481, #f8f9fa) */
                enterprise: {
                    50: '#f8f9fa',
                    100: '#f0fdf4',
                    200: '#e2e8e3',
                    300: '#c5d4c7',
                    400: '#858481',
                    500: '#6b7280',
                    600: '#34322d',
                    700: '#1a1a19',
                    800: '#0a1628',
                    900: '#0a1628',
                    950: '#060f18',
                },
                /* Saffhire brand green — primary accent from saffhire.com */
                accent: {
                    DEFAULT: '#39b54a',
                    dark: '#2ea03e',
                    darker: '#0f5132',
                    muted: '#f0fdf4',
                    light: '#dcfce7',
                },
                saffhire: {
                    green: '#39b54a',
                    navy: '#0a1628',
                    ink: '#34322d',
                    muted: '#858481',
                    surface: '#f8f9fa',
                },
            },
            boxShadow: {
                panel: '0 1px 2px 0 rgb(10 22 40 / 0.04), 0 0 0 1px rgb(10 22 40 / 0.06)',
                header: '0 1px 0 0 rgb(10 22 40 / 0.08)',
            },
        },
    },

    plugins: [forms],
};
