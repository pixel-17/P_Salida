<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="font-bold text-2xl text-gray-800 tracking-tight">{{ $papeleta->codigo }}</h2>
            <a href="{{ route('papeletas.pdf', $papeleta) }}"
               target="_blank"
               class="btn-glass text-white shadow-glass !px-4 !py-2" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6 12l-3-3m0 0l-3 3m3-3v6" />
                </svg>
                Descargar PDF
            </a>
        </div>
    </x-slot>

    @php
        $estadosFinales = [
            \App\Enums\EstadoPapeleta::FINALIZADO->value,
            \App\Enums\EstadoPapeleta::RECHAZADO->value,
            \App\Enums\EstadoPapeleta::VENCIDA->value,
            \App\Enums\EstadoPapeleta::CANCELADO->value,
        ];
        $sigueViva = !in_array($papeleta->estado->codigo, $estadosFinales, true);
    @endphp

    @if($sigueViva)
        @include('papeletas.partials.show._live-badge')
    @endif

    <div id="detalle-papeleta">
        @include('papeletas.partials.show._resumen')
        @include('papeletas.partials.show._cancelar')
        @include('papeletas.partials.show._qr')
        @include('papeletas.partials.show._stepper')
        @include('papeletas.partials.show._espera')
        @include('papeletas.partials.show._adjuntos')
        @include('papeletas.partials.show._observaciones')
        @include('papeletas.partials.show._acciones')
        @include('papeletas.partials.show._marcaciones')
        @include('papeletas.partials.show._historial')
    </div>{{-- /#detalle-papeleta --}}

    @include('papeletas.partials.show._ajax-forms')
</x-app-layout>
