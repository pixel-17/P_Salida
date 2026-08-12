

import Alpine from 'alpinejs';
import { registrarStoreNotificaciones } from './notificaciones';
import './progress-bar';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    registrarStoreNotificaciones(Alpine);
});

Alpine.start();

// Cargados de forma perezosa (code-splitting): solo se descargan en las
// páginas que realmente los usan (papeleta del trabajador / control de
// puerta del vigilante), no en el resto de la app.
window.dibujarQrPapeleta = async (...args) => {
    const { dibujarQrPapeleta } = await import('./qr-papeleta');
    return dibujarQrPapeleta(...args);
};

window.iniciarEscaneoQr = async (...args) => {
    const { iniciarEscaneoQr } = await import('./qr-vigilancia');
    return iniciarEscaneoQr(...args);
};

window.detenerEscaneoQr = async (...args) => {
    const { detenerEscaneoQr } = await import('./qr-vigilancia');
    return detenerEscaneoQr(...args);
};
