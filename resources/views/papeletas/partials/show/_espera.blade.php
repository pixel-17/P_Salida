@php
    $esperaConfig = match($papeleta->estado->codigo) {
        \App\Enums\EstadoPapeleta::SOLICITADO->value => [
            'mensaje' => 'Esperando aprobación del jefe',
            'desde' => $papeleta->created_at,
        ],
        \App\Enums\EstadoPapeleta::APROBADO_JEFE->value => [
            'mensaje' => 'Esperando aprobación de RRHH',
            'desde' => optional(
                $papeleta->flujoAprobaciones
                    ->where('rol', 'JEFE')
                    ->where('accion', \App\Enums\AccionFlujo::APROBADO->value)
                    ->last()
            )->created_at ?? $papeleta->created_at,
        ],
        default => null,
    };
@endphp

@if($esperaConfig)
    <div class="glass-card p-4 mb-4 flex items-center gap-3 animate-fade-in-up">
        <span class="inline-block w-6 h-6 border-2 border-brand-200 border-t-brand-600 rounded-full animate-spin shrink-0"></span>
        <div>
            <p class="text-sm font-semibold text-gray-700">{{ $esperaConfig['mensaje'] }}</p>
            <p class="text-xs text-gray-400" data-tiempo-espera
               data-desde="{{ $esperaConfig['desde']->toIso8601String() }}">
                calculando…
            </p>
        </div>
    </div>
@endif
