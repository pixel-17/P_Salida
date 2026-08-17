<section
    x-data="{
        soportado: true,
        permiso: 'default',
        suscrito: false,
        instalable: false,
        instalada: false,
        cargando: false,
        mensaje: null,

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
