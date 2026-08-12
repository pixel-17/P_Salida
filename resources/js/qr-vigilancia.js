import QrScanner from 'qr-scanner';
import QrScannerWorkerPath from 'qr-scanner/qr-scanner-worker.min.js?url';

QrScanner.WORKER_PATH = QrScannerWorkerPath;

let instancia = null;

/**
 * Inicia el escaneo con la cámara sobre el <video> indicado. El vigilante
 * puede escanear el QR de una papeleta (código) o, si el trabajador tiene
 * un carnet con QR de DNI, ese también sirve: onDecoded recibe el texto tal
 * cual salió del QR y quien llama decide qué hacer con él — en la pantalla
 * de vigilancia se manda directo al mismo buscador que ya filtra por
 * código, DNI o nombre, así que cualquiera de los dos formatos funciona
 * sin distinguir uno de otro.
 *
 * @param {string} videoElId
 * @param {(texto: string) => void} onDecoded
 * @returns {Promise<boolean>} true si la cámara pudo iniciarse
 */
export async function iniciarEscaneoQr(videoElId, onDecoded) {
    const video = document.getElementById(videoElId);
    if (!video) return false;

    await detenerEscaneoQr();

    instancia = new QrScanner(
        video,
        (resultado) => onDecoded(resultado.data),
        {
            highlightScanRegion: true,
            highlightCodeOutline: true,
            preferredCamera: 'environment',
            maxScansPerSecond: 5,
        }
    );

    try {
        await instancia.start();
        return true;
    } catch (e) {
        await detenerEscaneoQr();
        return false;
    }
}

export async function detenerEscaneoQr() {
    if (instancia) {
        instancia.stop();
        instancia.destroy();
        instancia = null;
    }
}
