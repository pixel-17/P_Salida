{{-- =========================================================
     RESUMEN
========================================================== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4 stagger">

    {{-- Total salidas --}}
    <div class="glass-card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
            📄
        </div>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold text-gray-800">{{ $totalSalidas }}</p>
            <p class="text-xs text-gray-500">Salidas en el rango</p>
        </div>
    </div>

    {{-- Trabajadores --}}
    <div class="glass-card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
            🧑‍💼
        </div>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold text-gray-800">{{ $cuadroTrabajadores->count() }}</p>
            <p class="text-xs text-gray-500">Trabajadores con salidas</p>
        </div>
    </div>

    {{-- Horas --}}
    <div class="glass-card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
            ⏱️
        </div>
        <div class="min-w-0">
            <p class="text-2xl font-extrabold text-gray-800">{{ number_format($horasPorMotivo->sum('horas'), 1) }}h</p>
            <p class="text-xs text-gray-500">Horas contabilizadas</p>
        </div>
    </div>

    {{-- Motivo más frecuente (general) --}}
    <div class="glass-card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
            🏷️
        </div>
        <div class="min-w-0">
            <p class="text-lg font-extrabold text-gray-800 truncate">{{ $motivoGeneralTop['nombre'] ?? '—' }}</p>
            <p class="text-xs text-gray-500">
                Motivo más frecuente
                @if(isset($motivoGeneralTop['total']))
                    · {{ $motivoGeneralTop['total'] }} salidas
                @endif
            </p>
        </div>
    </div>
</div>
