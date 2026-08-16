{{-- Filtros del detalle --}}
<form method="GET"
      action="{{ route('reportes.index') }}"
      class="flex flex-wrap items-end gap-3 mb-4">

    <input type="hidden" name="desde" value="{{ $desde }}">
    <input type="hidden" name="hasta" value="{{ $hasta }}">

    {{-- Buscar --}}
    <div class="flex-1 min-w-[160px]">
        <label class="block text-xs font-semibold text-gray-500 mb-1">Buscar</label>
        <input type="text"
               name="buscar"
               value="{{ $filtrosDetalle['buscar'] ?? '' }}"
               placeholder="Código, destino o trabajador"
               class="input-glass !py-2 text-sm w-full">
    </div>

    {{-- Estado --}}
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

    {{-- Área --}}
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

    <button type="submit" class="btn-secondary text-sm">Filtrar</button>
</form>
