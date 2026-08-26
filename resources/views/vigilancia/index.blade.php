<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <x-application-logo class="w-7 h-7 fill-current text-brand-600" />
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 tracking-tight leading-tight">Control de puerta</h2>
                    @if ($sedeNombre)
                        <p class="text-xs text-gray-400 leading-tight">{{ $sedeNombre }}</p>
                    @endif
                </div>
            </div>

            {{-- Reloj en vivo: ayuda al vigilante a ubicarse respecto al
            horario límite de registro sin tener que mirar el celular. --}}
            <div class="text-right shrink-0">
                <p class="text-lg font-bold text-gray-700 tabular-nums leading-tight" x-data x-text="new Date().toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'})" x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'}), 15000)"></p>
                <p class="text-xs text-gray-400 leading-tight">Límite {{ $horaLimiteRegistro }}</p>
            </div>
        </div>
    </x-slot>

    <div x-data="controlPuerta(@js($horaLimiteRegistro))" x-init="init()" class="max-w-xl mx-auto">

        {{-- Aviso de cierre de registros: aparece 30 min antes del límite y
        cambia de tono cuando ya se pasó la hora. No bloquea "Hoy"/"Buscar",
        solo advierte que confirmar salida/retorno ya no va a funcionar. --}}
        <div
            x-show="avisoHorario"
            x-cloak
            class="glass-card p-3.5 mb-4 flex items-start gap-2.5 border-l-4 animate-fade-in-up"
            :style="`border-left-color: ${horarioVencido ? '#f43f5e' : '#f59e0b'}`"
        >
            <span class="text-lg shrink-0" x-text="horarioVencido ? '🚫' : '⏰'"></span>
            <p class="text-xs leading-relaxed" :class="horarioVencido ? 'text-rose-600' : 'text-amber-600'">
                <span x-show="!horarioVencido">
                    Quedan <span class="font-semibold" x-text="minutosParaLimite"></span> min para el cierre de registros (<span x-text="horaLimite"></span>). Después solo se puede consultar.
                </span>
                <span x-show="horarioVencido" x-cloak>
                    Ya pasó el horario límite de registros (<span x-text="horaLimite"></span>). Puede seguir consultando, pero no confirmar salidas ni retornos.
                </span>
            </p>
        </div>

        {{-- Mismo patrón de pestañas glass que "Mis papeletas" / "Bandeja"
        del trabajador y del jefe — misma sensación de app en ambos roles. --}}
        <div class="flex gap-1 mb-4 glass p-1 rounded-xl">
            <button
                type="button"
                @click="cambiarModo('resumen')"
                class="flex-1 relative text-sm px-4 py-2 rounded-lg font-semibold transition-all duration-200"
                :class="modo === 'resumen' ? 'bg-white shadow-sm text-brand-700' : 'text-gray-500 hover:text-gray-700'"
            >
                Hoy
                <span
                    x-show="pendientesTotal > 0"
                    x-cloak
                    x-text="pendientesTotal"
                    class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-brand-600 text-white text-[10px] font-bold flex items-center justify-center"
                ></span>
            </button>
            <button
                type="button"
                @click="cambiarModo('escanear')"
                class="flex-1 text-sm px-4 py-2 rounded-lg font-semibold transition-all duration-200"
                :class="modo === 'escanear' ? 'bg-white shadow-sm text-brand-700' : 'text-gray-500 hover:text-gray-700'"
            >
                Escanear
            </button>
            <button
                type="button"
                @click="cambiarModo('buscar')"
                class="flex-1 text-sm px-4 py-2 rounded-lg font-semibold transition-all duration-200"
                :class="modo === 'buscar' ? 'bg-white shadow-sm text-brand-700' : 'text-gray-500 hover:text-gray-700'"
            >
                Buscar
            </button>
        </div>

        {{-- ---------- Modo escanear ---------- --}}
        <div x-show="modo === 'escanear'" x-cloak class="glass-card p-4 mb-4 animate-fade-in-up">
            <div class="relative rounded-xl overflow-hidden bg-gray-900" style="aspect-ratio: 4/3;">
                <video id="qr-video" class="w-full h-full object-cover"></video>

                <div x-show="!camaraLista && !errorCamara" class="absolute inset-0 flex items-center justify-center gap-2">
                    <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    <p class="text-white text-sm">Iniciando cámara…</p>
                </div>

                <div x-show="errorCamara" x-cloak class="absolute inset-0 flex items-center justify-center p-4">
                    <p class="text-rose-300 text-sm text-center" x-text="errorCamara"></p>
                </div>
            </div>

            <button
                x-show="errorCamara"
                x-cloak
                @click="iniciarEscaneo()"
                type="button"
                class="btn-secondary w-full justify-center mt-3"
            >
                Reintentar cámara
            </button>

            <p class="text-xs text-gray-400 mt-3 text-center">
                Apunta al QR de la papeleta o al DNI del trabajador
            </p>
        </div>

        {{-- ---------- Modo buscar a mano ---------- --}}
        <div x-show="modo === 'buscar'" x-cloak class="glass-card p-4 mb-4 animate-fade-in-up">
            <label class="block text-sm font-medium text-gray-600 mb-1.5">Buscar por nombre, DNI o código</label>
            <input
                type="text"
                x-model="q"
                @input.debounce.400ms="buscar()"
                placeholder="Ej: 45678912 o PAP-2026-00001"
                x-ref="inputBuscar"
                class="input-glass text-base"
            >
        </div>

        {{-- ---------- Modo resumen: pendientes / en curso / finalizados de hoy en la sede ---------- --}}
        <div x-show="modo === 'resumen'" x-cloak class="mb-4">
            <div x-show="errorResumen" x-cloak
                 class="glass-card border-l-4 !border-l-rose-400 text-xs text-rose-600 p-3 mb-2 flex items-center gap-2">
                <span>📡</span>
                <span x-text="huboResumenAntes ? 'Sin conexión — mostrando lo último cargado.' : 'Sin conexión. No se pudo cargar la lista de hoy.'"></span>
            </div>

            <div class="flex items-center justify-between px-1 mb-2">
                <p class="text-xs text-gray-400" x-show="!cargandoResumen">
                    Actualizado <span x-text="ultimaActualizacionTexto"></span>
                </p>
                <button
                    type="button"
                    @click="cargarResumen()"
                    :disabled="cargandoResumen"
                    class="text-xs font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1 ml-auto"
                >
                    <span :class="cargandoResumen ? 'animate-spin' : ''">↻</span> Actualizar
                </button>
            </div>

            <template x-if="cargandoResumen && !huboResumenAntes">
                <div class="glass-card p-8 text-center animate-fade-in-up">
                    <span class="inline-block w-6 h-6 border-2 border-brand-200 border-t-brand-600 rounded-full animate-spin"></span>
                    <p class="text-sm text-gray-400 mt-2">Cargando…</p>
                </div>
            </template>

            <template x-if="!cargandoResumen || huboResumenAntes">
                <div class="space-y-5">
                    {{-- Pendientes de salida --}}
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 px-1">
                            Pendientes de salida (<span x-text="resumen.pendientes.length"></span>)
                        </h3>
                        <template x-if="resumen.pendientes.length === 0">
                            <p class="text-xs text-gray-400 px-1">Nadie pendiente por salir.</p>
                        </template>
                        <div class="space-y-2">
                            <template x-for="p in resumen.pendientes" :key="p.id">
                                <div class="glass-card p-3.5 flex justify-between items-center border-l-4" style="border-left-color:#94a3b8">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm text-gray-800 truncate" x-text="p.trabajador"></p>
                                        <p class="text-xs text-gray-400" x-text="p.codigo"></p>
                                    </div>
                                    <span class="text-xs font-medium text-gray-500 shrink-0" x-text="p.hora_salida_programada"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- En curso (salió, falta retorno) --}}
                    <div>
                        <h3 class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-2 px-1">
                            Fuera / en curso (<span x-text="resumen.en_curso.length"></span>)
                        </h3>
                        <template x-if="resumen.en_curso.length === 0">
                            <p class="text-xs text-gray-400 px-1">Nadie fuera en este momento.</p>
                        </template>
                        <div class="space-y-2">
                            <template x-for="p in resumen.en_curso" :key="p.id">
                                <div class="glass-card p-3.5 border-l-4" style="border-left-color:#3b6cf6">
                                    <div class="flex justify-between items-center gap-2">
                                        <p class="font-semibold text-sm text-gray-800 truncate" x-text="p.trabajador"></p>
                                        <p class="text-xs text-gray-400 shrink-0" x-text="p.codigo"></p>
                                    </div>
                                    <p class="text-xs text-blue-600 mt-1">
                                        Salió <span x-text="p.salida?.hora"></span>
                                        <span x-show="p.salida?.registrado_por"> · <span x-text="p.salida?.registrado_por"></span></span>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Finalizadas hoy --}}
                    <div>
                        <h3 class="text-xs font-semibold text-emerald-600 uppercase tracking-wide mb-2 px-1">
                            Retornaron hoy (<span x-text="resumen.finalizadas.length"></span>)
                        </h3>
                        <template x-if="resumen.finalizadas.length === 0">
                            <p class="text-xs text-gray-400 px-1">Nadie ha retornado aún.</p>
                        </template>
                        <div class="space-y-2">
                            <template x-for="p in resumen.finalizadas" :key="p.id">
                                <div class="glass-card p-3.5 border-l-4" style="border-left-color:#22c55e">
                                    <div class="flex justify-between items-center gap-2">
                                        <p class="font-semibold text-sm text-gray-800 truncate" x-text="p.trabajador"></p>
                                        <p class="text-xs text-gray-400 shrink-0" x-text="p.codigo"></p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Salió <span x-text="p.salida?.hora"></span> · Retornó <span x-text="p.retorno?.hora"></span>
                                        <span x-show="p.retorno?.registrado_por"> · <span x-text="p.retorno?.registrado_por"></span></span>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- ---------- Resultados (comunes a los modos escanear/buscar) ---------- --}}
        <div x-show="modo !== 'resumen'" x-cloak>
            <template x-if="cargando">
                <p class="text-center text-sm text-gray-400 py-6">Buscando…</p>
            </template>

            <template x-if="!cargando && errorBusqueda">
                <p class="text-center text-sm text-rose-500 py-6">📡 Sin conexión. No se pudo buscar — intenta de nuevo.</p>
            </template>

            <template x-if="!cargando && !errorBusqueda && q.length >= 2 && resultados.length === 0">
                <p class="text-center text-sm text-gray-400 py-6">Sin resultados para "<span x-text="q"></span>".</p>
            </template>

            <div class="space-y-3">
                <template x-for="p in resultados" :key="p.id">
                    <div class="glass-card p-4 animate-fade-in-up">
                        <p class="font-bold text-gray-800" x-text="p.trabajador"></p>
                        <p class="text-sm text-gray-500">DNI <span x-text="p.dni"></span> · <span x-text="p.codigo"></span></p>

                        <div class="mt-3 flex gap-2">
                            <button
                                x-show="p.puede_salida && !horarioVencido"
                                :disabled="confirmando === p.id"
                                @click="confirmar(p, 'salida')"
                                class="flex-1 btn-glass text-white shadow-glass justify-center text-base py-3"
                                style="background: linear-gradient(135deg, #3b6cf6 0%, #6d5bf0 100%);"
                            >
                                <span x-show="confirmando !== p.id">Confirmar salida</span>
                                <span x-show="confirmando === p.id" x-cloak>Confirmando…</span>
                            </button>
                            <button
                                x-show="p.puede_retorno && !horarioVencido"
                                :disabled="confirmando === p.id"
                                @click="confirmar(p, 'retorno')"
                                class="flex-1 btn-glass text-white shadow-glass justify-center text-base py-3"
                                style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"
                            >
                                <span x-show="confirmando !== p.id">Confirmar retorno</span>
                                <span x-show="confirmando === p.id" x-cloak>Confirmando…</span>
                            </button>
                            <p
                                x-show="(p.puede_salida || p.puede_retorno) && horarioVencido"
                                x-cloak
                                class="flex-1 text-center text-xs font-medium text-rose-500 bg-rose-50 rounded-lg py-3"
                            >
                                Fuera de horario ({{ $horaLimiteRegistro }})
                            </p>
                            {{-- Papeleta aprobada pero para una fecha distinta a hoy: ni
                            futura ni vencida se puede marcar acá, se informa en vez de
                            ofrecer un botón que el servidor va a rechazar igual. --}}
                            <p
                                x-show="p.estado === 'APROBADO_RRHH' && !p.puede_salida"
                                x-cloak
                                class="flex-1 text-center text-xs font-medium py-3 rounded-lg"
                                :class="p.dias_para_salida > 0 ? 'text-amber-700 bg-amber-50' : 'text-rose-600 bg-rose-50'"
                            >
                                <template x-if="p.dias_para_salida > 0">
                                    <span>Válida el <span x-text="p.fecha_salida"></span> — faltan <span x-text="p.dias_para_salida"></span> día<span x-show="p.dias_para_salida > 1">s</span></span>
                                </template>
                                <template x-if="p.dias_para_salida <= 0">
                                    <span>Fecha autorizada (<span x-text="p.fecha_salida"></span>) ya venció</span>
                                </template>
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function controlPuerta(horaLimite) {
            return {
                modo: 'resumen',
                q: '',
                resultados: [],
                cargando: false,
                confirmando: null,
                camaraLista: false,
                errorCamara: '',
                resumen: { pendientes: [], en_curso: [], finalizadas: [] },
                cargandoResumen: false,
                huboResumenAntes: false,
                errorResumen: false,
                errorBusqueda: false,
                ultimaActualizacion: null,
                horaLimite,
                minutosParaLimite: null,
                horarioVencido: false,
                avisoHorario: false,
                autoRefrescoId: null,

                get pendientesTotal() {
                    return this.resumen.pendientes.length + this.resumen.en_curso.length;
                },

                get ultimaActualizacionTexto() {
                    if (!this.ultimaActualizacion) return '—';
                    return this.ultimaActualizacion.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
                },

                init() {
                    // Arranca en "Hoy" (resumen de la sede), no en la cámara:
                    // el vigilante ve primero quién falta/está fuera y recién
                    // activa la cámara cuando de verdad va a escanear.
                    this.cargarResumen();
                    this.evaluarHorario();
                    setInterval(() => this.evaluarHorario(), 30000);

                    // Refresco automático de "Hoy" cada 45s: la garita queda
                    // abierta en pantalla todo el turno, no tiene sentido
                    // que el vigilante tenga que refrescar a mano siempre.
                    this.autoRefrescoId = setInterval(() => {
                        if (this.modo === 'resumen' && !this.cargandoResumen) this.cargarResumen();
                    }, 45000);

                    // Si se navega fuera de la pantalla, apagar la cámara.
                    window.addEventListener('beforeunload', () => window.detenerEscaneoQr());
                },

                // Compara contra el horario límite calculado en el server
                // (Configuracion::horaLimiteGarita() = fin de jornada + 10
                // min), no un valor fijo en el front — si cambia el fin de
                // jornada, esto lo sigue.
                evaluarHorario() {
                    const [h, m] = this.horaLimite.split(':').map(Number);
                    const limite = new Date();
                    limite.setHours(h, m, 0, 0);

                    const ahora = new Date();
                    const diffMin = Math.round((limite - ahora) / 60000);

                    this.horarioVencido = diffMin <= 0;
                    this.minutosParaLimite = diffMin;
                    this.avisoHorario = diffMin <= 30;
                },

                cambiarModo(nuevoModo) {
                    if (this.modo === nuevoModo) return;

                    this.modo = nuevoModo;

                    if (nuevoModo === 'escanear') {
                        this.iniciarEscaneo();
                    } else {
                        window.detenerEscaneoQr();

                        if (nuevoModo === 'buscar') {
                            this.$nextTick(() => this.$refs.inputBuscar?.focus());
                        } else if (nuevoModo === 'resumen') {
                            this.cargarResumen();
                        }
                    }
                },

                async cargarResumen() {
                    this.cargandoResumen = true;

                    try {
                        const res = await fetch(`{{ route('vigilancia.resumen') }}`, {
                            headers: { Accept: 'application/json' },
                        });
                        if (!res.ok) throw new Error('respuesta-no-ok');

                        this.resumen = await res.json();
                        this.ultimaActualizacion = new Date();
                        this.huboResumenAntes = true;
                        this.errorResumen = false;
                    } catch (e) {
                        // No se vacía la lista: si ya había datos cargados,
                        // se mantienen en pantalla y solo se avisa que la
                        // actualización falló (wifi inestable de garita) —
                        // ver una lista que se vacía sola es peor que un
                        // aviso de "sin conexión".
                        this.errorResumen = true;
                    } finally {
                        this.cargandoResumen = false;
                    }
                },

                async buscar() {
                    if (this.q.length < 2) {
                        this.resultados = [];
                        this.errorBusqueda = false;
                        return;
                    }

                    this.cargando = true;

                    try {
                        const res = await fetch(`{{ route('vigilancia.buscar') }}?q=${encodeURIComponent(this.q)}`, {
                            headers: { Accept: 'application/json' },
                        });
                        if (!res.ok) throw new Error('respuesta-no-ok');

                        const data = await res.json();
                        this.resultados = data.papeletas ?? [];
                        this.errorBusqueda = false;
                    } catch (e) {
                        // Distingue "sin resultados" (búsqueda válida, nada
                        // que mostrar) de "falló la petición" (sin red) —
                        // antes ambos casos vaciaban resultados en silencio
                        // y se veían igual para el vigilante. Sí se limpian
                        // los resultados viejos: son de una query distinta,
                        // dejarlos puestos sería mostrar datos de otra
                        // búsqueda como si fueran de la actual.
                        this.resultados = [];
                        this.errorBusqueda = true;
                    } finally {
                        this.cargando = false;
                    }
                },

                async iniciarEscaneo() {
                    this.errorCamara = '';
                    this.camaraLista = false;

                    // El video ya está en el DOM (solo oculto con x-show, no
                    // desmontado), así que la cámara puede medir su tamaño
                    // real desde el primer frame en vez de arrancar sobre un
                    // elemento con display:none.
                    await this.$nextTick();

                    // El buscador ya filtra por código de papeleta O dni O
                    // nombre (ver VigilanteController::buscar), así que no
                    // importa qué tipo de QR sea: el texto decodificado se
                    // manda tal cual a la misma búsqueda de siempre.
                    const ok = await window.iniciarEscaneoQr('qr-video', (texto) => {
                        this.q = texto.trim();
                        this.buscar();
                    });

                    if (ok) {
                        this.camaraLista = true;
                    } else {
                        this.errorCamara = 'No se pudo acceder a la cámara. Revisa los permisos del navegador o usa "Buscar".';
                    }
                },

                // AJAX: antes esto armaba un <form> oculto y navegaba de
                // verdad (recarga completa). Ahora se confirma por fetch, se
                // avisa con un toast y se refresca en el sitio la búsqueda
                // (o el resumen "Hoy") — la garita nunca deja de ver la
                // cámara/lista mientras confirma.
                async confirmar(papeleta, tipo) {
                    if (this.confirmando) return;
                    this.confirmando = papeleta.id;

                    const url = tipo === 'salida'
                        ? `/vigilancia/papeletas/${papeleta.id}/salida`
                        : `/vigilancia/papeletas/${papeleta.id}/retorno`;

                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });

                        const data = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            // 422 (ValidationException) trae el motivo real
                            // (p. ej. "Ya pasó el horario límite…"); si no
                            // hay detalle, se cae al mensaje genérico.
                            const detalle = data.errors ? Object.values(data.errors).flat()[0] : null;
                            throw new Error(detalle || data.mensaje || 'respuesta-no-ok');
                        }

                        window.toastAccion?.(data.mensaje ?? 'Confirmado.', 'ok');

                        // Ya no debería seguir en la lista de resultados de
                        // búsqueda (ya no puede volver a marcar lo mismo).
                        this.resultados = this.resultados.filter((r) => r.id !== papeleta.id);

                        if (this.modo === 'resumen') this.cargarResumen();
                    } catch (e) {
                        window.toastAccion?.(e.message && e.message !== 'respuesta-no-ok' ? e.message : 'No se pudo confirmar. Intenta de nuevo.', 'error');
                    } finally {
                        this.confirmando = null;
                    }
                },
            };
        }
    </script>
</x-app-layout>
