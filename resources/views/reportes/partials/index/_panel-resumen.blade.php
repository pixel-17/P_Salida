{{-- =========================================================
     PESTAÑA: RESUMEN
     Destacados (quién, no solo cuánto) + un único gráfico
     clave. El detalle tabulado completo vive en la pestaña
     "Por trabajador"; aquí es solo el vistazo ejecutivo.
========================================================== --}}
<div x-show="tab === 'resumen'" x-cloak>

    @include('reportes.partials.index._destacados')

    <div class="glass-panel p-5 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-1">Top 10 trabajadores con más salidas</h3>
        <p class="text-xs text-gray-400 mb-4">
            Detalle completo, buscable y ordenable, en la pestaña "Por trabajador".
        </p>

        @if($rankingTrabajadores->isNotEmpty())
            <canvas id="chartTrabajadores" height="{{ max(140, $rankingTrabajadores->count() * 32) }}"></canvas>
        @else
            <p class="text-sm text-gray-400">Sin salidas registradas en este rango.</p>
        @endif
    </div>
</div>
