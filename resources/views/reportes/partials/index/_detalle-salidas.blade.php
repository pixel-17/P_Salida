{{-- =========================================================
     DETALLE DE SALIDAS
========================================================== --}}
<div class="glass-panel p-5 mb-4 animate-fade-in-up">
    <h3 class="font-semibold text-sm text-gray-700 mb-4">Detalle de salidas</h3>

    @include('reportes.partials.index._detalle-filtro')
    @include('reportes.partials.index._detalle-tabla-desktop')
    @include('reportes.partials.index._detalle-cards-movil')

    {{-- Paginación --}}
    <div class="mt-4">
        {{ $detalleSalidas->links() }}
    </div>
</div>
