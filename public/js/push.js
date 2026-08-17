/**
 * Activación de Web Push (notificaciones nativas en pantalla, Android/Windows/desktop).
 *
 * Flujo:
 *  1. El navegador ya registró /sw.js (ver layouts/app.blade.php).
 *  2. Si el usuario nunca respondió al permiso, se muestra un banner propio
 *     (mejor tasa de aceptación que el prompt nativo "a la fría" en el
 *     primer load).
 *  3. Al aceptar: Notification.requestPermission() -> pushManager.subscribe()
 *     con la VAPID public key -> POST /push-subscriptions con el JSON que
 *     entrega el navegador (endpoint + keys.p256dh + keys.auth).
 *  4. Si el permiso ya estaba concedido de una sesión anterior, se
 *     re-verifica/renueva la suscripción en silencio, sin volver a
 *     preguntar nada.
 */

(function () {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !window.VAPID_PUBLIC_KEY) {
        return;
    }

    const CLAVE_DESCARTADO = 'push_prompt_descartado';

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function guardarSuscripcion(subscription) {
        await fetch('/push-subscriptions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(subscription.toJSON()),
        });
    }

    async function suscribir() {
        const registro = await navigator.serviceWorker.ready;

        let subscription = await registro.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registro.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(window.VAPID_PUBLIC_KEY),
            });
        }

        await guardarSuscripcion(subscription);
    }

    function mostrarBanner() {
        if (localStorage.getItem(CLAVE_DESCARTADO)) return;

        const banner = document.createElement('div');
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Activar notificaciones');
        banner.style.cssText = [
            'position:fixed', 'left:16px', 'right:16px', 'bottom:16px', 'z-index:9999',
            'max-width:420px', 'margin:0 auto', 'background:#fff', 'color:#1f2937',
            'border-radius:14px', 'box-shadow:0 10px 30px rgba(0,0,0,.18)',
            'padding:14px 16px', 'display:flex', 'align-items:center', 'gap:12px',
            'font-family:inherit', 'font-size:14px', 'border:1px solid #e5e7eb',
        ].join(';');

        banner.innerHTML = `
            <div style="flex:1">
                <strong style="display:block;margin-bottom:2px;color:#2549ea;">Activar notificaciones</strong>
                <span style="color:#4b5563;">Recibe avisos de tus papeletas aunque no tengas la página abierta.</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <button type="button" data-accion="activar" style="background:#2549ea;color:#fff;border:none;border-radius:8px;padding:6px 12px;font-size:13px;cursor:pointer;">Activar</button>
                <button type="button" data-accion="descartar" style="background:transparent;color:#6b7280;border:none;font-size:12px;cursor:pointer;">Ahora no</button>
            </div>
        `;

        banner.querySelector('[data-accion="descartar"]').addEventListener('click', () => {
            localStorage.setItem(CLAVE_DESCARTADO, '1');
            banner.remove();
        });

        banner.querySelector('[data-accion="activar"]').addEventListener('click', async () => {
            banner.remove();
            try {
                const permiso = await Notification.requestPermission();
                if (permiso === 'granted') {
                    await suscribir();
                } else {
                    localStorage.setItem(CLAVE_DESCARTADO, '1');
                }
            } catch (e) {
                // Si algo falla (navegador sin soporte real, HTTPS faltante, etc.)
                // no rompemos el resto de la app.
            }
        });

        document.body.appendChild(banner);
    }

    window.addEventListener('load', async () => {
        if (!('Notification' in window)) return;

        if (Notification.permission === 'granted') {
            try {
                await suscribir();
            } catch (e) {
                // silencioso: se reintenta en el próximo load
            }
        } else if (Notification.permission === 'default') {
            setTimeout(mostrarBanner, 1500);
        }
        // 'denied': no insistimos, el usuario ya dijo que no desde el navegador.
    });

    async function borrarSuscripcion(endpoint) {
        await fetch('/push-subscriptions', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ endpoint }),
        });
    }

    // Expuesto por si se quiere disparar desde un botón propio (ej. en Perfil).
    window.activarNotificacionesPush = async function () {
        const permiso = await Notification.requestPermission();
        if (permiso === 'granted') {
            await suscribir();
            return true;
        }
        return false;
    };

    /**
     * Silencia las notificaciones push en ESTE dispositivo/navegador:
     * cancela la suscripción del lado del navegador y la marca como
     * inactiva en el servidor (sin borrar el registro, ver destroy()).
     * No afecta la campana/toast dentro de la web, solo el push nativo.
     */
    window.silenciarNotificacionesPush = async function () {
        if (!('serviceWorker' in navigator)) return false;

        const registro = await navigator.serviceWorker.ready;
        const subscription = await registro.pushManager.getSubscription();

        if (!subscription) return true;

        const endpoint = subscription.endpoint;
        await subscription.unsubscribe();
        await borrarSuscripcion(endpoint);
        return true;
    };

    /**
     * Estado actual de las notificaciones push para pintar la UI de Perfil.
     */
    window.estadoNotificacionesPush = async function () {
        const soportado = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

        if (!soportado) {
            return { soportado: false, permiso: 'unsupported', suscrito: false };
        }

        const permiso = Notification.permission;
        let suscrito = false;

        if (permiso === 'granted') {
            try {
                const registro = await navigator.serviceWorker.ready;
                suscrito = !!(await registro.pushManager.getSubscription());
            } catch (e) {
                suscrito = false;
            }
        }

        return { soportado: true, permiso, suscrito };
    };

    // ---------- PWA: "Instalar app" ----------
    // Chrome/Edge/Android disparan este evento cuando la app es instalable;
    // Safari/iOS no lo soportan (ahí no hay forma de disparar el prompt
    // por JS, el usuario instala desde "Compartir > Agregar a inicio").
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.__promptInstalacionApp = event;
        window.dispatchEvent(new CustomEvent('pwa-install-available'));
    });

    window.addEventListener('appinstalled', () => {
        window.__promptInstalacionApp = null;
        window.dispatchEvent(new CustomEvent('pwa-install-done'));
    });

    window.appEsInstalable = function () {
        return !!window.__promptInstalacionApp;
    };

    window.appYaInstalada = function () {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    };

    /**
     * Mensaje manual para cuando no hay prompt automático disponible:
     * Safari/iOS nunca lo dispara, y Chrome/Android puede tardar (necesita
     * cumplir su heurística de "engagement" antes de ofrecerlo).
     */
    window.instruccionesInstalarApp = function () {
        const ua = navigator.userAgent;
        const esIOS = /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
        const esAndroid = /Android/.test(ua);

        if (esIOS) {
            return 'Toca el botón Compartir de Safari y elige "Agregar a inicio".';
        }
        if (esAndroid) {
            return 'Abre el menú (⋮) del navegador y elige "Instalar app" o "Agregar a pantalla de inicio".';
        }
        return 'Abre el menú del navegador y busca la opción "Instalar" o "Agregar a pantalla de inicio".';
    };

    window.instalarApp = async function () {
        const evento = window.__promptInstalacionApp;
        if (!evento) return 'no-disponible';

        evento.prompt();
        const resultado = await evento.userChoice;
        window.__promptInstalacionApp = null;
        return resultado.outcome; // 'accepted' | 'dismissed'
    };
})();
