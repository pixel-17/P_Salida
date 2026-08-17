<x-guest-layout>
    <div class="mb-7 text-center">
        <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Área segura</h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            Esta es una sección sensible del sistema. Confirma tu contraseña antes de continuar.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button class="w-full !py-3 text-[0.95rem]">
            Confirmar
        </x-primary-button>
    </form>
</x-guest-layout>
