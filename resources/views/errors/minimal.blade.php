<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Error' }} — {{ config('app.name', 'Sistema de Papeletas') }}</title>
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
    @vite(['resources/css/app.css'])
</head>
<body class="app-bg font-sans antialiased text-gray-800 dark:text-gray-100">
    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="glass-panel px-10 py-12 max-w-md w-full animate-scale-in">
            <div class="w-20 h-20 mx-auto rounded-3xl glass-strong flex items-center justify-center shadow-glass-lg mb-6 animate-float">
                <span class="text-4xl">{{ $emoji ?? '🤔' }}</span>
            </div>

            <p class="text-sm font-semibold text-brand-600 tracking-wide mb-1">Error {{ $code }}</p>
            <h1 class="text-2xl font-extrabold text-gray-800 dark:text-gray-100 mb-2">{{ $title ?? 'Algo salió mal' }}</h1>
            <p class="text-sm text-gray-500 leading-relaxed mb-8">{{ $message ?? 'Ocurrió un problema inesperado.' }}</p>

            <a href="{{ url('/') }}" class="btn-primary w-full justify-center">
                Volver al inicio
            </a>
        </div>

        <p class="mt-8 text-xs text-gray-400">
            &copy; {{ date('Y') }} {{ config('app.name', 'Sistema de Papeletas') }}
        </p>
    </div>
</body>
</html>
