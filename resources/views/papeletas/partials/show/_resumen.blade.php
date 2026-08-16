@php
    $etiquetaVencimiento = $papeleta->etiquetaVencimiento();
    $motivoCancelacion = null;

    if ($papeleta->estaEn(\App\Enums\EstadoPapeleta::CANCELADO)) {
        $motivoCancelacion = $papeleta->historial()
            ->where('accion', 'CANCELADA_POR_TRABAJADOR')
            ->latest()
            ->first()
            ?->descripcion;
    }
@endphp

<div class="glass-card p-5 mb-4 animate-fade-in-up">
    <div class="flex justify-between items-start mb-3 gap-3 flex-wrap">
        <h1 class="text-lg font-bold text-gray-800">{{ $papeleta->codigo }}</h1>
        <x-status-badge :estado="$papeleta->estado" size="lg" :detalle="$etiquetaVencimiento" />
    </div>

    @if($etiquetaVencimiento === 'Sin retorno')
        <div class="mb-3 text-sm rounded-xl px-4 py-2.5 bg-red-50/80 text-red-700 border border-red-200/60">
            Marcó salida con el vigilante y nunca registró su retorno.
        </div>
    @elseif($etiquetaVencimiento === 'No se presentó')
        <div class="mb-3 text-sm rounded-xl px-4 py-2.5 bg-amber-50/80 text-amber-700 border border-amber-200/60">
            Se venció el día sin que el trabajador llegara a marcar salida.
        </div>
    @elseif($papeleta->estaEn(\App\Enums\EstadoPapeleta::CANCELADO))
        <div class="mb-3 text-sm rounded-xl px-4 py-2.5 bg-gray-100/80 text-gray-600 border border-gray-200/60">
            El trabajador canceló esta papeleta.
            @if($motivoCancelacion)
                Motivo: {{ $motivoCancelacion }}
            @endif
        </div>
    @endif

    <dl class="text-sm space-y-1.5 text-gray-700">
        <div class="flex justify-between">
            <dt class="text-gray-500">Trabajador</dt>
            <dd class="font-medium">{{ $papeleta->trabajador->name }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-500">Destino</dt>
            <dd class="font-medium">{{ $papeleta->destino }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-500">Motivo</dt>
            <dd class="font-medium">{{ $papeleta->motivo->nombre }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-500">Fecha</dt>
            <dd class="font-medium">{{ $papeleta->fecha_salida->format('d/m/Y') }}</dd>
        </div>
        <div class="flex justify-between">
            <dt class="text-gray-500">Horario</dt>
            <dd class="font-medium">{{ $papeleta->hora_salida_programada }} - {{ $papeleta->hora_retorno_programada ?? '—' }}</dd>
        </div>
        @if($papeleta->motivo_detalle)
            <div class="pt-3 border-t border-white/60 mt-2">
                <dt class="text-gray-500 mb-0.5">Detalle</dt>
                <dd>{{ $papeleta->motivo_detalle }}</dd>
            </div>
        @endif
    </dl>
</div>
