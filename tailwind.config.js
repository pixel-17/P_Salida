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
            // "brand" nunca estaba definido acá pese a usarse en más de
            // 25 vistas (botones, avatares, badges, iconos activos del
            // sidebar) — Tailwind descartaba esas clases en silencio y
            // esos elementos quedaban sin color de fondo/texto (blancos).
            // Los tonos 300–700 son exactamente los hex ya hardcodeados
            // en resources/css/app.css (botón primario, foco de inputs,
            // ícono activo del sidebar), para no cambiar la identidad
            // visual existente — solo hacerla real en las utilidades.
            colors: {
                brand: {
                    50: '#eef3ff',
                    100: '#dde7fe',
                    200: '#b8cdfd',
                    300: '#93b8ff',
                    400: '#5e91ff',
                    500: '#3b6cf6',
                    600: '#2549ea',
                    700: '#1e39d1',
                    800: '#1a2fb0',
                    900: '#16268f',
                    950: '#0c1550',
                },
            },
            boxShadow: {
                // Usada en tarjetas de acceso rápido / avatares con
                // degradado (dashboard admin); tampoco estaba definida.
                glass: '0 4px 14px -4px rgba(37, 73, 234, 0.35)',
            },
        },
    },

    plugins: [forms],
};
