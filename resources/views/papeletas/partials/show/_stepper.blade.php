@php
    // Camino "feliz" de una papeleta normal. Si el estado actual no está en
    // esta lista (RECHAZADO, OBSERVADO, VENCIDA, CANCELADO), el stepper no
    // se muestra — esos casos ya se explican solos con el badge de estado.
    $pasos = [
        \App\Enums\EstadoPapeleta::SOLICITADO->value => 'Solicitado',
        \App\Enums\EstadoPapeleta::APROBADO_JEFE->value => 'Jefe',
        \App\Enums\EstadoPapeleta::APROBADO_RRHH->value => 'RRHH',
        \App\Enums\EstadoPapeleta::EN_CURSO->value => 'En curso',
        \App\Enums\EstadoPapeleta::FINALIZADO->value => 'Finalizado',
    ];
    $indiceActual = array_search($papeleta->estado->codigo, array_keys($pasos), true);
@endphp

@if($indiceActual !== false)
    <div class="glass-card p-4 mb-4 animate-fade-in-up overflow-x-auto">
        <div class="flex items-center min-w-max">
            @foreach($pasos as $codigo => $etiqueta)
                @php $i = $loop->index; @endphp
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center gap-1 shrink-0">
                        <div @class([
                            'w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-all duration-300',
                            'bg-emerald-500 text-white' => $i < $indiceActual,
                            'bg-brand-500 text-white ring-4 ring-brand-100' => $i === $indiceActual,
                            'bg-gray-200/70 text-gray-400' => $i > $indiceActual,
                        ])>
                            @if($i < $indiceActual)
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </div>
                        <span class="text-[10px] font-medium whitespace-nowrap {{ $i <= $indiceActual ? 'text-gray-700' : 'text-gray-400' }}">{{ $etiqueta }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="h-0.5 flex-1 min-w-[1.5rem] mx-1 rounded-full transition-all duration-300 {{ $i < $indiceActual ? 'bg-emerald-400' : 'bg-gray-200/70' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
