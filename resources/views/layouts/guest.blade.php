<!DOCTYPE html>
<html lang="es">
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
    <body class="font-sans antialiased app-bg auth-bg">
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10">

            <div class="flex flex-col items-center gap-3 animate-fade-in-up">
                {{--
                    A diferencia del resto de la app (que usa
                    <x-application-logo>, el logo SP), el login lleva el
                    logo institucional de la Municipalidad Distrital de
                    Santiago (Cusco) — es la puerta de entrada al sistema,
                    así que es donde corresponde la identidad oficial. Va
                    más ancho que alto (no cuadrado) porque el logo incluye
                    el texto "SANTIAGO" y hay que dejarlo legible.

                    Sin enlace: no debe redirigir a ningún lado, solo es
                    identidad visual de la pantalla de acceso.
                --}}
                <div class="w-28 rounded-2xl bg-white flex items-center justify-center shadow-glass-lg animate-float p-3">
                    <img src="{{ asset('images/logo-municipalidad.png') }}"
                         alt="Municipalidad Distrital de Santiago - Cusco"
                         class="w-full h-auto object-contain">
                </div>
                <span class="text-sm font-bold tracking-wide text-gray-700 uppercase text-center leading-tight">
                    {{ config('app.name', 'Sistema de Papeletas') }}
                </span>
            </div>

            <div class="w-full sm:max-w-md mt-7 px-6 py-8 sm:px-9 sm:py-9 glass-panel !rounded-3xl animate-scale-in">
                {{ $slot }}
            </div>

            <p class="mt-8 text-xs text-gray-400 animate-fade-in">
                &copy; {{ date('Y') }} {{ config('app.name', 'Sistema de Papeletas') }}
            </p>
        </div>
    </body>
</html>
