<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema de Papeletas') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <script>
            (function () {
                const guardado = localStorage.getItem('tema');
                const prefiereOscuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (guardado === 'oscuro' || (!guardado && prefiereOscuro)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased app-bg">
        <div class="relative z-10 min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 px-4">

            <a href="/" class="animate-fade-in-up">
                <div class="w-20 h-20 rounded-3xl glass-strong flex items-center justify-center shadow-glass-lg animate-float">
                    <x-application-logo class="w-11 h-11 fill-current text-brand-600" />
                </div>
            </a>

            <div class="w-full sm:max-w-md mt-8 px-6 py-8 sm:px-8 glass-panel animate-scale-in">
                {{ $slot }}
            </div>

            <p class="mt-8 text-xs text-gray-500 animate-fade-in">
                &copy; {{ date('Y') }} {{ config('app.name', 'Sistema de Papeletas') }}
            </p>
        </div>
    </body>
</html>
