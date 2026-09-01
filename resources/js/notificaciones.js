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

const VOLUMEN_KEY = 'papeletas_sonido_volumen';

function obtenerVolumen() {
    const guardado = parseFloat(localStorage.getItem(VOLUMEN_KEY));
    return Number.isFinite(guardado) ? Math.min(1, Math.max(0, guardado)) : 0.6;
}

function guardarVolumen(valor) {
    localStorage.setItem(VOLUMEN_KEY, String(Math.min(1, Math.max(0, valor))));
}

/**
 * Sonido de aviso: reconstruido en Web Audio API a partir de un mp3 de
 * referencia que pasó el usuario (tono de notificación de un widget de
 * live chat), analizando su espectro — no es el archivo, es la misma
 * "receta" tocada con dos osciloscopios. Dos notas sinusoidales puras
 * (sin armónicos: se confirmó vía FFT que no es triángulo/cuadrada),
 * la segunda una octava justa arriba de la primera y mucho más sostenida
 * — el clásico "ding... DING" de aviso de mensaje:
 *   - Nota 1: D6 (1174.66 Hz), ataque ~15ms, decae en ~0.18s.
 *   - Nota 2: D7 (2349.32 Hz, la octava), entra ~0.174s después del
 *     inicio de la nota 1 (justo cuando esta ya bajó de volumen), decae
 *     mucho más lento, ~0.9s — es la que se queda "sonando" al final.
 * `pico` en cada nota es el volumen máximo de esa nota antes de aplicar
 * el multiplicador de volumen del usuario (0-1, guardado en localStorage).
 */
const SONIDO = {
    notas: [
        { frecuencia: 1174.66, inicio: 0, duracion: 0.18, pico: 0.20 },
        { frecuencia: 2349.32, inicio: 0.174, duracion: 0.9, pico: 0.22 },
    ],
    onda: 'sine',
};

/**
 * Reproduce el sonido de aviso al volumen que el usuario eligió. `volumen`
 * es opcional: si no se pasa, se usa la preferencia guardada — así
 * `probarSonido()` (el botón "Probar" en Notificaciones y app) puede
 * previsualizar un volumen sin haberlo guardado todavía.
 */
function reproducirSonidoAviso(volumen) {
    try {
        const multiplicador = volumen ?? obtenerVolumen();
        if (multiplicador <= 0) return;

        const Ctx = window.AudioContext || window.webkitAudioContext;
        const ctx = new Ctx();
        const ahora = ctx.currentTime;

        SONIDO.notas.forEach((nota) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = SONIDO.onda;
            osc.frequency.value = nota.frecuencia;

            const inicio = ahora + nota.inicio;
            gain.gain.setValueAtTime(0, inicio);
            gain.gain.linearRampToValueAtTime(nota.pico * multiplicador, inicio + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.001, inicio + nota.duracion);

            osc.connect(gain).connect(ctx.destination);
            osc.start(inicio);
            osc.stop(inicio + nota.duracion + 0.05);
        });

        setTimeout(() => ctx.close(), 1200);
    } catch (e) {
        // Navegadores que bloquean audio sin interacción previa: se ignora,
        // la campana/contador ya se actualizó visualmente igual.
    }
}

// Expuesto global: lo usa el panel "Notificaciones y app" del perfil para
// el slider de volumen y el botón "Probar".
window.sonidoAvisoObtenerVolumen = obtenerVolumen;
window.sonidoAvisoGuardarVolumen = guardarVolumen;
window.sonidoAvisoProbar = (volumen) => reproducirSonidoAviso(volumen);

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
