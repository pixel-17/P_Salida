{{-- =========================================================
     PESTAÑA: DETALLE
     El filtro (buscar/estado/área/fechas) ya vive en la barra
     superior única — aquí solo la tabla resultante.
========================================================== --}}
<div x-show="tab === 'detalle'" x-cloak>
    <div class="glass-panel p-5 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-4">
            Detalle de salidas
            <span class="text-xs font-normal text-gray-400">
                · {{ $detalleSalidas->total() }} {{ Str::plural('resultado', $detalleSalidas->total()) }}
            </span>
        </h3>

        @include('reportes.partials.index._detalle-tabla-desktop')
        @include('reportes.partials.index._detalle-cards-movil')

        <div class="mt-4">
            {{ $detalleSalidas->links() }}
        </div>
    </div>
</div>
