<x-app-layout>

    {{-- El título va dentro de la tarjeta de encabezado (_filtro-principal),
         junto con la descripción del alcance (global/equipo) y los filtros,
         para no repetirlo dos veces en la misma pantalla. --}}

    @include('reportes.partials.index._filtro-principal')
    @include('reportes.partials.index._resumen')
    @include('reportes.partials.index._destacados')
    @include('reportes.partials.index._cuadro-trabajadores')
    @include('reportes.partials.index._graficos-trabajadores-areas')
    @include('reportes.partials.index._motivos-horas-fuera')
    @include('reportes.partials.index._horas-por-motivo')
    @include('reportes.partials.index._detalle-salidas')
    @include('reportes.partials.index._scripts-chart')

</x-app-layout>
