{{-- =====================================================
     HISTORIAL DE SOLICITUDES DE JUSTIFICACIÓN
     $historial: Collection<Observacion> tipo JUSTIFICACION,
     ordenadas de más antigua a más reciente (ver
     Papeleta::observacionesJustificacion). Solo la ÚLTIMA
     define el estado vigente (Papeleta::estadoSustento), pero
     acá se muestran todas para trazabilidad: si un documento
     fue rechazado y se volvió a pedir, queda visible.
====================================================== --}}
<div class="bg-white/50 rounded-xl p-4 space-y-3">
    @foreach($historial as $i => $observacion)
        @php
            $respuesta = $observacion->adjuntos->first();
            $fueSuperada = $i < $historial->count() - 1;
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
