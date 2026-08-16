<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <x-application-logo class="w-7 h-7 fill-current text-brand-600" />
            <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Control de puerta</h2>
        </div>
    </x-slot>

    {{-- Domingo: nada de escanear/buscar/confirmar, solo este aviso. La
    garita vuelve a funcionar sola cuando cambie el día (no hay nada que
    "reactivar" a mano). --}}
    <div class="max-w-xl mx-auto">
        <div class="glass-card p-10 text-center animate-fade-in-up">
            <div class="w-20 h-20 mx-auto rounded-3xl glass-strong flex items-center justify-center shadow-glass-lg mb-6 animate-float">
                <span class="text-4xl">😌</span>
            </div>

            <h1 class="text-xl font-extrabold text-gray-800 mb-2">Buen domingo, descanse</h1>
            <p class="text-sm text-gray-500 leading-relaxed mb-1">
                Hoy es día no laborable, así que la garita está deshabilitada.
            </p>
            <p class="text-sm text-gray-500 leading-relaxed">
                No se pueden escanear QR, buscar papeletas ni confirmar salidas o retornos hasta el próximo día laborable.
            </p>
        </div>
    </div>
</x-app-layout>
