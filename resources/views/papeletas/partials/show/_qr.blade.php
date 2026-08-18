{{--
    Se muestra apenas debajo del estado, antes que cualquier otra cosa: para
    el trabajador en APROBADO_RRHH/EN_CURSO, este código es la única acción
    pendiente real (mostrarlo en la puerta), así que no debería quedar
    enterrado al final de la pantalla.
--}}
@can('verCodigo', $papeleta)
    <div class="glass-card p-5 mb-4 animate-fade-in-up text-center"
         x-data="cooldownCodigo({{ $papeleta->segundosRestantesParaCodigo() }}, @js($papeleta->codigo))"
         x-init="init()">
        <h2 class="font-semibold text-sm text-gray-700 mb-1">
            {{ $papeleta->yaMarcoSalida() ? 'Muestra este código para tu retorno' : 'Muestra este código para tu salida' }}
        </h2>

        <template x-if="listo">
            <div>
                <p class="text-xs text-gray-400 mb-3">El vigilante lo escanea o lo busca por este código</p>
                <canvas id="qr-papeleta" class="mx-auto rounded-lg bg-white p-2 shadow-sm"></canvas>
                <p class="text-sm font-mono font-semibold text-gray-700 mt-3 tracking-wide">{{ $papeleta->codigo }}</p>
            </div>
        </template>

        <template x-if="!listo">
            <div class="py-2">
                <p class="text-xs text-gray-400 mb-2">
                    Por seguridad, el código se habilita en unos segundos para evitar una marcación doble por error.
                </p>
                <p class="text-2xl font-mono font-semibold text-gray-500" x-text="restante + 's'"></p>
            </div>
        </template>
    </div>
@endcan

<script>
    function cooldownCodigo(segundosIniciales, codigo) {
        return {
            restante: segundosIniciales,
            listo: segundosIniciales <= 0,
            intervalo: null,

            init() {
                if (this.listo) {
                    // El x-if hijo todavía no insertó el <canvas> en el DOM
                    // en este mismo tick — sin $nextTick, getElementById no
                    // lo encuentra y no dibuja nada.
                    this.$nextTick(() => this.dibujar());
                    return;
                }

                this.intervalo = setInterval(() => {
                    this.restante -= 1;
                    if (this.restante <= 0) {
                        clearInterval(this.intervalo);
                        this.listo = true;
                        this.$nextTick(() => this.dibujar());
                    }
                }, 1000);

                window.addEventListener('beforeunload', () => {
                    if (this.intervalo) clearInterval(this.intervalo);
                });
            },

            dibujar() {
                const intentar = (reintentos) => {
                    const canvas = document.getElementById('qr-papeleta');

                    if (!canvas) {
                        // Colchón extra por si el x-if aún no montó el canvas
                        // ni con $nextTick.
                        if (reintentos > 0) setTimeout(() => intentar(reintentos - 1), 50);
                        return;
                    }

                    if (typeof window.dibujarQrPapeleta === 'function') {
                        window.dibujarQrPapeleta('qr-papeleta', codigo);
                    } else {
                        // "app.js" (type="module") puede no haber terminado
                        // de ejecutarse todavía.
                        document.addEventListener('DOMContentLoaded', () => intentar(reintentos), { once: true });
                    }
                };
                intentar(10);
            },
        };
    }
</script>