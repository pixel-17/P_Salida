{{-- =====================================================
     TABLA ESCRITORIO
     Fila expandible con el historial de solicitudes de
     justificación (Papeleta::observacionesJustificacion):
     quién la pidió, si se respondió y si esa respuesta fue
     aceptada o volvió a observarse.
====================================================== --}}
<div class="hidden md:block overflow-x-auto" x-data="{ expandida: null }">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-white/60">
                <th class="py-2 pr-3 font-semibold">Código</th>
                <th class="py-2 pr-3 font-semibold">Trabajador</th>
                <th class="py-2 pr-3 font-semibold">Motivo</th>
                <th class="py-2 pr-3 font-semibold">Fecha</th>
                <th class="py-2 pr-3 font-semibold">Estado papeleta</th>
                <th class="py-2 pr-3 font-semibold">Sustento</th>
                <th class="py-2 pr-3 font-semibold">Documentos</th>
                <th class="py-2 pr-0 font-semibold"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($sustentos as $papeleta)
                @php
                    $sustento = $papeleta->estadoSustento();
                    $fondo = "color-mix(in srgb, {$sustento['color']} 16%, white)";
                    $texto = "color-mix(in srgb, {$sustento['color']} 65%, black)";
                    $borde = "color-mix(in srgb, {$sustento['color']} 40%, white)";
                    $historial = $papeleta->observacionesJustificacion;
                @endphp

                <tr class="border-b border-white/40 last:border-0">
                    <td class="py-2.5 pr-3 font-semibold text-gray-800">{{ $papeleta->codigo }}</td>
                    <td class="py-2.5 pr-3 text-gray-700">{{ $papeleta->trabajador?->name ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->motivo?->nombre ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->fecha_salida->format('d/m/Y') }}</td>
                    <td class="py-2.5 pr-3">
                        <x-status-badge :estado="$papeleta->estado" :detalle="$papeleta->etiquetaVencimiento()" />
                    </td>
                    <td class="py-2.5 pr-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap"
                              style="background-color: {{ $fondo }}; color: {{ $texto }}; border: 1px solid {{ $borde }};">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $sustento['color'] }}"></span>
                            {{ $sustento['label'] }}
                        </span>
                    </td>
                    <td class="py-2.5 pr-3 text-gray-600">{{ $papeleta->adjuntos->count() }}</td>
                    <td class="py-2.5 pr-0">
                        <div class="flex items-center gap-2 justify-end">
                            @if($historial->isNotEmpty())
                                <button type="button"
                                        @click="expandida = expandida === {{ $papeleta->id }} ? null : {{ $papeleta->id }}"
                                        class="btn-secondary !px-2.5 !py-1.5 text-xs whitespace-nowrap">
                                    <span x-text="expandida === {{ $papeleta->id }} ? 'Ocultar' : 'Historial'"></span>
                                </button>
                            @endif
                            <a href="{{ route('papeletas.show', $papeleta) }}"
                               title="Ver papeleta"
                               class="btn-primary !px-2.5 !py-1.5 text-xs whitespace-nowrap">
                                Ver
                            </a>
                        </div>
                    </td>
                </tr>

                @if($historial->isNotEmpty())
                    <tr x-show="expandida === {{ $papeleta->id }}" x-cloak>
                        <td colspan="8" class="pb-4 pt-1">
                            @include('sustentos.partials._historial')
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="8" class="py-6 text-center text-gray-400">
                        No hay papeletas con motivo que exija documento en este rango.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
