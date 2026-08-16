@php
    $pideSustento = $pideSustento ?? $papeleta->observaciones
        ->where('atendida', false)
        ->contains(fn ($o) => $o->tipo === \App\Enums\TipoObservacion::JUSTIFICACION);
@endphp

@if($papeleta->observaciones->isNotEmpty())
    <div class="glass-card p-5 mb-4 animate-fade-in-up">
        <h2 class="font-semibold text-sm text-gray-700 mb-3">Observaciones</h2>
        <div class="space-y-3">
            @foreach($papeleta->observaciones as $observacion)
                <div class="rounded-xl p-3 text-sm border {{ $observacion->atendida ? 'bg-white/40 border-white/60' : 'bg-amber-50/70 border-amber-200/70' }}">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-xs font-semibold text-gray-500">{{ $observacion->tipo->label() }}</span>
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap {{ $observacion->atendida ? 'bg-gray-200/80 text-gray-600' : 'bg-amber-200/80 text-amber-800' }}">
                            {{ $observacion->atendida ? 'Respondida' : 'Pendiente de respuesta' }}
                        </span>
                    </div>
                    <p class="mt-1 text-gray-700">{{ $observacion->comentario }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        {{ $observacion->usuario?->name ?? 'Sistema' }} — {{ $observacion->created_at->diffForHumans() }}
                    </p>
                </div>
            @endforeach
        </div>

        @can('responderObservacion', $papeleta)
            @if($pideSustento)
                <p class="text-xs text-gray-400 mt-3">
                    Sube el documento de sustento arriba — al subirlo, esta observación queda respondida automáticamente.
                </p>
            @else
                <form method="POST" action="{{ route('papeletas.responder-observacion', $papeleta) }}" class="mt-3 space-y-2">
                    @csrf
                    <textarea name="respuesta" required placeholder="Escribe tu respuesta a la observación..."
                              class="input-glass text-sm" rows="3"></textarea>
                    <button class="btn-primary w-full justify-center">Enviar respuesta</button>
                    <p class="text-xs text-gray-400">Al responder, tu papeleta vuelve a revisión de quien la observó.</p>
                </form>
            @endif
        @endcan
    </div>
@endif
