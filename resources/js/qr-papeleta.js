import QRCode from 'qrcode';

/**
 * Dibuja el QR de una papeleta dentro del <canvas> con el id indicado.
 * El QR codifica únicamente el código de la papeleta (p.ej. "PAP-2026-00042-X7K"),
 * el mismo valor que el vigilante ya podía escribir a mano en la búsqueda —
 * escanear el QR solo evita que lo tenga que tipear.
 *
 * @param {string} canvasId
 * @param {string} codigoPapeleta
 */
export async function dibujarQrPapeleta(canvasId, codigoPapeleta) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    await QRCode.toCanvas(canvas, codigoPapeleta, {
        width: 220,
        margin: 1,
        color: { dark: '#1f2937', light: '#ffffffff' },
    });
}
