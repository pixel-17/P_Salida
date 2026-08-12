/**
 * Store global de notificaciones (Alpine.store).
 *
 * Antes: `Alpine.data('notificacionesCampana', ...)` + `x-data="notificacionesCampana()"`
 * creaba una instancia NUEVA (con su propio polling) por cada `<x-campana-notificaciones />`
 * en el DOM. Como el nav renderiza una copia para desktop y otra para mobile
 * (alternadas por CSS, pero ambas presentes), esto duplicaba las peticiones
 * cada 12s. Un store es una única instancia compartida por toda la página:
 * ambas campanas leen el mismo estado, un solo polling.
 *
 * Comportamiento (sin cambios respecto al original):
 *  - Se queda "en espera" (sin hacer nada visible) mientras no hay novedades.
 *  - Cuando el backend reporta notificaciones nuevas, se actualiza la lista,
 *    sube el contador y suena un aviso corto (como una notificación de chat).
 *  - Pausa el polling mientras la pestaña no está visible.
 *  - Además arma una cola de "toasts" (ver notification-toast.blade.php):
 *    banners tipo push que aparecen en pantalla para cada notificación nueva,
 *    no solo el contador de la campana.
 *  - No usa WebSockets/Echo: no hay servidor de broadcasting configurado,
 *    así que esto funciona por polling ligero. Junto con el Web Push que ya
 *    existe para cuando la pestaña está cerrada, cubre el caso de "me entero
 *    casi al instante" sin añadir infraestructura nueva.
 */

const INTERVALO_MS = 12000;

/**
 * Beep sintetizado con Web Audio API (dos tonos cortos, tipo "ding").
 * Se genera en el navegador: no depende de ningún archivo de audio externo.
 */
function reproducirSonidoAviso() {
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        const ctx = new Ctx();
        const ahora = ctx.currentTime;

        [880, 1320].forEach((frecuencia, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.value = frecuencia;

            const inicio = ahora + i * 0.11;
            gain.gain.setValueAtTime(0, inicio);
            gain.gain.linearRampToValueAtTime(0.15, inicio + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, inicio + 0.18);

            osc.connect(gain).connect(ctx.destination);
            osc.start(inicio);
            osc.stop(inicio + 0.2);
        });

        setTimeout(() => ctx.close(), 500);
    } catch (e) {
        // Navegadores que bloquean audio sin interacción previa: se ignora,
        // la campana/contador ya se actualizó visualmente igual.
    }
}

function formatoRelativo(fechaIso) {
    const diffMs = Date.now() - new Date(fechaIso).getTime();
    const minutos = Math.round(diffMs / 60000);

    if (minutos < 1) return 'ahora';
    if (minutos < 60) return `hace ${minutos} min`;

    const horas = Math.round(minutos / 60);
    if (horas < 24) return `hace ${horas} h`;

    const dias = Math.round(horas / 24);
    return `hace ${dias} d`;
}

/**
 * Registra el store una sola vez, en el evento 'alpine:init'
 * (se llama desde app.js antes de Alpine.start()).
 */
export function registrarStoreNotificaciones(Alpine) {
    Alpine.store('notificaciones', {
        cargando: true,
        primeraCarga: true,
        notificaciones: [],
        noLeidas: 0,

        // Cola de banners tipo "push" (ver notification-toast.blade.php).
        // Independiente de `notificaciones` (que alimenta la campana):
        // acá solo entran las que son nuevas desde el último chequeo.
        toasts: [],
        _idsConocidos: new Set(),

        init() {
            this.consultar();

            setInterval(() => {
                if (!document.hidden) this.consultar();
            }, INTERVALO_MS);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.consultar();
            });
        },

        async consultar() {
            try {
                const res = await fetch('/notificaciones', {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) return;

                const data = await res.json();
                const huboNuevas = !this.primeraCarga && data.no_leidas > this.noLeidas;

                if (!this.primeraCarga) {
                    data.notificaciones
                        .filter((n) => !n.leida_at && !this._idsConocidos.has(n.id))
                        .forEach((n) => this.encolarToast(n));
                }

                this.notificaciones = data.notificaciones;
                this.noLeidas = data.no_leidas;
                this._idsConocidos = new Set(data.notificaciones.map((n) => n.id));
                this.cargando = false;
                this.primeraCarga = false;

                if (huboNuevas) reproducirSonidoAviso();
            } catch (e) {
                // Sin conexión momentánea: se queda "en espera" y reintenta
                // en el siguiente ciclo, sin romper la interfaz.
            }
        },

        encolarToast(notificacion) {
            const toast = { ...notificacion, _key: `${notificacion.id}-${Date.now()}` };
            this.toasts.push(toast);

            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t._key !== toast._key);
            }, 7000);
        },

        cerrarToast(key) {
            this.toasts = this.toasts.filter((t) => t._key !== key);
        },

        formatoFecha(fecha) {
            return formatoRelativo(fecha);
        },

        async marcarLeida(notificacion) {
            if (notificacion.leida_at) return;

            notificacion.leida_at = new Date().toISOString();
            this.noLeidas = Math.max(0, this.noLeidas - 1);

            await fetch(`/notificaciones/${notificacion.id}/leida`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
        },

        async marcarTodas() {
            this.notificaciones.forEach((n) => (n.leida_at = n.leida_at || new Date().toISOString()));
            this.noLeidas = 0;

            await fetch('/notificaciones/leidas', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
        },
    });
}
