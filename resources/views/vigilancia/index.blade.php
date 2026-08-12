<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <x-application-logo class="w-7 h-7 fill-current text-brand-600" />
            <h2 class="font-bold text-2xl text-gray-800 tracking-tight">Control de puerta</h2>
        </div>
    </x-slot>

    <div x-data="controlPuerta()" x-init="init()" class="max-w-xl mx-auto">

        {{-- Mismo patrón de pestañas glass que "Mis papeletas" / "Bandeja"
        del trabajador y del jefe — misma sensación de app en ambos roles. --}}
        <div class="flex gap-1 mb-4 glass p-1 rounded-xl">
            <button
                type="button"
                @click="cambiarModo('resumen')"
                class="flex-1 text-sm px-4 py-2 rounded-lg font-semibold transition-all duration-200"
                :class="modo === 'resumen' ? 'bg-white shadow-sm text-brand-700' : 'text-gray-500 hover:text-gray-700'"
            >
                Hoy
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
            <template x-if="cargandoResumen">
                <div class="glass-card p-8 text-center animate-fade-in-up">
                    <span class="inline-block w-6 h-6 border-2 border-brand-200 border-t-brand-600 rounded-full animate-spin"></span>
                    <p class="text-sm text-gray-400 mt-2">Cargando…</p>
                </div>
            </template>

            <template x-if="!cargandoResumen">
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

            <template x-if="!cargando && q.length >= 2 && resultados.length === 0">
                <p class="text-center text-sm text-gray-400 py-6">Sin resultados para "<span x-text="q"></span>".</p>
            </template>

            <div class="space-y-3">
                <template x-for="p in resultados" :key="p.id">
                    <div class="glass-card p-4 animate-fade-in-up">
                        <p class="font-bold text-gray-800" x-text="p.trabajador"></p>
                        <p class="text-sm text-gray-500">DNI <span x-text="p.dni"></span> · <span x-text="p.codigo"></span></p>

                        <div class="mt-3 flex gap-2">
                            <button
                                x-show="p.puede_salida"
                                :disabled="confirmando === p.id"
                                @click="confirmar(p, 'salida')"
                                class="flex-1 btn-glass text-white shadow-glass justify-center text-base py-3"
                                style="background: linear-gradient(135deg, #3b6cf6 0%, #6d5bf0 100%);"
                            >
                                <span x-show="confirmando !== p.id">Confirmar salida</span>
                                <span x-show="confirmando === p.id" x-cloak>Confirmando…</span>
                            </button>
                            <button
                                x-show="p.puede_retorno"
                                :disabled="confirmando === p.id"
                                @click="confirmar(p, 'retorno')"
                                class="flex-1 btn-glass text-white shadow-glass justify-center text-base py-3"
                                style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"
                            >
                                <span x-show="confirmando !== p.id">Confirmar retorno</span>
                                <span x-show="confirmando === p.id" x-cloak>Confirmando…</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function controlPuerta() {
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

                init() {
                    // Arranca en "Hoy" (resumen de la sede), no en la cámara:
                    // el vigilante ve primero quién falta/está fuera y recién
                    // activa la cámara cuando de verdad va a escanear.
                    this.cargarResumen();

                    // Si se navega fuera de la pantalla, apagar la cámara.
                    window.addEventListener('beforeunload', () => window.detenerEscaneoQr());
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
                        this.resumen = await res.json();
                    } catch (e) {
                        this.resumen = { pendientes: [], en_curso: [], finalizadas: [] };
                    } finally {
                        this.cargandoResumen = false;
                    }
                },

                async buscar() {
                    if (this.q.length < 2) {
                        this.resultados = [];
                        return;
                    }

                    this.cargando = true;

                    try {
                        const res = await fetch(`{{ route('vigilancia.buscar') }}?q=${encodeURIComponent(this.q)}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const data = await res.json();
                        this.resultados = data.papeletas ?? [];
                    } catch (e) {
                        this.resultados = [];
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

                        if (!res.ok) throw new Error('respuesta-no-ok');

                        const data = await res.json().catch(() => ({}));
                        window.toastAccion?.(data.mensaje ?? 'Confirmado.', 'ok');

                        // Ya no debería seguir en la lista de resultados de
                        // búsqueda (ya no puede volver a marcar lo mismo).
                        this.resultados = this.resultados.filter((r) => r.id !== papeleta.id);

                        if (this.modo === 'resumen') this.cargarResumen();
                    } catch (e) {
                        window.toastAccion?.('No se pudo confirmar. Intenta de nuevo.', 'error');
                    } finally {
                        this.confirmando = null;
                    }
                },
            };
        }
    </script>
</x-app-layout>
