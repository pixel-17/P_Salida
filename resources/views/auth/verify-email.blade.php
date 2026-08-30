<x-guest-layout>
    <div class="mb-7 text-center">
        <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Verifica tu correo</h1>
        <p class="text-sm text-gray-500 mt-2 leading-relaxed">
            Antes de continuar, confirma tu correo haciendo clic en el enlace que te
            enviamos. Si no lo recibiste, con gusto te enviamos otro.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-5 font-medium text-sm text-emerald-700 bg-emerald-50/80 rounded-xl px-4 py-2.5">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
            @csrf
            <x-primary-button class="w-full sm:w-auto !py-3">
                Reenviar correo de verificación
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
