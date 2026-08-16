{{-- =========================================================
     RESUMEN
========================================================== --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 stagger">

    {{-- Total salidas --}}
    <div class="glass-card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
            📄
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-800">{{ $totalSalidas }}</p>
            <p class="text-xs text-gray-500">Salidas en el rango</p>
        </div>
    </div>

    {{-- Trabajadores --}}
    <div class="glass-card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
            🧑‍💼
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-800">{{ $rankingTrabajadores->count() }}</p>
            <p class="text-xs text-gray-500">Trabajadores con salidas</p>
        </div>
    </div>

    {{-- Horas --}}
    <div class="glass-card p-4 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
            ⏱️
        </div>
        <div>
            <p class="text-2xl font-extrabold text-gray-800">{{ number_format($horasPorMotivo->sum('horas'), 1) }}h</p>
            <p class="text-xs text-gray-500">Horas contabilizadas</p>
        </div>
    </div>
</div>
