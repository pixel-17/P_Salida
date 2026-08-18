{{-- =========================================================
     ENCABEZADO + FILTRO PRINCIPAL
========================================================== --}}
<div class="glass-card p-5 mb-4 animate-fade-in-up">

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
            'bg-brand-50 text-brand-700 border-brand-100' => $esSoloJefe,
            'bg-brand-50 text-brand-700 border-brand-100' => ! $esSoloJefe,
        ])>
            <span class="w-1.5 h-1.5 rounded-full {{ $esSoloJefe ? 'bg-brand-500' : 'bg-brand-500' }}"></span>
            {{ $esSoloJefe ? 'Mi equipo' : 'Todas las áreas' }}
        </span>
    </div>

    <form method="GET"
          action="{{ route('reportes.index') }}"
          class="flex flex-wrap items-end gap-3 mt-5 pt-5 border-t border-white/60">

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

        <button type="submit" class="btn-primary text-sm">
            Filtrar
        </button>

        <a href="{{ route('reportes.exportar', ['desde' => $desde, 'hasta' => $hasta]) }}"
           class="btn-secondary text-sm inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Exportar Excel
        </a>

        <span class="text-xs text-gray-400 ml-auto self-center">
            {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }}
            —
            {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}
        </span>
    </form>
</div>
