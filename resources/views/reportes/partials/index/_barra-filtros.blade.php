{{-- =========================================================
     ENCABEZADO + BARRA DE FILTROS ÚNICA
     Un solo formulario controla rankings, gráficos y detalle a
     la vez (desde/hasta/buscar/estado_id/area_id). El botón
     "Exportar Excel" reutiliza los mismos filtros vía query
     string, así el archivo siempre coincide con lo que se ve
     en pantalla. El campo "tab" es un hidden sincronizado con
     Alpine para no perder la pestaña activa al filtrar.
========================================================== --}}
<div class="glass-card p-5 mb-4 animate-fade-in-up"
     x-data="{
        desde: '{{ $desde }}',
        hasta: '{{ $hasta }}',
        preset(dias) {
            const hoy = new Date();
            const hasta = new Date(hoy);
            const desde = new Date(hoy);
            if (dias === 'hoy') {
                // desde = hasta = hoy
            } else if (dias === 'semana') {
                desde.setDate(hoy.getDate() - hoy.getDay());
            } else if (dias === 'mes') {
                desde.setDate(1);
            } else if (dias === 'mes_pasado') {
                desde.setMonth(hoy.getMonth() - 1, 1);
                hasta.setDate(0);
            }
            this.desde = desde.toISOString().slice(0, 10);
            this.hasta = hasta.toISOString().slice(0, 10);
            $nextTick(() => $refs.formFiltros.submit());
        },
     }">

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white text-lg shrink-0 shadow-glass">
                📊
            </div>
            <div>
                <h1 class="font-extrabold text-lg text-gray-800 leading-tight">Reportes de salidas</h1>
                <p class="text-xs text-gray-500 mt-0.5 max-w-md">
                    @if($esSoloJefe)
                        Vista de tu equipo: solo las salidas de los trabajadores que te reportan.
                    @else
                        Vista global: todas las áreas y trabajadores de la empresa.
                    @endif
                </p>
            </div>
        </div>

        <span @class([
            'inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full self-start lg:self-auto border',
            'bg-purple-50 text-purple-700 border-purple-100' => $esSoloJefe,
            'bg-brand-50 text-brand-700 border-brand-100' => ! $esSoloJefe,
        ])>
            <span class="w-1.5 h-1.5 rounded-full {{ $esSoloJefe ? 'bg-purple-500' : 'bg-brand-500' }}"></span>
            {{ $esSoloJefe ? 'Mi equipo' : 'Todas las áreas' }}
        </span>
    </div>

    <form method="GET"
          action="{{ route('reportes.index') }}"
          x-ref="formFiltros"
          class="mt-5 pt-5 border-t border-white/60">

        <input type="hidden" name="tab" x-model="tab">

        {{-- Presets de rango rápido --}}
        <div class="flex flex-wrap items-center gap-1.5 mb-3">
            <span class="text-xs font-semibold text-gray-400 mr-1">Rango rápido:</span>
            <button type="button" @click="preset('hoy')" class="btn-secondary !px-3 !py-1.5 text-xs">Hoy</button>
            <button type="button" @click="preset('semana')" class="btn-secondary !px-3 !py-1.5 text-xs">Esta semana</button>
            <button type="button" @click="preset('mes')" class="btn-secondary !px-3 !py-1.5 text-xs">Este mes</button>
            <button type="button" @click="preset('mes_pasado')" class="btn-secondary !px-3 !py-1.5 text-xs">Mes pasado</button>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Desde</label>
                <input type="date" name="desde" x-model="desde" class="input-glass !py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Hasta</label>
                <input type="date" name="hasta" x-model="hasta" class="input-glass !py-2 text-sm">
            </div>

            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Buscar</label>
                <input type="text"
                       name="buscar"
                       value="{{ $filtrosDetalle['buscar'] ?? '' }}"
                       placeholder="Código, destino o trabajador"
                       class="input-glass !py-2 text-sm w-full">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Estado</label>
                <select name="estado_id" class="input-glass !py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado->id }}"
                            @selected(($filtrosDetalle['estado_id'] ?? null) == $estado->id)>
                            {{ $estado->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Área</label>
                <select name="area_id" class="input-glass !py-2 text-sm">
                    <option value="">Todas</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}"
                            @selected(($filtrosDetalle['area_id'] ?? null) == $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary text-sm">Filtrar</button>

            <a href="{{ route('reportes.exportar', array_filter(array_merge(['desde' => $desde, 'hasta' => $hasta], $filtrosDetalle))) }}"
               class="btn-secondary text-sm inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Exportar Excel
            </a>
        </div>

        @if(($filtrosDetalle['buscar'] ?? null) || ($filtrosDetalle['estado_id'] ?? null) || ($filtrosDetalle['area_id'] ?? null))
            <p class="text-xs text-gray-400 mt-3">
                Los filtros de búsqueda/estado/área se aplican a todo: rankings, gráficos y detalle.
                <a href="{{ route('reportes.index', ['desde' => $desde, 'hasta' => $hasta, 'tab' => request('tab', 'resumen')]) }}"
                   class="text-brand-600 font-semibold hover:underline">Quitar filtros</a>
            </p>
        @endif
    </form>
</div>
