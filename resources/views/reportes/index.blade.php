<x-app-layout>

    {{-- El título va dentro de la barra de filtros (_barra-filtros), junto
         con la descripción del alcance (global/equipo), para no repetirlo.
         "tab" vive acá arriba porque lo comparten: la barra de filtros
         (hidden que preserva la pestaña al recargar), el nav de pestañas
         y los 4 paneles. Se inicializa desde ?tab= para que la pestaña
         activa sobreviva a un filtrado o a compartir el link. --}}
    <div x-data="{ tab: (new URLSearchParams(window.location.search).get('tab')) || 'resumen' }">

        @include('reportes.partials.index._barra-filtros')
        @include('reportes.partials.index._resumen')
        @include('reportes.partials.index._tabs-nav')

        @include('reportes.partials.index._panel-resumen')
        @include('reportes.partials.index._panel-trabajador')
        @include('reportes.partials.index._panel-area-motivo')
        @include('reportes.partials.index._panel-detalle')

    </div>

    @include('reportes.partials.index._scripts-chart')

</x-app-layout>
