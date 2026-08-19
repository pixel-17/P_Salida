{{-- =========================================================
     ENCABEZADO + FILTROS
     Mismo patrón visual que reportes/_barra-filtros, pero
     módulo independiente: rango de fechas + buscar (código/
     destino/trabajador) + estado de sustento derivado.
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
                📎
            </div>
            <div>
                <h1 class="font-extrabold text-lg text-gray-800 leading-tight">Sustentos documentales</h1>
                <p class="text-xs text-gray-500 mt-0.5 max-w-md">
                    Papeletas cuyo motivo exige documento: si se presentó, si se pidió justificación
                    y si la respuesta fue aceptada o volvió a observarse.
                </p>
            </div>
        </div>

        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full self-start lg:self-auto border bg-brand-50 text-brand-700 border-brand-100">
            <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
            {{ $esSoloJefe ? 'Mi equipo' : 'Todas las áreas' }}
        </span>
    </div>

    <form method="GET"
          action="{{ route('sustentos.index') }}"
          x-ref="formFiltros"
          class="mt-5 pt-5 border-t border-white/60">

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
                <label class="block text-xs font-semibold text-gray-500 mb-1">Buscar trabajador</label>
                <input type="text"
                       name="buscar"
                       value="{{ $filtros['buscar'] ?? '' }}"
                       placeholder="Código, destino o trabajador"
                       class="input-glass !py-2 text-sm w-full">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Sustento</label>
                <select name="estado_sustento" class="input-glass !py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="pendiente" @selected(($filtros['estado_sustento'] ?? null) === 'pendiente')>Pendiente de subsanar</option>
                    <option value="aceptado" @selected(($filtros['estado_sustento'] ?? null) === 'aceptado')>Subsanado</option>
                    <option value="presentado" @selected(($filtros['estado_sustento'] ?? null) === 'presentado')>Presentado</option>
                    <option value="sin_pedir" @selected(($filtros['estado_sustento'] ?? null) === 'sin_pedir')>Sin observar</option>
                </select>
            </div>

            <button type="submit" class="btn-primary text-sm">Filtrar</button>
        </div>

        @if(($filtros['buscar'] ?? null) || ($filtros['estado_sustento'] ?? null))
            <p class="text-xs text-gray-400 mt-3">
                <a href="{{ route('sustentos.index', ['desde' => $desde, 'hasta' => $hasta]) }}"
                   class="text-brand-600 font-semibold hover:underline">Quitar filtros</a>
            </p>
        @endif
    </form>
</div>
