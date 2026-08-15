<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Reportes de salidas</h2>
    </x-slot>

    {{-- ---------- Filtro de rango de fechas ---------- --}}
    <form method="GET" action="{{ route('reportes.index') }}" class="glass-card p-4 mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ $desde }}" class="input-glass !py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}" class="input-glass !py-2 text-sm">
        </div>
        <button type="submit" class="btn-primary text-sm">Filtrar</button>
        <span class="text-xs text-gray-400 ml-auto self-center">
            {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
        </span>
    </form>

    {{-- ---------- Resumen ---------- --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 stagger">
        <div class="glass-card p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">📄</div>
            <div>
                <p class="text-2xl font-extrabold text-gray-800">{{ $totalSalidas }}</p>
                <p class="text-xs text-gray-500">Salidas en el rango</p>
            </div>
        </div>
        <div class="glass-card p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">🧑‍💼</div>
            <div>
                <p class="text-2xl font-extrabold text-gray-800">{{ $rankingTrabajadores->count() }}</p>
                <p class="text-xs text-gray-500">Trabajadores con salidas</p>
            </div>
        </div>
        <div class="glass-card p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">⏱️</div>
            <div>
                <p class="text-2xl font-extrabold text-gray-800">{{ number_format($horasPorMotivo->sum('horas'), 1) }}h</p>
                <p class="text-xs text-gray-500">Horas contabilizadas (salida+retorno registrados)</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        {{-- ---------- Ranking de trabajadores ---------- --}}
        <div class="glass-panel p-5 animate-fade-in-up">
            <h3 class="font-semibold text-sm text-gray-700 mb-4">Trabajadores con más salidas</h3>
            <canvas id="chartTrabajadores" height="{{ max(120, $rankingTrabajadores->count() * 32) }}"></canvas>
            @if($rankingTrabajadores->isEmpty())
                <p class="text-sm text-gray-400 mt-2">Sin salidas registradas en este rango.</p>
            @endif
        </div>

        {{-- ---------- Motivos más usados ---------- --}}
        <div class="glass-panel p-5 animate-fade-in-up">
            <h3 class="font-semibold text-sm text-gray-700 mb-4">Motivos más usados</h3>
            <canvas id="chartMotivos" height="{{ max(120, $motivosMasUsados->count() * 32) }}"></canvas>
            @if($motivosMasUsados->isEmpty())
                <p class="text-sm text-gray-400 mt-2">Sin salidas registradas en este rango.</p>
            @endif
        </div>
    </div>

    {{-- ---------- Horas por motivo ---------- --}}
    <div class="glass-panel p-5 mb-4 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-1">Horas de salida contabilizadas por motivo</h3>
        <p class="text-xs text-gray-400 mb-4">Solo incluye salidas con marcación de salida y retorno registradas por el vigilante.</p>

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

    @push('scripts')
        {{-- Cargado solo en esta página, mismo criterio que admin.dashboard. --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const trabajadores = @json($rankingTrabajadores);
                const motivos = @json($motivosMasUsados);

                if (trabajadores.length) {
                    new Chart(document.getElementById('chartTrabajadores'), {
                        type: 'bar',
                        data: {
                            labels: trabajadores.map(t => t.nombre),
                            datasets: [{
                                label: 'Salidas',
                                data: trabajadores.map(t => t.total),
                                backgroundColor: '#2549ea',
                                borderRadius: 6,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                        },
                    });
                }

                if (motivos.length) {
                    new Chart(document.getElementById('chartMotivos'), {
                        type: 'bar',
                        data: {
                            labels: motivos.map(m => m.nombre),
                            datasets: [{
                                label: 'Salidas',
                                data: motivos.map(m => m.total),
                                backgroundColor: '#22c55e',
                                borderRadius: 6,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                        },
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
