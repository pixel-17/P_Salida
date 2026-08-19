{{-- =====================================================
     CARDS MÓVIL
====================================================== --}}
<div class="md:hidden space-y-3">
    @forelse($detalleSalidas as $papeleta)
        @php
            $marcSalida = $papeleta->marcaciones->firstWhere('tipo', 'SALIDA');
            $marcRetorno = $papeleta->marcaciones->firstWhere('tipo', 'RETORNO');
            $horasFuera = $papeleta->horasFuera();
        @endphp

        <div class="glass-card p-4 border-l-4" style="border-left-color: {{ $papeleta->estado->color }}">
            <div class="flex items-start justify-between gap-2 mb-1">
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-gray-800">{{ $papeleta->codigo }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $papeleta->trabajador?->name ?? '—' }}
                        ·
                        {{ $papeleta->area?->nombre ?? '—' }}
                        @if($papeleta->sede)
                            · {{ $papeleta->sede->nombre }}
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <x-status-badge :estado="$papeleta->estado" :detalle="$papeleta->etiquetaVencimiento()" />
                    <a href="{{ route('papeletas.show', $papeleta) }}"
                       title="Ver papeleta"
                       class="btn-primary !px-2.5 !py-1.5 text-xs whitespace-nowrap inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Ver
                    </a>
                </div>
            </div>

            <p class="text-xs text-gray-400">
                Jefe: {{ $papeleta->jefe?->name ?? '—' }}
                · Motivo: {{ $papeleta->motivo?->nombre ?? '—' }}
                @if($papeleta->destino)
                    · Destino: {{ $papeleta->destino }}
                @endif
            </p>

            <p class="text-xs text-gray-400 mt-1">
                {{ $papeleta->fecha_salida->format('d/m/Y') }}
                · Salida: {{ $marcSalida?->created_at->format('H:i') ?? '—' }}
                · Retorno: {{ $marcRetorno?->created_at->format('H:i') ?? '—' }}
                @if($horasFuera !== null)
                    · {{ number_format($horasFuera, 1) }}h fuera
                @endif
            </p>
        </div>
    @empty
        <p class="text-sm text-gray-400 text-center py-6">
            No hay salidas que coincidan con estos filtros.
        </p>
    @endforelse
</div>
