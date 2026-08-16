<x-app-layout>

    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">
            Reportes de salidas
        </h2>
    </x-slot>

    {{-- =========================================================
         FILTRO PRINCIPAL
    ========================================================== --}}

    <form method="GET"
          action="{{ route('reportes.index') }}"
          class="glass-card p-4 mb-4 flex flex-wrap items-end gap-3">

        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">
                Desde
            </label>

            <input type="date"
                   name="desde"
                   value="{{ $desde }}"
                   class="input-glass !py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">
                Hasta
            </label>

            <input type="date"
                   name="hasta"
                   value="{{ $hasta }}"
                   class="input-glass !py-2 text-sm">
        </div>

        <button type="submit"
                class="btn-primary text-sm">
            Filtrar
        </button>

        <span class="text-xs text-gray-400 ml-auto self-center">
            {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }}
            —
            {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
        </span>

    </form>


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
                <p class="text-2xl font-extrabold text-gray-800">
                    {{ $totalSalidas }}
                </p>

                <p class="text-xs text-gray-500">
                    Salidas en el rango
                </p>
            </div>

        </div>


        {{-- Trabajadores --}}
        <div class="glass-card p-4 flex items-center gap-3">

            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
                🧑‍💼
            </div>

            <div>
                <p class="text-2xl font-extrabold text-gray-800">
                    {{ $rankingTrabajadores->count() }}
                </p>

                <p class="text-xs text-gray-500">
                    Trabajadores con salidas
                </p>
            </div>

        </div>


        {{-- Horas --}}
        <div class="glass-card p-4 flex items-center gap-3">

            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
                ⏱️
            </div>

            <div>
                <p class="text-2xl font-extrabold text-gray-800">
                    {{ number_format($horasPorMotivo->sum('horas'), 1) }}h
                </p>

                <p class="text-xs text-gray-500">
                    Horas contabilizadas
                </p>
            </div>

        </div>

    </div>


    {{-- =========================================================
         DESTACADOS
    ========================================================== --}}

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 stagger">

        {{-- Trabajador con más salidas --}}
        <div class="glass-panel p-5 animate-fade-in-up">

            <p class="text-xs text-gray-500 mb-2">
                Trabajador que más solicitó
            </p>

            @if($rankingTrabajadores->isNotEmpty())

                @php
                    $top = $rankingTrabajadores->first();
                @endphp

                <p class="text-lg font-extrabold text-gray-800">
                    {{ $top['nombre'] }}
                </p>

                <p class="text-xs text-gray-500 mt-1">
                    {{ $top['area'] }}
                    · Jefe: {{ $top['jefe'] }}
                </p>

                <p class="text-sm font-bold text-brand-700 mt-2">
                    {{ $top['total'] }} salidas
                </p>

            @else

                <p class="text-sm text-gray-400">
                    Sin datos en este rango.
                </p>

            @endif

        </div>


        {{-- Área más solicitada --}}
        <div class="glass-panel p-5 animate-fade-in-up">

            <p class="text-xs text-gray-500 mb-2">
                Área más solicitada
            </p>

            @if($rankingAreas->isNotEmpty())

                @php
                    $topArea = $rankingAreas->first();
                @endphp

                <p class="text-lg font-extrabold text-gray-800">
                    {{ $topArea['nombre'] }}
                </p>

                <p class="text-sm font-bold text-purple-700 mt-2">
                    {{ $topArea['total'] }} salidas
                </p>

            @else

                <p class="text-sm text-gray-400">
                    Sin datos en este rango.
                </p>

            @endif

        </div>


        {{-- Más horas fuera --}}
        <div class="glass-panel p-5 animate-fade-in-up">

            <p class="text-xs text-gray-500 mb-2">
                Trabajador con más horas afuera
            </p>

            @if($rankingHorasFuera->isNotEmpty())

                @php
                    $topHoras = $rankingHorasFuera->first();
                @endphp

                <p class="text-lg font-extrabold text-gray-800">
                    {{ $topHoras['nombre'] }}
                </p>

                <p class="text-xs text-gray-500 mt-1">
                    {{ $topHoras['area'] }}
                    · Jefe: {{ $topHoras['jefe'] }}
                </p>

                <p class="text-sm font-bold text-emerald-700 mt-2">
                    {{ number_format($topHoras['horas'], 1) }}h fuera
                </p>

            @else

                <p class="text-sm text-gray-400">
                    Sin datos en este rango.
                </p>

            @endif

        </div>

    </div>


    {{-- =========================================================
         GRÁFICOS
    ========================================================== --}}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Trabajadores --}}
        <div class="glass-panel p-5 animate-fade-in-up">

            <h3 class="font-semibold text-sm text-gray-700 mb-4">
                Trabajadores con más salidas
            </h3>

            @if($rankingTrabajadores->isNotEmpty())

                <canvas id="chartTrabajadores"
                        height="{{ max(120, $rankingTrabajadores->count() * 32) }}">
                </canvas>

            @else

                <p class="text-sm text-gray-400">
                    Sin salidas registradas en este rango.
                </p>

            @endif

        </div>


        {{-- Áreas --}}
        <div class="glass-panel p-5 animate-fade-in-up">

            <h3 class="font-semibold text-sm text-gray-700 mb-4">
                Áreas con más salidas solicitadas
            </h3>

            @if($rankingAreas->isNotEmpty())

                <canvas id="chartAreas"
                        height="{{ max(120, $rankingAreas->count() * 32) }}">
                </canvas>

            @else

                <p class="text-sm text-gray-400">
                    Sin salidas registradas en este rango.
                </p>

            @endif

        </div>

    </div>


    {{-- =========================================================
         MOTIVOS + HORAS
    ========================================================== --}}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Motivos --}}
        <div class="glass-panel p-5 animate-fade-in-up">

            <h3 class="font-semibold text-sm text-gray-700 mb-4">
                Motivos más usados
            </h3>

            @if($motivosMasUsados->isNotEmpty())

                <canvas id="chartMotivos"
                        height="{{ max(120, $motivosMasUsados->count() * 32) }}">
                </canvas>

            @else

                <p class="text-sm text-gray-400">
                    Sin salidas registradas en este rango.
                </p>

            @endif

        </div>


        {{-- Horas fuera --}}
        <div class="glass-panel p-5 animate-fade-in-up">

            <h3 class="font-semibold text-sm text-gray-700 mb-1">
                Trabajadores con más horas fuera
            </h3>

            <p class="text-xs text-gray-400 mb-4">
                Desde la marcación de salida hasta el retorno.
            </p>

            @forelse($rankingHorasFuera as $fila)

                <div class="flex items-center gap-3 py-2.5 border-b border-white/50 last:border-0">

                    <div class="min-w-0 flex-1">

                        <p class="text-sm text-gray-700 truncate">
                            {{ $fila['nombre'] }}
                        </p>

                        <p class="text-xs text-gray-400 truncate">
                            {{ $fila['area'] }}
                            · Jefe: {{ $fila['jefe'] }}
                        </p>

                    </div>

                    <span class="text-sm font-bold text-gray-800 shrink-0">
                        {{ number_format($fila['horas'], 1) }}h
                    </span>

                </div>

            @empty

                <p class="text-sm text-gray-400">
                    Sin marcaciones de salida en este rango.
                </p>

            @endforelse

        </div>

    </div>


    {{-- =========================================================
         HORAS POR MOTIVO
    ========================================================== --}}

    <div class="glass-panel p-5 mb-4 animate-fade-in-up">

        <h3 class="font-semibold text-sm text-gray-700 mb-1">
            Horas de salida contabilizadas por motivo
        </h3>

        <p class="text-xs text-gray-400 mb-4">
            Solo incluye salidas con salida y retorno registrados.
        </p>

        @forelse($horasPorMotivo as $fila)

            <div class="flex items-center gap-3 py-2.5 border-b border-white/50 last:border-0">

                <span class="text-sm text-gray-700 flex-1">
                    {{ $fila['nombre'] }}
                </span>

                <span class="text-xs text-gray-400">
                    {{ $fila['salidas_contabilizadas'] }} salidas
                </span>

                <span class="text-sm font-bold text-gray-800 w-16 text-right">
                    {{ number_format($fila['horas'], 1) }}h
                </span>

            </div>

        @empty

            <p class="text-sm text-gray-400">
                Aún no hay salidas con retorno registrado en este rango.
            </p>

        @endforelse

    </div>


    {{-- =========================================================
         DETALLE DE SALIDAS
    ========================================================== --}}

    <div class="glass-panel p-5 mb-4 animate-fade-in-up">

        <h3 class="font-semibold text-sm text-gray-700 mb-4">
            Detalle de salidas
        </h3>


        {{-- Filtros del detalle --}}
        <form method="GET"
              action="{{ route('reportes.index') }}"
              class="flex flex-wrap items-end gap-3 mb-4">

            <input type="hidden"
                   name="desde"
                   value="{{ $desde }}">

            <input type="hidden"
                   name="hasta"
                   value="{{ $hasta }}">


            {{-- Buscar --}}
            <div class="flex-1 min-w-[160px]">

                <label class="block text-xs font-semibold text-gray-500 mb-1">
                    Buscar
                </label>

                <input type="text"
                       name="buscar"
                       value="{{ $filtrosDetalle['buscar'] ?? '' }}"
                       placeholder="Código, destino o trabajador"
                       class="input-glass !py-2 text-sm w-full">

            </div>


            {{-- Estado --}}
            <div>

                <label class="block text-xs font-semibold text-gray-500 mb-1">
                    Estado
                </label>

                <select name="estado_id"
                        class="input-glass !py-2 text-sm">

                    <option value="">
                        Todos
                    </option>

                    @foreach($estados as $estado)

                        <option value="{{ $estado->id }}"
                            @selected(($filtrosDetalle['estado_id'] ?? null) == $estado->id)>
                            {{ $estado->nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Área --}}
            <div>

                <label class="block text-xs font-semibold text-gray-500 mb-1">
                    Área
                </label>

                <select name="area_id"
                        class="input-glass !py-2 text-sm">

                    <option value="">
                        Todas
                    </option>

                    @foreach($areas as $area)

                        <option value="{{ $area->id }}"
                            @selected(($filtrosDetalle['area_id'] ?? null) == $area->id)>
                            {{ $area->nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <button type="submit"
                    class="btn-secondary text-sm">
                Filtrar
            </button>

        </form>


        {{-- =====================================================
             TABLA ESCRITORIO
        ====================================================== --}}

        <div class="hidden md:block overflow-x-auto">

            <table class="w-full text-sm">

                <thead>

                    <tr class="text-left text-xs text-gray-500 border-b border-white/60">

                        <th class="py-2 pr-3 font-semibold">
                            Código
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Trabajador
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Área
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Jefe
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Motivo
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Fecha
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Salida real
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Retorno real
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Horas fuera
                        </th>

                        <th class="py-2 pr-3 font-semibold">
                            Estado
                        </th>

                        <th class="py-2 pr-0 font-semibold text-right">
                            Acción
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($detalleSalidas as $papeleta)

                        @php

                            $marcSalida =
                                $papeleta->marcaciones
                                    ->firstWhere('tipo', 'SALIDA');

                            $marcRetorno =
                                $papeleta->marcaciones
                                    ->firstWhere('tipo', 'RETORNO');

                            $horasFuera = null;

                            if ($marcSalida) {

                                $fin =
                                    $marcRetorno?->created_at
                                    ?? now();

                                $horasFuera =
                                    round(
                                        $marcSalida->created_at
                                            ->diffInMinutes($fin) / 60,
                                        1
                                    );

                            }

                        @endphp


                        <tr class="border-b border-white/40 last:border-0">

                            <td class="py-2.5 pr-3 font-semibold text-gray-800">
                                {{ $papeleta->codigo }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-700">
                                {{ $papeleta->trabajador?->name ?? '—' }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $papeleta->area?->nombre ?? '—' }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $papeleta->jefe?->name ?? '—' }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $papeleta->motivo?->nombre ?? '—' }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $papeleta->fecha_salida->format('d/m/Y') }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $marcSalida?->created_at->format('H:i') ?? '—' }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $marcRetorno?->created_at->format('H:i') ?? '—' }}
                            </td>

                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $horasFuera !== null ? number_format($horasFuera, 1).'h' : '—' }}
                            </td>

                            <td class="py-2.5 pr-3">

                                <x-status-badge
                                    :estado="$papeleta->estado"
                                    :detalle="$papeleta->etiquetaVencimiento()" />

                            </td>

                            <td class="py-2.5 pr-0 text-right">

                                <a href="{{ route('papeletas.show', $papeleta) }}"
                                   class="btn-primary !px-3 !py-1.5 text-xs whitespace-nowrap">

                                    Ver papeleta

                                </a>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="11"
                                class="py-6 text-center text-gray-400">

                                No hay salidas que coincidan con estos filtros.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =====================================================
             CARDS MÓVIL
        ====================================================== --}}

        <div class="md:hidden space-y-3">

            @forelse($detalleSalidas as $papeleta)

                @php

                    $marcSalida =
                        $papeleta->marcaciones
                            ->firstWhere('tipo', 'SALIDA');

                    $marcRetorno =
                        $papeleta->marcaciones
                            ->firstWhere('tipo', 'RETORNO');

                    $horasFuera = null;

                    if ($marcSalida) {

                        $fin =
                            $marcRetorno?->created_at
                            ?? now();

                        $horasFuera =
                            round(
                                $marcSalida->created_at
                                    ->diffInMinutes($fin) / 60,
                                1
                            );

                    }

                @endphp


                <div class="glass-card p-4 border-l-4"
                     style="border-left-color: {{ $papeleta->estado->color }}">

                    <div class="flex items-start justify-between gap-2 mb-1">

                        <div class="min-w-0">

                            <p class="font-semibold text-sm text-gray-800">
                                {{ $papeleta->codigo }}
                            </p>

                            <p class="text-xs text-gray-500">

                                {{ $papeleta->trabajador?->name ?? '—' }}

                                ·

                                {{ $papeleta->area?->nombre ?? '—' }}

                            </p>

                        </div>


                        <x-status-badge
                            :estado="$papeleta->estado"
                            :detalle="$papeleta->etiquetaVencimiento()" />

                    </div>


                    <p class="text-xs text-gray-400">

                        Jefe:
                        {{ $papeleta->jefe?->name ?? '—' }}

                        · Motivo:
                        {{ $papeleta->motivo?->nombre ?? '—' }}

                    </p>


                    <p class="text-xs text-gray-400 mt-1">

                        {{ $papeleta->fecha_salida->format('d/m/Y') }}

                        · Salida:
                        {{ $marcSalida?->created_at->format('H:i') ?? '—' }}

                        · Retorno:
                        {{ $marcRetorno?->created_at->format('H:i') ?? '—' }}

                        @if($horasFuera !== null)

                            · {{ number_format($horasFuera, 1) }}h fuera

                        @endif

                    </p>


                    <a href="{{ route('papeletas.show', $papeleta) }}"
                       class="btn-primary !py-1.5 text-xs mt-3 inline-block">

                        Ver papeleta

                    </a>

                </div>


            @empty

                <p class="text-sm text-gray-400 text-center py-6">

                    No hay salidas que coincidan con estos filtros.

                </p>

            @endforelse

        </div>


        {{-- Paginación --}}
        <div class="mt-4">

            {{ $detalleSalidas->links() }}

        </div>

    </div>


    {{-- =========================================================
         CHART.JS
    ========================================================== --}}

    @push('scripts')

        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"
            crossorigin="anonymous">
        </script>


        <script>

            document.addEventListener('DOMContentLoaded', () => {

                const trabajadores =
                    @json($rankingTrabajadores->values());

                const areas =
                    @json($rankingAreas->values());

                const motivos =
                    @json($motivosMasUsados->values());


                function crearGrafico(
                    id,
                    datos,
                    color,
                    etiqueta
                ) {

                    const canvas =
                        document.getElementById(id);

                    if (!canvas || !datos.length) {
                        return;
                    }


                    new Chart(canvas, {

                        type: 'bar',

                        data: {

                            labels:
                                datos.map(
                                    item => item.nombre
                                ),

                            datasets: [{

                                label: etiqueta,

                                data:
                                    datos.map(
                                        item => item.total
                                    ),

                                backgroundColor: color,

                                borderRadius: 6

                            }]

                        },


                        options: {

                            indexAxis: 'y',

                            responsive: true,

                            plugins: {

                                legend: {
                                    display: false
                                }

                            },

                            scales: {

                                x: {

                                    beginAtZero: true,

                                    ticks: {
                                        precision: 0
                                    }

                                }

                            }

                        }

                    });

                }


                crearGrafico(
                    'chartTrabajadores',
                    trabajadores,
                    '#2549ea',
                    'Salidas'
                );


                crearGrafico(
                    'chartAreas',
                    areas,
                    '#a855f7',
                    'Salidas'
                );


                crearGrafico(
                    'chartMotivos',
                    motivos,
                    '#22c55e',
                    'Salidas'
                );

            });

        </script>

    @endpush

</x-app-layout>