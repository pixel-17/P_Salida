import QRCode from 'qrcode';

/**
 * Dibuja el QR de una papeleta dentro del <canvas> con el id indicado.
 * El QR codifica únicamente el código de la papeleta (p.ej. "PAP-2026-00042-X7K"),
 * el mismo valor que el vigilante ya podía escribir a mano en la búsqueda —
 * escanear el QR solo evita que lo tenga que tipear.
 *
 * @param {string} canvasId
 * @param {string} codigoPapeleta
 * @param {number} [size] Ancho/alto del QR en px. La vista normal usa el
 *   default (220); la vista de pantalla completa (para acercar el celular
 *   al lector) pide un tamaño mayor.
 */
export async function dibujarQrPapeleta(canvasId, codigoPapeleta, size = 220) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    await QRCode.toCanvas(canvas, codigoPapeleta, {
        width: size,
        margin: 1,
        color: { dark: '#1f2937', light: '#ffffffff' },
    });
}
