@if($papeleta->marcaciones->isNotEmpty())
    <div class="glass-card p-5 mb-4 animate-fade-in-up">
        <h2 class="font-semibold text-sm text-gray-700 mb-3">Historial de marcación</h2>
        <div class="space-y-2">
            @foreach($papeleta->marcaciones as $marcacion)
                <div class="rounded-xl bg-white/40 border border-white/60 p-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-700">
                            {{ $marcacion->tipo === \App\Enums\TipoMarcacion::SALIDA ? 'Salida' : 'Retorno' }}
                        </span>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-100/80 text-emerald-700">
                            ✓ Confirmado por vigilante
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $marcacion->created_at->format('d/m/Y H:i') }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif
