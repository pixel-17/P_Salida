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
const SONIDO_KEY = 'papeletas_sonido_tipo';

function obtenerVolumen() {
    const guardado = parseFloat(localStorage.getItem(VOLUMEN_KEY));
    return Number.isFinite(guardado) ? Math.min(1, Math.max(0, guardado)) : 0.6;
}

function guardarVolumen(valor) {
    localStorage.setItem(VOLUMEN_KEY, String(Math.min(1, Math.max(0, valor))));
}

function obtenerSonido() {
    return localStorage.getItem(SONIDO_KEY) || 'campanita';
}

function guardarSonido(id) {
    localStorage.setItem(SONIDO_KEY, id);
}

/**
 * Todas las variantes se sintetizan con Web Audio API: ningún archivo de
 * audio externo, cero peso extra en el bundle. `pico` es el volumen máximo
 * de cada nota antes de aplicar el multiplicador de volumen del usuario
 * (0-1, guardado en localStorage) — cada sonido trae su propio balance
 * relativo entre notas, el usuario solo sube/baja el conjunto.
 */
const SONIDOS = {
    // Campanita: arpegio ascendente Do-Mi-Sol, onda triangular + pasa-bajos.
    // El sonido original de la app.
    campanita: {
        etiqueta: 'Campanita',
        notas: [
            { frecuencia: 523.25, inicio: 0, duracion: 0.35, pico: 0.16 },
            { frecuencia: 659.25, inicio: 0.09, duracion: 0.35, pico: 0.16 },
            { frecuencia: 783.99, inicio: 0.18, duracion: 0.35, pico: 0.16 },
        ],
        onda: 'triangle',
        pasaBajos: 4000,
    },
    // Suave: una sola nota cálida, ataque lento, decaimiento largo — casi
    // imperceptible en volumen bajo, agradable para oficina.
    suave: {
        etiqueta: 'Suave',
        notas: [
            { frecuencia: 440, inicio: 0, duracion: 0.6, pico: 0.18 },
        ],
        onda: 'sine',
        pasaBajos: 2500,
    },
    // Xilófono: dos notas cortas y brillantes, ataque rápido.
    xilofono: {
        etiqueta: 'Xilófono',
        notas: [
            { frecuencia: 987.77, inicio: 0, duracion: 0.2, pico: 0.14 },
            { frecuencia: 1318.51, inicio: 0.08, duracion: 0.22, pico: 0.14 },
        ],
        onda: 'triangle',
        pasaBajos: 5000,
    },
    // Marimba: acorde descendente de 3 notas, tono grave y redondo.
    marimba: {
        etiqueta: 'Marimba',
        notas: [
            { frecuencia: 392.0, inicio: 0, duracion: 0.45, pico: 0.18 },
            { frecuencia: 329.63, inicio: 0.1, duracion: 0.45, pico: 0.16 },
            { frecuencia: 261.63, inicio: 0.2, duracion: 0.5, pico: 0.16 },
        ],
        onda: 'sine',
        pasaBajos: 1800,
    },
};

/**
 * Reproduce el sonido de aviso elegido por el usuario (localStorage), al
 * volumen que también eligió. `idSonido` y `volumen` son opcionales: si no
 * se pasan, se usan las preferencias guardadas — así `probarSonido()` (el
 * botón "Probar" en Notificaciones y app) puede previsualizar una
 * combinación sin haberla guardado todavía.
 */
function reproducirSonidoAviso(idSonido, volumen) {
    try {
        const definicion = SONIDOS[idSonido || obtenerSonido()] || SONIDOS.campanita;
        const multiplicador = volumen ?? obtenerVolumen();
        if (multiplicador <= 0) return;

        const Ctx = window.AudioContext || window.webkitAudioContext;
        const ctx = new Ctx();
        const ahora = ctx.currentTime;

        // Pasa-bajos suave: sin esto, la onda triangular suena metálica.
        // Con el filtro queda cálida, como una campanita, no un beep.
        const filtro = ctx.createBiquadFilter();
        filtro.type = 'lowpass';
        filtro.frequency.value = definicion.pasaBajos;
        filtro.connect(ctx.destination);

        definicion.notas.forEach((nota) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = definicion.onda;
            osc.frequency.value = nota.frecuencia;

            const inicio = ahora + nota.inicio;
            gain.gain.setValueAtTime(0, inicio);
            gain.gain.linearRampToValueAtTime(nota.pico * multiplicador, inicio + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.001, inicio + nota.duracion);

            osc.connect(gain).connect(filtro);
            osc.start(inicio);
            osc.stop(inicio + nota.duracion + 0.05);
        });

        setTimeout(() => ctx.close(), 900);
    } catch (e) {
        // Navegadores que bloquean audio sin interacción previa: se ignora,
        // la campana/contador ya se actualizó visualmente igual.
    }
}

// Expuesto global: lo usa el panel "Notificaciones y app" del perfil para
// el slider de volumen, el selector de sonido y el botón "Probar".
window.sonidoAvisoOpciones = Object.entries(SONIDOS).map(([id, def]) => ({ id, etiqueta: def.etiqueta }));
window.sonidoAvisoObtenerVolumen = obtenerVolumen;
window.sonidoAvisoGuardarVolumen = guardarVolumen;
window.sonidoAvisoObtenerSonido = obtenerSonido;
window.sonidoAvisoGuardarSonido = guardarSonido;
window.sonidoAvisoProbar = (idSonido, volumen) => reproducirSonidoAviso(idSonido, volumen);

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
