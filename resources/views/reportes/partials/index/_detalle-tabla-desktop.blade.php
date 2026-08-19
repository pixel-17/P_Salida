{{-- =====================================================
     TABLA ESCRITORIO
====================================================== --}}
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-white/60">
                <th class="py-2 pr-3 font-semibold">Código</th>
                <th class="py-2 pr-3 font-semibold">Trabajador</th>
                <th class="py-2 pr-3 font-semibold">Área</th>
                <th class="py-2 pr-3 font-semibold">Sede</th>
                <th class="py-2 pr-3 font-semibold">Jefe</th>
                <th class="py-2 pr-3 font-semibold">Motivo</th>
                <th class="py-2 pr-3 font-semibold">Destino</th>
                <th class="py-2 pr-3 font-semibold">Fecha</th>
                <th class="py-2 pr-3 font-semibold">Salida real</th>
                <th class="py-2 pr-3 font-semibold">Retorno real</th>
                <th class="py-2 pr-3 font-semibold">Horas fuera</th>
                <th class="py-2 pr-0 font-semibold">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detalleSalidas as $papeleta)
                @php
                    $marcSalida = $papeleta->marcaciones->firstWhere('tipo', 'SALIDA');
                    $marcRetorno = $papeleta->marcaciones->firstWhere('tipo', 'RETORNO');
                    $horasFuera = $papeleta->horasFuera();
                @endphp

                <tr class="border-b border-white/40 last:border-0">
                    <td class="py-2.5 pr-3 font-semibold text-gray-800">{{ $papeleta->codigo }}</td>
                    <td class="py-2.5 pr-3 text-gray-700">{{ $papeleta->trabajador?->name ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->area?->nombre ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->sede?->nombre ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->jefe?->name ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->motivo?->nombre ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->destino ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->fecha_salida->format('d/m/Y') }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $marcSalida?->created_at->format('H:i') ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $marcRetorno?->created_at->format('H:i') ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $horasFuera !== null ? number_format($horasFuera, 1).'h' : '—' }}</td>
                    <td class="py-2.5 pr-0">
                        <div class="flex items-center gap-2 justify-end">
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
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="py-6 text-center text-gray-400">
                        No hay salidas que coincidan con estos filtros.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
