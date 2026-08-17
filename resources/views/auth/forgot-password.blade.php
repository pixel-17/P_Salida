<x-guest-layout>
    <div class="mb-7 text-center">
        <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">¿Olvidaste tu contraseña?</h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            No hay problema. Escribe tu correo y te enviaremos un enlace para crear una nueva contraseña.
        </p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button class="w-full !py-3 text-[0.95rem]">
            Enviar enlace de recuperación
        </x-primary-button>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                ← Volver a ingresar
            </a>
        </div>
    </form>
</x-guest-layout>
