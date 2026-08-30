<section
    x-data="{
        soportado: true,
        permiso: 'default',
        suscrito: false,
        instalable: false,
        instalada: false,
        cargando: false,
        mensaje: null,

        sonidoOpciones: window.sonidoAvisoOpciones,
        sonidoElegido: window.sonidoAvisoObtenerSonido(),
        volumen: Math.round(window.sonidoAvisoObtenerVolumen() * 100),

        cambiarSonido(id) {
            this.sonidoElegido = id;
            window.sonidoAvisoGuardarSonido(id);
            window.sonidoAvisoProbar(id, this.volumen / 100);
        },

        cambiarVolumen(valor) {
            this.volumen = valor;
            window.sonidoAvisoGuardarVolumen(valor / 100);
        },

        probarSonido() {
            window.sonidoAvisoProbar(this.sonidoElegido, this.volumen / 100);
        },

        async refrescar() {
            const estado = await window.estadoNotificacionesPush();
            this.soportado = estado.soportado;
            this.permiso = estado.permiso;
            this.suscrito = estado.suscrito;
            this.instalable = window.appEsInstalable();
            this.instalada = window.appYaInstalada();
        },

        async permitir() {
            this.cargando = true;
            this.mensaje = null;
            const ok = await window.activarNotificacionesPush();
            this.mensaje = ok ? 'Notificaciones activadas.' : 'No se concedió el permiso.';
            await this.refrescar();
            this.cargando = false;
        },

        async silenciar() {
            this.cargando = true;
            this.mensaje = null;
            await window.silenciarNotificacionesPush();
            this.mensaje = 'Notificaciones silenciadas en este dispositivo.';
            await this.refrescar();
            this.cargando = false;
        },

        async instalar() {
            const resultado = await window.instalarApp();

            if (resultado === 'no-disponible') {
                this.mensaje = window.instruccionesInstalarApp();
                return;
            }

            if (resultado === 'accepted') this.mensaje = 'App instalada.';
            await this.refrescar();
        },
    }"
    x-init="
        refrescar();
        window.addEventListener('pwa-install-available', refrescar);
        window.addEventListener('pwa-install-done', refrescar);
    "
>
    <header>
        <h2 class="text-lg font-bold text-gray-800">
            Notificaciones y app
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            Controla los avisos push de tus papeletas en este dispositivo.
        </p>
    </header>

    <div class="mt-6 space-y-5">

        {{-- Sin soporte del navegador --}}
        <div x-show="!soportado" x-cloak class="text-sm text-gray-500">
            Este navegador no soporta notificaciones push.
        </div>

        <template x-if="soportado">
            <div class="space-y-5">

                {{-- Bloqueadas desde el navegador: no se puede re-preguntar por JS --}}
                <div x-show="permiso === 'denied'" x-cloak class="flex items-start gap-3">
                    <x-icon name="bell" class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-medium text-gray-800">Notificaciones bloqueadas</p>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Las bloqueaste desde el navegador. Para reactivarlas, entra a la configuración
                            del sitio (icono de candado junto a la URL), busca "Notificaciones" y cambia a "Permitir".
                        </p>
                    </div>
                </div>

                {{-- Nunca respondió al permiso --}}
                <div x-show="permiso === 'default'" x-cloak class="flex items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <x-icon name="bell" class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-gray-800">Notificaciones push</p>
                            <p class="text-sm text-gray-500 mt-0.5">Recibe avisos aunque no tengas la página abierta.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-secondary shrink-0" :disabled="cargando" @click="permitir()">
                        Permitir acceso
                    </button>
                </div>

                {{-- Permiso concedido: activo o silenciado --}}
                <div x-show="permiso === 'granted'" x-cloak class="flex items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <x-icon name="bell" class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-gray-800">Notificaciones push</p>
                            <p class="text-sm text-gray-500 mt-0.5" x-text="suscrito ? 'Activas en este dispositivo.' : 'Silenciadas en este dispositivo.'"></p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-secondary shrink-0"
                        :disabled="cargando"
                        x-show="suscrito"
                        @click="silenciar()"
                    >
                        Silenciar notificaciones
                    </button>
                    <button
                        type="button"
                        class="btn-secondary shrink-0"
                        :disabled="cargando"
                        x-show="!suscrito"
                        @click="permitir()"
                    >
                        Activar notificaciones
                    </button>
                </div>

                {{-- Sonido de aviso: independiente del permiso de push, ya
                que también suena para las notificaciones dentro de la app
                (campanita del header) mientras la pestaña está abierta. --}}
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-start gap-3 mb-3">
                        <x-icon name="bell" class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-gray-800">Sonido de aviso</p>
                            <p class="text-sm text-gray-500 mt-0.5">Elige el tono y el volumen del aviso dentro de la app.</p>
                        </div>
                    </div>

                    <div class="pl-8 space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="opcion in sonidoOpciones" :key="opcion.id">
                                <button
                                    type="button"
                                    @click="cambiarSonido(opcion.id)"
                                    :class="sonidoElegido === opcion.id
                                        ? 'bg-brand-600 text-white border-brand-600'
                                        : 'bg-white/60 text-gray-600 border-gray-200 hover:bg-white'"
                                    class="text-xs font-medium px-3 py-1.5 rounded-full border transition-colors"
                                    x-text="opcion.etiqueta"
                                ></button>
                            </template>
                        </div>

                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.59-.71-1.59-1.59v-4.32c0-.88.71-1.59 1.59-1.59h2.24z" />
                            </svg>
                            <input
                                type="range" min="0" max="100" step="5"
                                :value="volumen"
                                @input="cambiarVolumen(Number($event.target.value))"
                                @change="probarSonido()"
                                class="w-full accent-brand-600"
                            >
                            <span class="text-xs text-gray-500 w-9 text-right" x-text="volumen + '%'"></span>
                        </div>

                        <button type="button" class="btn-secondary text-xs !py-1.5 !px-3" @click="probarSonido()">
                            Probar sonido
                        </button>
                    </div>
                </div>

                {{-- Instalar app (PWA) --}}
                <div x-show="!instalada" x-cloak class="flex items-center justify-between gap-4 pt-4 border-t border-gray-100">
                    <div class="flex items-start gap-3">
                        <x-icon name="download" class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-gray-800">Instalar app</p>
                            <p class="text-sm text-gray-500 mt-0.5">Ábrela como app, sin pasar por el navegador.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-secondary shrink-0" @click="instalar()">
                        Instalar app
                    </button>
                </div>

                <p x-show="mensaje" x-cloak x-text="mensaje" class="text-sm text-emerald-600 font-medium"></p>
            </div>
        </template>
    </div>
</section>
