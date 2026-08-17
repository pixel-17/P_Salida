/**
 * Service Worker del Sistema de Papeletas.
 *
 * Dos responsabilidades:
 *  1. Requisito técnico para que el navegador ofrezca "Instalar app"
 *     (se registra siempre, ver layouts/app.blade.php).
 *  2. Recibir el evento 'push' que llega desde el servidor (vía
 *     App\Jobs\EnviarWebPushJob + minishlink/web-push) y mostrarlo como
 *     notificación nativa del sistema operativo — esto es lo que hace que
 *     aparezca en la pantalla de Windows o en la bandeja de Android,
 *     incluso con el navegador cerrado.
 */

self.addEventListener('install', () => {
    // Activa esta versión del SW de inmediato, sin esperar a que se
    // cierren las pestañas viejas.
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let datos = {};

    try {
        datos = event.data ? event.data.json() : {};
    } catch (e) {
        datos = { title: 'Sistema de Papeletas', body: event.data ? event.data.text() : '' };
    }

    const titulo = datos.title || 'Sistema de Papeletas';
    const opciones = {
        body: datos.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        data: { url: datos.url || '/' },
        // Agrupa notificaciones del mismo tipo en vez de apilar una por una.
        tag: datos.tag || 'papeleta',
        renotify: true,
    };

    event.waitUntil(self.registration.showNotification(titulo, opciones));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((listaClientes) => {
            for (const cliente of listaClientes) {
                if (cliente.url === url && 'focus' in cliente) {
                    return cliente.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});
