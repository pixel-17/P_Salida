{{-- =========================================================
     GRÁFICOS: TRABAJADORES + ÁREAS
     El detalle tabulado de trabajadores vive en el cuadro
     dinámico de arriba; aquí solo el vistazo visual rápido.
========================================================== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- Trabajadores --}}
    <div class="glass-panel p-5 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-4">Top 10 trabajadores con más salidas</h3>

        @if($rankingTrabajadores->isNotEmpty())
            <canvas id="chartTrabajadores" height="{{ max(140, $rankingTrabajadores->count() * 32) }}"></canvas>
        @else
            <p class="text-sm text-gray-400">Sin salidas registradas en este rango.</p>
        @endif
    </div>

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
</div>
