{{-- =========================================================
     HORAS POR MOTIVO
========================================================== --}}
<div class="glass-panel p-5 mb-4 animate-fade-in-up">
    <h3 class="font-semibold text-sm text-gray-700 mb-1">Horas de salida contabilizadas por motivo</h3>
    <p class="text-xs text-gray-400 mb-4">Solo incluye salidas con salida y retorno registrados.</p>

    @php $maxHoras = $horasPorMotivo->max('horas') ?: 1; @endphp

    @forelse($horasPorMotivo as $fila)
        <div class="py-2.5 border-b border-white/50 last:border-0">
            <div class="flex items-center gap-3 mb-1.5">
                <span class="text-sm text-gray-700 flex-1 truncate">{{ $fila['nombre'] }}</span>
                <span class="text-xs text-gray-400 whitespace-nowrap">{{ $fila['salidas_contabilizadas'] }} salidas</span>
                <span class="text-sm font-bold text-gray-800 w-16 text-right shrink-0">{{ number_format($fila['horas'], 1) }}h</span>
            </div>
            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-brand-400 to-brand-600 rounded-full"
                     style="width: {{ round($fila['horas'] / $maxHoras * 100) }}%"></div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">Aún no hay salidas con retorno registrado en este rango.</p>
    @endforelse
</div>
