{{--
    Se muestra apenas debajo del estado, antes que cualquier otra cosa: para
    el trabajador en APROBADO_RRHH/EN_CURSO, este código es la única acción
    pendiente real (mostrarlo en la puerta), así que no debería quedar
    enterrado al final de la pantalla.
--}}
@php
    // Papeleta aprobada por RRHH pero para una fecha futura: la fecha
    // autorizada no es negociable (ver PapeletaPolicy::verCodigo /
    // MarcarSalidaVigilanteAction), así que el código todavía no se
    // muestra. En vez de no mostrar nada, se avisa para qué fecha quedó
    // autorizada la salida.
    $esperandoFechaAutorizada = auth()->id() === $papeleta->trabajador_id
        && $papeleta->estaEn(\App\Enums\EstadoPapeleta::APROBADO_RRHH)
        && ! $papeleta->esHoyFechaDeSalida();
@endphp

@can('verCodigo', $papeleta)
    <div class="glass-card p-5 mb-4 animate-fade-in-up text-center"
         x-data="qrPapeleta({{ $papeleta->segundosRestantesParaCodigo() }}, @js($papeleta->codigo))"
         x-init="init()">
        <h2 class="font-semibold text-sm text-gray-700 mb-1">
            {{ $papeleta->yaMarcoSalida() ? 'Muestra este código para tu retorno' : 'Muestra este código para tu salida' }}
        </h2>

        <template x-if="listo">
            <div>
                <p class="text-xs text-gray-400 mb-3">El vigilante lo escanea o lo busca por este código</p>

                {{-- Un solo toque no hace nada (para no abrir pantalla
                completa sin querer al solo mirar el código); doble toque
                o doble clic lo pone en pantalla completa, listo para que
                el vigilante lo escanee de cerca. --}}
                <div
                    class="inline-block cursor-pointer select-none"
                    @click="tap()"
                    @dblclick.prevent="abrirPantallaCompleta()"
                    role="button"
                    aria-label="Doble toque para ver el código en pantalla completa"
                >
                    <canvas id="qr-papeleta" class="mx-auto rounded-lg bg-white p-2 shadow-sm"></canvas>
                </div>

                <p class="text-sm font-mono font-semibold text-gray-700 mt-3 tracking-wide">{{ $papeleta->codigo }}</p>
                <p class="text-[11px] text-gray-400 mt-1">Doble toque en el código para ampliarlo</p>
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

        {{-- ---------- Pantalla completa (para el escaneo) ---------- --}}
        <div
            x-show="pantallaCompleta"
            x-cloak
            x-transition.opacity
            @click="cerrarPantallaCompleta()"
            @keydown.escape.window="cerrarPantallaCompleta()"
            class="fixed inset-0 z-50 bg-white flex flex-col items-center justify-center gap-5 p-6"
            style="touch-action: manipulation;"
        >
            <canvas id="qr-papeleta-completo" class="rounded-xl"></canvas>
            <p class="text-lg font-mono font-semibold text-gray-800 tracking-wide" x-text="codigo"></p>
            <p class="text-sm text-gray-400">Toca la pantalla o presiona atrás para volver</p>
        </div>
    </div>
@elseif($esperandoFechaAutorizada)
    <div class="glass-card p-5 mb-4 animate-fade-in-up text-center">
        <h2 class="font-semibold text-sm text-gray-700 mb-1">Aún no puedes mostrar el código</h2>
        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
            Solicitaste tu salida para el
            <span class="font-semibold text-gray-700">{{ $papeleta->fecha_salida->format('d/m/Y') }}</span>.
            @if($papeleta->diasParaSalida() > 0)
                El código se habilita recién ese día — falta{{ $papeleta->diasParaSalida() === 1 ? '' : 'n' }}
                {{ $papeleta->diasParaSalida() }} {{ Str::plural('día', $papeleta->diasParaSalida()) }}.
            @else
                La fecha autorizada ya pasó, así que el código ya no está disponible.
            @endif
        </p>
    </div>
@endcan

<script>
    function qrPapeleta(segundosIniciales, codigo) {
        return {
            codigo,
            restante: segundosIniciales,
            listo: segundosIniciales <= 0,
            intervalo: null,
            pantallaCompleta: false,
            ultimoTap: 0,

            init() {
                if (this.listo) {
                    // El x-if hijo todavía no insertó el <canvas> en el DOM
                    // en este mismo tick — sin $nextTick, getElementById no
                    // lo encuentra y no dibuja nada.
                    this.$nextTick(() => this.dibujar('qr-papeleta'));
                } else {
                    this.intervalo = setInterval(() => {
                        this.restante -= 1;
                        if (this.restante <= 0) {
                            clearInterval(this.intervalo);
                            this.listo = true;
                            this.$nextTick(() => this.dibujar('qr-papeleta'));
                        }
                    }, 1000);

                    window.addEventListener('beforeunload', () => {
                        if (this.intervalo) clearInterval(this.intervalo);
                    });
                }

                // Si el trabajador entra a pantalla completa y usa el botón
                // "atrás" del celular (o del navegador), esto cierra la
                // pantalla completa en vez de sacarlo de la papeleta — ver
                // abrirPantallaCompleta/cerrarPantallaCompleta.
                window.addEventListener('popstate', () => {
                    if (this.pantallaCompleta) this.pantallaCompleta = false;
                });
            },

            // Detecta el doble toque a mano (además de @dblclick, que en
            // algunos navegadores móviles no dispara de forma confiable
            // sobre <canvas>): si el segundo toque llega dentro de 350ms
            // del primero, cuenta como doble toque.
            tap() {
                const ahora = Date.now();
                if (ahora - this.ultimoTap < 350) {
                    this.ultimoTap = 0;
                    this.abrirPantallaCompleta();
                } else {
                    this.ultimoTap = ahora;
                }
            },

            abrirPantallaCompleta() {
                if (this.pantallaCompleta || !this.listo) return;

                this.pantallaCompleta = true;

                // Estado extra en el historial: así el botón "atrás" cierra
                // la pantalla completa (ver el listener de popstate en
                // init()) en vez de navegar fuera de la papeleta.
                history.pushState({ qrPantallaCompleta: true }, '');

                this.$nextTick(() => this.dibujar('qr-papeleta-completo', 320));
            },

            cerrarPantallaCompleta() {
                if (!this.pantallaCompleta) return;

                this.pantallaCompleta = false;

                // Saca del historial el estado que empujó abrirPantallaCompleta,
                // para no dejar una entrada "fantasma" que obligue a un
                // segundo "atrás" para salir de verdad de la papeleta.
                if (history.state?.qrPantallaCompleta) history.back();
            },

            dibujar(canvasId, size) {
                const intentar = (reintentos) => {
                    const canvas = document.getElementById(canvasId);

                    if (!canvas) {
                        // Colchón extra por si el x-if/x-show aún no montó
                        // el canvas ni con $nextTick.
                        if (reintentos > 0) setTimeout(() => intentar(reintentos - 1), 50);
                        return;
                    }

                    if (typeof window.dibujarQrPapeleta === 'function') {
                        window.dibujarQrPapeleta(canvasId, codigo, size);
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
