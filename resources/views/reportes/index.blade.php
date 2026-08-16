<x-app-layout>

    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">
            Reportes de salidas
        </h2>
    </x-slot>

    @include('reportes.partials.index._filtro-principal')
    @include('reportes.partials.index._resumen')
    @include('reportes.partials.index._destacados')
    @include('reportes.partials.index._graficos-trabajadores-areas')
    @include('reportes.partials.index._motivos-horas-fuera')
    @include('reportes.partials.index._horas-por-motivo')
    @include('reportes.partials.index._detalle-salidas')
    @include('reportes.partials.index._scripts-chart')

</x-app-layout>
