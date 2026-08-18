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
            // Escala celeste — vivo, moderno y elegante; mismos puntos
            // 300–700 replicados en resources/css/app.css (botón
            // primario, foco de inputs, ícono activo del sidebar) para
            // mantener todo coherente.
            colors: {
                brand: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                    950: '#082f49',
                },
            },
            boxShadow: {
                // Usada en tarjetas de acceso rápido / avatares con
                // degradado (dashboard admin); tampoco estaba definida.
                glass: '0 4px 14px -4px rgba(14, 165, 233, 0.35)',
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
