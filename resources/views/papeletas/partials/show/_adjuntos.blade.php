@php
    $pideSustento = $papeleta->observaciones
        ->where('atendida', false)
        ->contains(fn ($o) => $o->tipo === \App\Enums\TipoObservacion::JUSTIFICACION);

    $mostrarSeccionAdjuntos = $papeleta->adjuntos->isNotEmpty()
        || $papeleta->motivo->requiere_documento
        || $pideSustento;
@endphp

@if($mostrarSeccionAdjuntos)
    <div class="glass-card p-5 mb-4 animate-fade-in-up">
        <h2 class="font-semibold text-sm text-gray-700 mb-2">Documento sustentatorio</h2>

        @if($pideSustento)
            <p class="text-xs text-gray-400 mb-3">Te observaron pidiendo sustento — adjunta un documento para responder.</p>
        @elseif($papeleta->adjuntos->isEmpty())
            <p class="text-xs text-gray-400 mb-3">Este motivo requiere adjuntar un documento (solo se admite uno).</p>
        @endif

        @foreach($papeleta->adjuntos as $adjunto)
            <div class="flex items-center justify-between text-sm rounded-xl bg-white/50 border border-white/60 p-2.5 mb-2">
                <a href="{{ route('adjuntos.download', $adjunto) }}" target="_blank" class="text-brand-600 hover:text-brand-800 truncate flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    {{ $adjunto->nombre_original }}
                </a>
                @if($papeleta->trabajador_id === auth()->id())
                    <form method="POST" action="{{ route('adjuntos.destroy', $adjunto) }}" data-confirm="¿Eliminar este documento?">
                        @csrf
                        @method('DELETE')
                        <button class="text-rose-500 hover:text-rose-700 text-xs font-medium">Quitar</button>
                    </form>
                @endif
            </div>
        @endforeach

        @can('adjuntar', $papeleta)
            <form method="POST" action="{{ route('adjuntos.store', $papeleta) }}" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <input type="file" name="archivo" required accept=".pdf,.jpg,.jpeg,.png"
                       class="input-glass text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-100 file:text-brand-700 file:font-medium file:text-xs">
                <p class="text-xs text-gray-400">PDF, JPG o PNG, máx. 5MB.</p>
                <button class="btn-secondary w-full justify-center">
                    {{ $pideSustento ? 'Subir y responder observación' : 'Subir documento' }}
                </button>
            </form>
        @elseif($papeleta->adjuntos->isEmpty())
            <p class="text-sm text-gray-400">Aún no se adjunta ningún documento.</p>
        @endcan
    </div>
@endif
