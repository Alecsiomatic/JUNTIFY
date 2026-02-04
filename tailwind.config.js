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
                'ddu-aqua': '#6DDEDD',
                'ddu-lavanda': '#546CB1',
                'ddu-navy-dark': '#1F2A4E',
                'ddu-navy': '#233771',
                'ddu-blue': '#45539F',
                'ddu-purple': '#6F78E4',
            },
        },
    },

    plugins: [forms],
};
