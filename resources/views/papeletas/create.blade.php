<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Nueva Papeleta</h2>
    </x-slot>

    @if(auth()->user()->sede)
        <div class="glass-card border-l-4 !border-l-brand-400 text-sm text-brand-800 p-4 mb-4 animate-fade-in-up flex gap-2.5">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            <div>
                Tu sede asignada es <strong>{{ auth()->user()->sede->nombre }}</strong>
                @if(auth()->user()->sede->direccion)
                    ({{ auth()->user()->sede->direccion }})
                @endif.
            </div>
        </div>
    @else
        <div class="glass-card border-l-4 !border-l-amber-400 text-sm text-amber-800 p-4 mb-4 animate-fade-in-up flex gap-2.5">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            Todavía no tienes una sede asignada — consulta con RRHH antes de marcar tu GPS.
        </div>
    @endif

    <form method="POST" action="{{ route('papeletas.store') }}" class="glass-panel p-5 sm:p-6 space-y-5 animate-fade-in-up pb-32 sm:pb-6">
        @csrf

        <div>
            <x-input-label value="Motivo" />
            <select name="motivo_id" required class="input-glass">
                <option value="">Selecciona un motivo</option>
                @foreach(\App\Models\Motivo::activos()->orderBy('nombre')->get() as $motivo)
                    <option value="{{ $motivo->id }}" @selected(old('motivo_id') == $motivo->id)>
                        {{ $motivo->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label value="Destino" />
            <input type="text" name="destino" required value="{{ old('destino') }}"
                   class="input-glass" placeholder="Ej: Municipalidad Provincial">
        </div>

        <div>
            <x-input-label value="Detalle (opcional)" />
            <textarea name="motivo_detalle" rows="3" class="input-glass">{{ old('motivo_detalle') }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Fecha" />
                <input type="date" name="fecha_salida" id="fecha_salida" required
                       min="{{ now()->toDateString() }}" value="{{ old('fecha_salida') }}"
                       class="input-glass">
            </div>
            <div>
                <x-input-label value="Hora salida" />
                <input type="time" name="hora_salida_programada" id="hora_salida_programada" required
                       value="{{ old('hora_salida_programada') }}"
                       class="input-glass">
                <p id="hora-hint" class="text-[11px] text-gray-400 mt-1.5"></p>
            </div>
            <div>
                <x-input-label value="Hora retorno" />
                <input type="time" name="hora_retorno_programada" value="{{ old('hora_retorno_programada') }}"
                       class="input-glass">
            </div>
        </div>

        <script>
            // Solo UX: si eligen hoy, no dejamos elegir una hora ya pasada
            // en el picker. La validación real (que no se puede saltar
            // editando el HTML) va en StorePapeletaRequest::withValidator.
            (function () {
                const fechaInput = document.getElementById('fecha_salida');
                const horaInput = document.getElementById('hora_salida_programada');
                const hint = document.getElementById('hora-hint');

                function actualizarMinHora() {
                    const hoy = new Date().toISOString().slice(0, 10);

                    if (fechaInput.value === hoy) {
                        const ahora = new Date();
                        const hh = String(ahora.getHours()).padStart(2, '0');
                        const mm = String(ahora.getMinutes()).padStart(2, '0');
                        horaInput.min = `${hh}:${mm}`;
                        hint.textContent = 'Como la fecha es hoy, la hora no puede ser menor a la actual.';
                    } else {
                        horaInput.removeAttribute('min');
                        hint.textContent = '';
                    }
                }

                fechaInput.addEventListener('change', actualizarMinHora);
                actualizarMinHora();
            })();
        </script>

        {{-- En móvil la barra queda fija abajo, a un pulgar de distancia,
        arriba de la tab bar (x-bottom-nav); en escritorio va en flujo normal. --}}
        <div class="hidden sm:flex gap-3 pt-2">
            <button type="submit" class="btn-primary w-full sm:w-auto">
                Enviar solicitud
            </button>
            <a href="{{ route('papeletas.index') }}" class="btn-secondary w-full sm:w-auto justify-center">Cancelar</a>
        </div>

        <div class="sm:hidden fixed bottom-16 inset-x-0 z-30 p-3 glass !rounded-none !border-x-0 !border-b-0"
             style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <div class="flex gap-2 max-w-5xl mx-auto">
                <a href="{{ route('papeletas.index') }}" class="btn-secondary !px-4">Cancelar</a>
                <button type="submit" class="btn-primary flex-1 justify-center">Enviar solicitud</button>
            </div>
        </div>
    </form>
</x-app-layout>
