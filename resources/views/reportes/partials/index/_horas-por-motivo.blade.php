{{-- =========================================================
     HORAS POR MOTIVO
========================================================== --}}
<div class="glass-panel p-5 mb-4 animate-fade-in-up">
    <h3 class="font-semibold text-sm text-gray-700 mb-1">Horas de salida contabilizadas por motivo</h3>
    <p class="text-xs text-gray-400 mb-4">Solo incluye salidas con salida y retorno registrados.</p>

    @forelse($horasPorMotivo as $fila)
        <div class="flex items-center gap-3 py-2.5 border-b border-white/50 last:border-0">
            <span class="text-sm text-gray-700 flex-1">{{ $fila['nombre'] }}</span>
            <span class="text-xs text-gray-400">{{ $fila['salidas_contabilizadas'] }} salidas</span>
            <span class="text-sm font-bold text-gray-800 w-16 text-right">{{ number_format($fila['horas'], 1) }}h</span>
        </div>
    @empty
        <p class="text-sm text-gray-400">Aún no hay salidas con retorno registrado en este rango.</p>
    @endforelse
</div>
