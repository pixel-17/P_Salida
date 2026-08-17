<x-guest-layout>
    <div class="mb-7 text-center">
        <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center">
            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Te recomendamos cambiar tu contraseña</h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            Tu contraseña actual es tu número de DNI. Por seguridad, te
            sugerimos definir una propia. Puedes hacerlo ahora o más tarde
            desde tu perfil.
        </p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.forzado.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input id="password" class="block w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" class="block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
                Ahora no
            </a>

            <x-primary-button class="!py-3">
                Guardar y continuar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
