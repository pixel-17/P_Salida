{{-- =========================================================
     PESTAÑA: SUSTENTOS
     Solo papeletas cuyo motivo exige documento (ver
     ReporteController::index -> $sustentos). Por cada una se
     muestra el estado derivado (Papeleta::estadoSustento()) y,
     al expandir la fila, el historial completo de solicitudes
     de justificación: quién la pidió, si el trabajador ya
     respondió y si esa respuesta fue aceptada o volvió a
     observarse (documento incorrecto/insuficiente).
========================================================== --}}
<div x-show="tab === 'sustentos'" x-cloak x-data="{ expandida: null }">
    <div class="glass-panel p-5 animate-fade-in-up">
        <h3 class="font-semibold text-sm text-gray-700 mb-1">
            Sustentos documentales
            <span class="text-xs font-normal text-gray-400">
                · {{ $sustentos->total() }} {{ Str::plural('papeleta', $sustentos->total()) }} con motivo que exige documento
            </span>
        </h3>
        <p class="text-xs text-gray-400 mb-4">
            Usa "Buscar" en la barra de filtros para acotar a un trabajador específico.
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 border-b border-white/60">
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
                            <td class="py-2.5 pr-3 text-gray-600">
                                {{ $papeleta->adjuntos->count() }}
                            </td>
                            <td class="py-2.5 pr-0">
                                <div class="flex items-center gap-2 justify-end">
                                    @if($historial->isNotEmpty())
                                        <button type="button"
                                                @click="expandida = expandida === {{ $papeleta->id }} ? null : {{ $papeleta->id }}"
                                                class="btn-primary !px-2.5 !py-1.5 text-xs whitespace-nowrap">
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
                                <td colspan="7" class="pb-4 pt-1">
                                    <div class="bg-white/50 rounded-xl p-4 space-y-3">
                                        @foreach($historial as $i => $observacion)
                                            @php
                                                $respuesta = $observacion->adjuntos->first();
                                                $fueSuperada = $i < $historial->count() - 1; // se volvió a pedir después: esta respuesta no sirvió
                                            @endphp
                                            <div class="text-xs border-l-2 pl-3"
                                                 style="border-color: {{ $observacion->atendida ? ($fueSuperada ? '#ef4444' : '#22c55e') : '#f59e0b' }}">
                                                <p class="text-gray-700">
                                                    <span class="font-semibold">Solicitud {{ $i + 1 }}</span>
                                                    · pedida por {{ $observacion->usuario?->name ?? '—' }}
                                                    el {{ $observacion->created_at->format('d/m/Y H:i') }}
                                                </p>
                                                @if($observacion->comentario)
                                                    <p class="text-gray-500 mt-0.5">"{{ $observacion->comentario }}"</p>
                                                @endif

                                                @if(! $observacion->atendida)
                                                    <p class="text-amber-600 font-semibold mt-1">Pendiente de respuesta del trabajador</p>
                                                @elseif($fueSuperada)
                                                    <p class="text-red-600 font-semibold mt-1">
                                                        Respondida el {{ $respuesta?->created_at?->format('d/m/Y H:i') ?? '—' }}
                                                        — no fue aceptada, se volvió a solicitar
                                                    </p>
                                                @else
                                                    <p class="text-green-600 font-semibold mt-1">
                                                        Aceptada — respondida el {{ $respuesta?->created_at?->format('d/m/Y H:i') ?? '—' }}
                                                    </p>
                                                @endif

                                                @if($respuesta)
                                                    <a href="{{ route('adjuntos.download', $respuesta) }}"
                                                       target="_blank"
                                                       class="text-brand-600 hover:underline inline-flex items-center gap-1 mt-1">
                                                        📎 {{ $respuesta->nombre_original }}
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-400">
                                No hay papeletas con motivo que exija documento en este rango.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sustentos->links() }}
        </div>
    </div>
</div>
