{{-- =========================================================
     MOTIVOS + HORAS FUERA
========================================================== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- Motivos --}}
    <div class="glass-panel p-5 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-4">Motivos más usados</h3>

        @if($motivosMasUsados->isNotEmpty())
            <canvas id="chartMotivos" height="{{ max(120, $motivosMasUsados->count() * 32) }}"></canvas>
        @else
            <p class="text-sm text-gray-400">Sin salidas registradas en este rango.</p>
        @endif
    </div>

    {{-- Horas fuera --}}
    <div class="glass-panel p-5 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-1">Trabajadores con más horas fuera</h3>
        <p class="text-xs text-gray-400 mb-4">
            Desde que el vigilante marca la salida en garita hasta que marca el retorno.
        </p>

        @forelse($rankingHorasFuera as $fila)
            @if($loop->first)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-white/60">
                                <th class="py-2 pr-3 font-semibold">#</th>
                                <th class="py-2 pr-3 font-semibold">Trabajador</th>
                                <th class="py-2 pr-3 font-semibold">Área</th>
                                <th class="py-2 pr-3 font-semibold">Jefe</th>
                                <th class="py-2 pr-0 font-semibold text-right">Horas fuera</th>
                            </tr>
                        </thead>
                        <tbody>
            @endif

                            <tr class="border-b border-white/40 last:border-0">
                                <td class="py-2 pr-3 text-gray-400">{{ $loop->iteration }}</td>
                                <td class="py-2 pr-3 text-gray-700 font-semibold truncate">{{ $fila['nombre'] }}</td>
                                <td class="py-2 pr-3 text-gray-600 truncate">{{ $fila['area'] }}</td>
                                <td class="py-2 pr-3 text-gray-600 truncate">{{ $fila['jefe'] }}</td>
                                <td class="py-2 pr-0 text-right font-bold text-gray-800">{{ number_format($fila['horas'], 1) }}h</td>
                            </tr>

            @if($loop->last)
                        </tbody>
                    </table>
                </div>
            @endif
        @empty
            <p class="text-sm text-gray-400">Sin marcaciones de salida en este rango.</p>
        @endforelse
    </div>
</div>
