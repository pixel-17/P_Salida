{{-- =====================================================
     CARDS MÓVIL
====================================================== --}}
<div class="md:hidden space-y-3" x-data="{ expandida: null }">
    @forelse($sustentos as $papeleta)
        @php
            $sustento = $papeleta->estadoSustento();
            $fondo = "color-mix(in srgb, {$sustento['color']} 16%, white)";
            $texto = "color-mix(in srgb, {$sustento['color']} 65%, black)";
            $borde = "color-mix(in srgb, {$sustento['color']} 40%, white)";
            $historial = $papeleta->observacionesJustificacion;
        @endphp

        <div class="glass-card p-4 border-l-4" style="border-left-color: {{ $papeleta->estado->color }}">
            <div class="flex items-start justify-between gap-2 mb-1">
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-gray-800">{{ $papeleta->codigo }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $papeleta->trabajador?->name ?? '—' }} · {{ $papeleta->motivo?->nombre ?? '—' }}
                    </p>
                </div>

                <x-status-badge :estado="$papeleta->estado" :detalle="$papeleta->etiquetaVencimiento()" />
            </div>

            <p class="text-xs text-gray-400 mb-2">
                {{ $papeleta->fecha_salida->format('d/m/Y') }}
                · {{ $papeleta->adjuntos->count() }} {{ Str::plural('documento', $papeleta->adjuntos->count()) }}
            </p>

            <div class="flex items-center justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap"
                      style="background-color: {{ $fondo }}; color: {{ $texto }}; border: 1px solid {{ $borde }};">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $sustento['color'] }}"></span>
                    {{ $sustento['label'] }}
                </span>

                <div class="flex items-center gap-2">
                    @if($historial->isNotEmpty())
                        <button type="button"
                                @click="expandida = expandida === {{ $papeleta->id }} ? null : {{ $papeleta->id }}"
                                class="btn-secondary !px-2.5 !py-1.5 text-xs whitespace-nowrap">
                            <span x-text="expandida === {{ $papeleta->id }} ? 'Ocultar' : 'Historial'"></span>
                        </button>
                    @endif
                    <a href="{{ route('papeletas.show', $papeleta) }}"
                       class="btn-primary !px-2.5 !py-1.5 text-xs whitespace-nowrap">
                        Ver
                    </a>
                </div>
            </div>

            @if($historial->isNotEmpty())
                <div x-show="expandida === {{ $papeleta->id }}" x-cloak class="mt-3">
                    @include('sustentos.partials._historial')
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-400 text-center py-6">
            No hay papeletas con motivo que exija documento en este rango.
        </p>
    @endforelse
</div>
