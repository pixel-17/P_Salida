<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-800 tracking-tight">
                Mi perfil
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Gestiona tu información personal y la seguridad de tu cuenta.
            </p>
        </div>
    </x-slot>

    {{-- No hay opción de "Eliminar cuenta" acá para ningún rol, sin
         excepción: eliminar cuentas es una acción exclusiva de
         Administrador desde el panel de usuarios (users.destroy),
         donde queda con soft-delete y no rompe el historial de
         papeletas. Self-service delete fue removido a propósito. --}}
    <div class="space-y-6 stagger max-w-2xl">
        <div class="glass-panel p-6 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="glass-panel p-6 sm:p-8">
            @include('profile.partials.update-password-form')
        </div>

        @if ($user->jefe)
            <div class="glass-panel p-6 sm:p-8">
                <section class="flex items-center gap-3">
                    <x-icon name="user-circle" class="w-5 h-5 text-gray-400 shrink-0" />
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Tu jefe inmediato</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $user->jefe->name }}
                            @if ($user->jefe->sede)
                                — {{ $user->jefe->sede->nombre }}
                            @endif
                        </p>
                    </div>
                </section>
            </div>
        @endif

        <div class="glass-panel p-6 sm:p-8">
            @include('profile.partials.notification-settings')
        </div>

        <div class="glass-panel p-6 sm:p-8">
            <section class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Cerrar sesión</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Cierra tu sesión en este dispositivo.
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <x-icon name="logout" class="w-4 h-4" />
                        Cerrar sesión
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
