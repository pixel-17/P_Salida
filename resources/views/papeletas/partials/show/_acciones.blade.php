@can('decidir', $papeleta)
    <div class="glass-card p-5 mb-4 space-y-3 animate-fade-in-up">
        <h2 class="font-semibold text-sm text-gray-700">Acciones</h2>

        <form method="POST" action="{{ route('papeletas.aprobar', $papeleta) }}"
              x-data="{ confirmado: false }"
              @submit="confirmado = true">
            @csrf
            <button type="submit"
                    class="btn-glass text-white shadow-glass w-full justify-center relative overflow-hidden"
                    style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"
                    :disabled="confirmado">
                <span x-show="!confirmado" class="flex items-center gap-2">Aprobar</span>
                <span x-show="confirmado" x-cloak class="flex items-center gap-2">
                    <svg class="w-5 h-5 animate-scale-in" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    ¡Aprobado!
                </span>
            </button>
        </form>

        <form method="POST" action="{{ route('papeletas.rechazar', $papeleta) }}" class="space-y-2">
            @csrf
            <textarea name="comentario" required placeholder="Motivo del rechazo" class="input-glass text-sm" rows="2"></textarea>
            <button class="btn-danger w-full justify-center">Rechazar</button>
        </form>

        <form method="POST" action="{{ route('papeletas.observar', $papeleta) }}" class="space-y-2">
            @csrf
            <select name="tipo" required class="input-glass text-sm">
                <option value="ADMINISTRATIVA">Observación administrativa</option>
                <option value="JUSTIFICACION">Requiere justificación</option>
            </select>
            <textarea name="comentario" required placeholder="Detalle de la observación" class="input-glass text-sm" rows="2"></textarea>
            <button class="btn-glass text-white shadow-glass w-full justify-center" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">Observar</button>
        </form>
    </div>
@endcan
