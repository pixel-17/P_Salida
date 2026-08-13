import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Acento de marca: el único color vivo sobre una interfaz que,
                // fuera de él, es enteramente degradados de negro/gris.
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                },
                // Escala neutra fría usada por las superficies oscuras
                // (sidebar, tarjetas, fondo) para lograr degradados de negro
                // realmente neutros, no azulados.
                ink: {
                    50: '#f7f7f8',
                    100: '#ececee',
                    200: '#d3d3d7',
                    300: '#a8a8b0',
                    400: '#75757e',
                    500: '#4d4d55',
                    600: '#333338',
                    700: '#232326',
                    800: '#18181a',
                    900: '#0c0c0d',
                    950: '#000000',
                },
            },
            boxShadow: {
                'glass-lg': '0 20px 45px -12px rgba(0, 0, 0, 0.35)',
                glow: '0 0 0 1px rgba(99, 102, 241, 0.4), 0 8px 24px -4px rgba(99, 102, 241, 0.45)',
            },
        },
    },

    plugins: [forms],
};
