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

    <div class="space-y-6 stagger max-w-2xl">
        <div class="glass-panel p-6 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="glass-panel p-6 sm:p-8">
            @include('profile.partials.update-password-form')
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

        <div class="glass-panel p-6 sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
