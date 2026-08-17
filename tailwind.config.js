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
            // Escala violeta/índigo (antes era azul) — más moderna y
            // distintiva; mismos puntos 300–700 replicados en
            // resources/css/app.css (botón primario, foco de inputs,
            // ícono activo del sidebar) para mantener todo coherente.
            colors: {
                brand: {
                    50: '#ecfdf5',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                    800: '#065f46',
                    900: '#064e3b',
                    950: '#022c22',
                },
            },
            boxShadow: {
                // Usada en tarjetas de acceso rápido / avatares con
                // degradado (dashboard admin); tampoco estaba definida.
                glass: '0 4px 14px -4px rgba(76, 237, 58, 0.35)',
                // shadow-glass-lg: usada en dropdowns, toasts, la campana
                // de notificaciones y los íconos "hero" de login/errores/
                // bloqueo — tampoco estaba definida, así que esos paneles
                // flotantes se veían sin elevación (planos).
                'glass-lg': '0 16px 40px -12px rgba(15, 15, 20, 0.28)',
            },
        },
    },

    plugins: [forms],
};
