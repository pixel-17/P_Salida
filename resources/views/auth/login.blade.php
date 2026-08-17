<x-guest-layout>
    <div class="mb-7 text-center">
        <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Bienvenido de nuevo</h1>
        <p class="text-sm text-gray-500 mt-1.5">Ingresa a tu cuenta para continuar</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input id="remember_me" type="checkbox"
                       class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-400 focus:ring-offset-0"
                       name="remember">
                <span class="text-sm text-gray-600">Recuérdame</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full !py-3 text-[0.95rem]">
            Ingresar
        </x-primary-button>
    </form>
</x-guest-layout>
