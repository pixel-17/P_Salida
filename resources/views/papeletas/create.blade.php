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

        @php
            // Minutos en pasos de 5 para que el selector sea corto y usable
            // en móvil. Si en el futuro se necesita minuto exacto, basta con
            // cambiar este rango a range(0, 59).
            $minutosDisponibles = range(0, 55, 5);
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label value="Fecha" />
                <input type="date" name="fecha_salida" id="fecha_salida" required
                       min="{{ now()->toDateString() }}" value="{{ old('fecha_salida') }}"
                       class="input-glass">
            </div>

            <div>
                <x-input-label value="Hora salida" />
                @include('papeletas.partials.time-picker', [
                    'name' => 'hora_salida_programada',
                    'id' => 'hora_salida_programada',
                    'required' => true,
                    'minutos' => $minutosDisponibles,
                ])
                <p id="hora-hint" class="text-[11px] text-gray-400 mt-1.5"></p>
            </div>

            <div>
                <x-input-label value="Hora retorno" />
                @include('papeletas.partials.time-picker', [
                    'name' => 'hora_retorno_programada',
                    'id' => 'hora_retorno_programada',
                    'required' => false,
                    'minutos' => $minutosDisponibles,
                ])
            </div>
        </div>

        <script>
            // Convierte los tres selects (hora 1-12 / minuto / AM-PM) de un
            // picker en el valor 24h "H:i" que espera el backend, y viceversa
            // para repoblar el picker si la request anterior fue rechazada
            // (old()). Todo el manejo de horario vive en selects propios, ya
            // no en un <input type="time"> nativo — así el formato AM/PM es
            // siempre explícito sin importar el idioma/config del navegador.
            function initTimePicker(id) {
                const root = document.getElementById('picker-' + id);
                if (!root) return null;

                const hiddenInput = document.getElementById(id);
                const hourSel = root.querySelector('[data-role="hour"]');
                const minSel = root.querySelector('[data-role="minute"]');
                const ampmSel = root.querySelector('[data-role="ampm"]');

                function actualizar() {
                    if (!hourSel.value || !minSel.value) {
                        hiddenInput.value = '';
                        return;
                    }
                    let h = parseInt(hourSel.value, 10) % 12;
                    if (ampmSel.value === 'PM') h += 12;
                    hiddenInput.value = String(h).padStart(2, '0') + ':' + minSel.value;
                }

                function repoblarDesdeValorInicial() {
                    const valor = hiddenInput.value;
                    if (!valor) return;

                    const [hh, mm] = valor.split(':').map(Number);
                    const ampm = hh >= 12 ? 'PM' : 'AM';
                    let h12 = hh % 12;
                    if (h12 === 0) h12 = 12;

                    // Si el minuto guardado no cae justo en un paso de 5
                    // (dato viejo, por ejemplo), lo redondeamos al más
                    // cercano para poder mostrarlo en el selector.
                    const minRedondeado = Math.round(mm / 5) * 5 % 60;

                    hourSel.value = String(h12);
                    minSel.value = String(minRedondeado).padStart(2, '0');
                    ampmSel.value = ampm;
                }

                [hourSel, minSel, ampmSel].forEach(sel => sel.addEventListener('change', actualizar));

                repoblarDesdeValorInicial();
                actualizar();

                return actualizar;
            }

            (function () {
                initTimePicker('hora_salida_programada');
                initTimePicker('hora_retorno_programada');

                const fechaInput = document.getElementById('fecha_salida');
                const hint = document.getElementById('hora-hint');

                // Ya no hay ningún atributo `min` nativo que bloquee elegir
                // una hora: el picker por selects permite cualquier
                // combinación siempre. Esto es solo un aviso informativo;
                // la validación real (correcta, en horario de Perú) va en
                // StorePapeletaRequest::withValidator.
                //
                // OJO: NO usar `new Date().toISOString()` para calcular "hoy"
                // acá. Eso da la fecha en UTC, y con Perú en UTC-5, desde
                // ~19:00 hora local el reloj UTC ya marca el día siguiente —
                // eso fue justamente el bug anterior. Se arman los
                // componentes de fecha con el Date LOCAL del navegador.
                function fechaLocalISO(date) {
                    const yyyy = date.getFullYear();
                    const mm = String(date.getMonth() + 1).padStart(2, '0');
                    const dd = String(date.getDate()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd}`;
                }

                function actualizarHint() {
                    const ahora = new Date();
                    const hoy = fechaLocalISO(ahora);

                    if (fechaInput.value === hoy) {
                        const horaTexto = ahora.toLocaleTimeString('es-PE', {
                            hour: 'numeric', minute: '2-digit', hour12: true,
                        });
                        hint.textContent = `Como la fecha es hoy, elige una hora igual o posterior a las ${horaTexto}.`;
                    } else {
                        hint.textContent = '';
                    }
                }

                fechaInput.addEventListener('change', actualizarHint);
                actualizarHint();
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
