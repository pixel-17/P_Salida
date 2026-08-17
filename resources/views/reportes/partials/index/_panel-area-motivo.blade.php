{{-- =========================================================
     PESTAÑA: POR ÁREA Y MOTIVO
     Los gráficos de esta pestaña (chartAreas, chartMotivos) se
     crean de forma perezosa la primera vez que se abre la
     pestaña — ver _scripts-chart.blade.php — porque un canvas
     oculto con display:none no puede medirse al cargar la
     página.
========================================================== --}}
<div x-show="tab === 'area_motivo'" x-cloak
     x-effect="if (tab === 'area_motivo' && window.dibujarGraficosAreaMotivo) window.dibujarGraficosAreaMotivo()">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Áreas --}}
        <div class="glass-panel p-5 animate-fade-in-up">
            <h3 class="font-semibold text-sm text-gray-700 mb-4">Áreas con más salidas solicitadas</h3>

            @if($rankingAreas->isNotEmpty())
                <canvas id="chartAreas" height="{{ max(140, $rankingAreas->count() * 32) }}"></canvas>

                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-white/60">
                                <th class="py-2 pr-3 font-semibold">#</th>
                                <th class="py-2 pr-3 font-semibold">Área</th>
                                <th class="py-2 pr-0 font-semibold text-right">Salidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rankingAreas as $fila)
                                <tr class="border-b border-white/40 last:border-0">
                                    <td class="py-2 pr-3 text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="py-2 pr-3 text-gray-700 font-semibold">{{ $fila['nombre'] }}</td>
                                    <td class="py-2 pr-0 text-right font-bold text-gray-800">{{ $fila['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-400">Sin salidas registradas en este rango.</p>
            @endif
        </div>

        {{-- Motivos --}}
        <div class="glass-panel p-5 animate-fade-in-up">
            <h3 class="font-semibold text-sm text-gray-700 mb-4">Motivos más usados</h3>

            @if($motivosMasUsados->isNotEmpty())
                <canvas id="chartMotivos" height="{{ max(120, $motivosMasUsados->count() * 32) }}"></canvas>
            @else
                <p class="text-sm text-gray-400">Sin salidas registradas en este rango.</p>
            @endif
        </div>
    </div>

    @include('reportes.partials.index._horas-por-motivo')
</div>
