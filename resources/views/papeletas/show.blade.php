<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">{{ $papeleta->codigo }}</h2>
    </x-slot>

    @php
        // Mientras la papeleta sigue "viva" (puede cambiar de estado sin que
        // el usuario haga nada: la mueve el jefe, RRHH o el vigilante), esta
        // pantalla hace polling corto y se autorecarga al detectar cambios.
        // En estados terminales no tiene sentido seguir preguntando.
        $estadosFinales = [
            \App\Enums\EstadoPapeleta::FINALIZADO->value,
            \App\Enums\EstadoPapeleta::RECHAZADO->value,
            \App\Enums\EstadoPapeleta::VENCIDA->value,
        ];
        $sigueViva = ! in_array($papeleta->estado->codigo, $estadosFinales, true);
    @endphp

    @if($sigueViva)
        <div
            x-data="estadoEnVivo(@js($papeleta->id))"
            x-init="init()"
        >
            <div x-show="cambio"
                 x-cloak
                 x-transition
                 class="mb-3 flex items-center justify-between glass-card !rounded-xl border-l-4 !border-l-brand-400 text-brand-800 text-sm px-4 py-3">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                    Esta papeleta se actualizó. Recargando…
                </span>
                <button @click="window.refrescarDetallePapeleta ? window.refrescarDetallePapeleta(true) : window.location.reload()" class="font-semibold hover:underline shrink-0">
                    Actualizar ahora
                </button>
            </div>
        </div>

        <script>
            function estadoEnVivo(papeletaId) {
                return {
                    cambio: false,
                    ultimoUpdatedAt: @js($papeleta->updated_at?->toIso8601String()),
                    temporizador: null,

                    init() {
                        this.consultar();

                        this.temporizador = setInterval(() => {
                            if (!document.hidden && !this.cambio) {
                                this.consultar();
                            }
                        }, 5000);

                        window.addEventListener('beforeunload', () => {
                            if (this.temporizador) {
                                clearInterval(this.temporizador);
                            }
                        });
                    },

                    async consultar() {
                        try {
                            const response = await fetch(
                                `/papeletas/${papeletaId}/eventos`,
                                {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    cache: 'no-store',
                                }
                            );

                            if (!response.ok) return;

                            const data = await response.json();

                            if (
                                data.updated_at &&
                                data.updated_at !== this.ultimoUpdatedAt
                            ) {
                                this.cambio = true;
                                this.ultimoUpdatedAt = data.updated_at;

                                if (this.temporizador) {
                                    clearInterval(this.temporizador);
                                }

                                // AJAX: se reemplaza el contenido en vivo (sin
                                // recargar la página entera) apenas 900ms
                                // después, para que se alcance a leer el aviso.
                                setTimeout(async () => {
                                    const ok = window.refrescarDetallePapeleta
                                        ? await window.refrescarDetallePapeleta(true)
                                        : false;
                                    if (!ok) window.location.reload();
                                }, 900);
                            }
                        } catch (error) {
                            console.warn('No se pudo consultar el estado de la papeleta.', error);
                        }
                    },
                };
            }
        </script>
    @endif

    {{--
        Contenedor "vivo": todas las acciones (aprobar/rechazar/observar/
        responder/adjuntar) se interceptan y se envían por fetch en vez de
        hacer un POST con navegación completa. Laravel sigue respondiendo
        normal (redirect 302 -> GET show), así que no hace falta ninguna
        vista/endpoint nuevo: se pide esa misma página por fetch y se
        reemplaza solo este contenedor con el HTML fresco. Si algo falla
        (sin red, sesión expirada, etc.) se hace un fallback a navegación
        real, así la acción nunca se pierde.
    --}}
    <div id="detalle-papeleta">

    <div class="glass-card p-5 mb-4 animate-fade-in-up">
        <div class="flex justify-between items-start mb-3">
            <h1 class="text-lg font-bold text-gray-800">{{ $papeleta->codigo }}</h1>
            <x-status-badge :estado="$papeleta->estado" />
        </div>

        <dl class="text-sm space-y-1.5 text-gray-700">
            <div class="flex justify-between"><dt class="text-gray-500">Trabajador</dt><dd class="font-medium">{{ $papeleta->trabajador->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Destino</dt><dd class="font-medium">{{ $papeleta->destino }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Motivo</dt><dd class="font-medium">{{ $papeleta->motivo->nombre }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Fecha</dt><dd class="font-medium">{{ $papeleta->fecha_salida->format('d/m/Y') }}</dd></div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Horario</dt>
                <dd class="font-medium">{{ $papeleta->hora_salida_programada }} - {{ $papeleta->hora_retorno_programada ?? '—' }}</dd>
            </div>
            @if($papeleta->motivo_detalle)
                <div class="pt-3 border-t border-white/60 mt-2">
                    <dt class="text-gray-500 mb-0.5">Detalle</dt>
                    <dd>{{ $papeleta->motivo_detalle }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Se muestra apenas debajo del estado, antes que cualquier otra cosa:
    para el trabajador en APROBADO_RRHH/EN_CURSO, este código es la única
    acción pendiente real (mostrarlo en la puerta), así que no debería
    quedar enterrado al final de la pantalla. --}}
    @can('verCodigo', $papeleta)
        <div class="glass-card p-5 mb-4 animate-fade-in-up text-center"
             x-data="cooldownCodigo({{ $papeleta->segundosRestantesParaCodigo() }}, @js($papeleta->codigo))"
             x-init="init()">
            <h2 class="font-semibold text-sm text-gray-700 mb-1">
                @if(!$papeleta->yaMarcoSalida())
                    Muestra este código para tu salida
                @else
                    Muestra este código para tu retorno
                @endif
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

    @php
        // Camino "feliz" de una papeleta normal. Si el estado actual no está
        // en esta lista (RECHAZADO, OBSERVADO, VENCIDA), el stepper no se
        // muestra — esos casos ya se explican solos con el badge de estado.
        $pasos = [
            \App\Enums\EstadoPapeleta::SOLICITADO->value => 'Solicitado',
            \App\Enums\EstadoPapeleta::APROBADO_JEFE->value => 'Jefe',
            \App\Enums\EstadoPapeleta::APROBADO_RRHH->value => 'RRHH',
            \App\Enums\EstadoPapeleta::EN_CURSO->value => 'En curso',
            \App\Enums\EstadoPapeleta::FINALIZADO->value => 'Finalizado',
        ];
        $codigoActual = $papeleta->estado->codigo;
        $indiceActual = array_search($codigoActual, array_keys($pasos));
    @endphp

    @if($indiceActual !== false)
        <div class="glass-card p-4 mb-4 animate-fade-in-up overflow-x-auto">
            <div class="flex items-center min-w-max">
                @foreach($pasos as $codigo => $etiqueta)
                    @php $i = $loop->index; @endphp
                    <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center gap-1 shrink-0">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-all duration-300
                                        {{ $i < $indiceActual ? 'bg-emerald-500 text-white' : ($i === $indiceActual ? 'bg-brand-500 text-white ring-4 ring-brand-100' : 'bg-gray-200/70 text-gray-400') }}">
                                @if($i < $indiceActual)
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </div>
                            <span class="text-[10px] font-medium whitespace-nowrap {{ $i <= $indiceActual ? 'text-gray-700' : 'text-gray-400' }}">{{ $etiqueta }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="h-0.5 flex-1 min-w-[1.5rem] mx-1 rounded-full transition-all duration-300 {{ $i < $indiceActual ? 'bg-emerald-400' : 'bg-gray-200/70' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @php
        $esperaConfig = match($papeleta->estado->codigo) {
            \App\Enums\EstadoPapeleta::SOLICITADO->value => [
                'mensaje' => 'Esperando aprobación del jefe',
                'desde' => $papeleta->created_at,
            ],
            \App\Enums\EstadoPapeleta::APROBADO_JEFE->value => [
                'mensaje' => 'Esperando aprobación de RRHH',
                'desde' => optional(
                    $papeleta->flujoAprobaciones
                        ->where('rol', 'JEFE')
                        ->where('accion', \App\Enums\AccionFlujo::APROBADO->value)
                        ->last()
                )->created_at ?? $papeleta->created_at,
            ],
            default => null,
        };
    @endphp

    @if($esperaConfig)
        <div class="glass-card p-4 mb-4 flex items-center gap-3 animate-fade-in-up">
            <span class="inline-block w-6 h-6 border-2 border-brand-200 border-t-brand-600 rounded-full animate-spin shrink-0"></span>
            <div>
                <p class="text-sm font-semibold text-gray-700">{{ $esperaConfig['mensaje'] }}</p>
                <p class="text-xs text-gray-400" data-tiempo-espera
                   data-desde="{{ $esperaConfig['desde']->toIso8601String() }}">
                    calculando…
                </p>
            </div>
        </div>
    @endif

    @php
        $pideSustento = $papeleta->observaciones
            ->where('atendida', false)
            ->contains(fn ($o) => $o->tipo === \App\Enums\TipoObservacion::JUSTIFICACION);
    @endphp

    @if($papeleta->adjuntos->isNotEmpty() || $papeleta->motivo->requiere_documento || $pideSustento)
        <div class="glass-card p-5 mb-4 animate-fade-in-up">
            <h2 class="font-semibold text-sm text-gray-700 mb-2">Documento sustentatorio</h2>
            <p class="text-xs text-gray-400 mb-3">
                @if($pideSustento)
                    Te observaron pidiendo sustento — adjunta un documento para responder.
                @elseif($papeleta->adjuntos->isEmpty())
                    Este motivo requiere adjuntar un documento (solo se admite uno).
                @endif
            </p>

            @foreach($papeleta->adjuntos as $adjunto)
                <div class="flex items-center justify-between text-sm rounded-xl bg-white/50 border border-white/60 p-2.5 mb-2">
                    <a href="{{ route('adjuntos.download', $adjunto) }}" target="_blank" class="text-brand-600 hover:text-brand-800 truncate flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        {{ $adjunto->nombre_original }}
                    </a>
                    @if($papeleta->trabajador_id === auth()->id())
                        <form method="POST" action="{{ route('adjuntos.destroy', $adjunto) }}"
                              data-confirm="¿Eliminar este documento?">
                            @csrf
                            @method('DELETE')
                            <button class="text-rose-500 hover:text-rose-700 text-xs font-medium">Quitar</button>
                        </form>
                    @endif
                </div>
            @endforeach

            @can('adjuntar', $papeleta)
                <form method="POST" action="{{ route('adjuntos.store', $papeleta) }}"
                      enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <input type="file" name="archivo" required accept=".pdf,.jpg,.jpeg,.png"
                           class="input-glass text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-100 file:text-brand-700 file:font-medium file:text-xs">
                    <p class="text-xs text-gray-400">PDF, JPG o PNG, máx. 5MB.</p>
                    <button class="btn-secondary w-full justify-center">
                        {{ $pideSustento ? 'Subir y responder observación' : 'Subir documento' }}
                    </button>
                </form>
            @else
                @if($papeleta->adjuntos->isEmpty())
                    <p class="text-sm text-gray-400">Aún no se adjunta ningún documento.</p>
                @endif
            @endcan
        </div>
    @endif

    @if($papeleta->observaciones->isNotEmpty())
        <div class="glass-card p-5 mb-4 animate-fade-in-up">
            <h2 class="font-semibold text-sm text-gray-700 mb-3">Observaciones</h2>
            <div class="space-y-3">
                @foreach($papeleta->observaciones as $observacion)
                    <div class="rounded-xl p-3 text-sm border {{ $observacion->atendida ? 'bg-white/40 border-white/60' : 'bg-amber-50/70 border-amber-200/70' }}">
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-xs font-semibold text-gray-500">
                                {{ $observacion->tipo->label() }}
                            </span>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap {{ $observacion->atendida ? 'bg-gray-200/80 text-gray-600' : 'bg-amber-200/80 text-amber-800' }}">
                                {{ $observacion->atendida ? 'Respondida' : 'Pendiente de respuesta' }}
                            </span>
                        </div>
                        <p class="mt-1 text-gray-700">{{ $observacion->comentario }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">
                            {{ $observacion->usuario?->name ?? 'Sistema' }} — {{ $observacion->created_at->diffForHumans() }}
                        </p>
                    </div>
                @endforeach
            </div>

            @can('responderObservacion', $papeleta)
                @if($pideSustento)
                    <p class="text-xs text-gray-400 mt-3">
                        Sube el documento de sustento arriba — al subirlo, esta observación queda respondida automáticamente.
                    </p>
                @else
                    <form method="POST" action="{{ route('papeletas.responder-observacion', $papeleta) }}" class="mt-3 space-y-2">
                        @csrf
                        <textarea name="respuesta" required placeholder="Escribe tu respuesta a la observación..."
                                  class="input-glass text-sm" rows="3"></textarea>
                        <button class="btn-primary w-full justify-center">
                            Enviar respuesta
                        </button>
                        <p class="text-xs text-gray-400">
                            Al responder, tu papeleta vuelve a revisión de quien la observó.
                        </p>
                    </form>
                @endif
            @endcan
        </div>
    @endif

    @can('decidir', $papeleta)
        <div class="glass-card p-5 mb-4 space-y-3 animate-fade-in-up">
            <h2 class="font-semibold text-sm text-gray-700">Acciones</h2>

            <form method="POST" action="{{ route('papeletas.aprobar', $papeleta) }}"
                  x-data="{ confirmado: false }"
                  @submit="confirmado = true">
                @csrf
                <button type="submit"
                        class="btn-glass text-white shadow-glass w-full justify-center relative overflow-hidden"
                        style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"
                        :disabled="confirmado">
                    <span x-show="!confirmado" class="flex items-center gap-2">Aprobar</span>
                    <span x-show="confirmado" x-cloak class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-scale-in" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        ¡Aprobado!
                    </span>
                </button>
            </form>

            <form method="POST" action="{{ route('papeletas.rechazar', $papeleta) }}" class="space-y-2">
                @csrf
                <textarea name="comentario" required placeholder="Motivo del rechazo"
                          class="input-glass text-sm" rows="2"></textarea>
                <button class="btn-danger w-full justify-center">Rechazar</button>
            </form>

            <form method="POST" action="{{ route('papeletas.observar', $papeleta) }}" class="space-y-2">
                @csrf
                <select name="tipo" required class="input-glass text-sm">
                    <option value="ADMINISTRATIVA">Observación administrativa</option>
                    <option value="JUSTIFICACION">Requiere justificación</option>
                </select>
                <textarea name="comentario" required placeholder="Detalle de la observación"
                          class="input-glass text-sm" rows="2"></textarea>
                <button class="btn-glass text-white shadow-glass w-full justify-center" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">Observar</button>
            </form>
        </div>
    @endcan

    @if($papeleta->marcaciones->isNotEmpty())
        <div class="glass-card p-5 mb-4 animate-fade-in-up">
            <h2 class="font-semibold text-sm text-gray-700 mb-3">Historial de marcación</h2>
            <div class="space-y-2">
                @foreach($papeleta->marcaciones as $marcacion)
                    <div class="rounded-xl bg-white/40 border border-white/60 p-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-gray-700">
                                {{ $marcacion->tipo === \App\Enums\TipoMarcacion::SALIDA ? 'Salida' : 'Retorno' }}
                            </span>

                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-100/80 text-emerald-700">
                                ✓ Confirmado por vigilante
                            </span>
                        </div>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ $marcacion->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="glass-card p-5 animate-fade-in-up">
        <h2 class="font-semibold text-sm text-gray-700 mb-2">Historial</h2>
        <ul class="text-xs text-gray-600 space-y-2">
            @foreach($papeleta->historial as $item)
                <li class="border-b border-white/60 pb-2 last:border-0">
                    <span class="font-medium text-gray-700">{{ $item->accion }}</span>
                    — {{ $item->usuario?->name ?? 'Sistema' }}
                    <span class="text-gray-400">({{ $item->created_at->diffForHumans() }})</span>
                </li>
            @endforeach
        </ul>
    </div>

    </div>{{-- /#detalle-papeleta --}}

    <script>
        (function () {
            const contenedor = document.getElementById('detalle-papeleta');
            if (!contenedor) return;

            // El QR ya no se dibuja aquí: lo maneja el componente Alpine
            // "cooldownCodigo" (definido más abajo), que además controla la
            // ventana de espera antes de mostrarlo. Se auto-inicializa solo
            // en cada bloque que muestre el código, incluso tras un
            // reemplazo de innerHTML vía aplicarFragmento (Alpine detecta
            // los nuevos x-data automáticamente).

            function iniciarTiempoEsperaWatcher() {
                contenedor.querySelectorAll('[data-tiempo-espera]').forEach(function (el) {
                    if (el.dataset.timerAttached) return;
                    el.dataset.timerAttached = '1';

                    const desde = new Date(el.dataset.desde);

                    function actualizar() {
                        const diffMs = Math.max(0, Date.now() - desde.getTime());
                        const minutosTotales = Math.floor(diffMs / 60000);
                        const horas = Math.floor(minutosTotales / 60);
                        const minutos = minutosTotales % 60;

                        el.textContent = horas > 0
                            ? `Tienes ${horas} h ${minutos} min esperando`
                            : `Tienes ${minutos} min esperando`;
                    }

                    actualizar();
                    setInterval(actualizar, 30000);
                });
            }

            function aplicarFragmento(html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nuevo = doc.getElementById('detalle-papeleta');

                if (!nuevo) return false;

                contenedor.innerHTML = nuevo.innerHTML;
                iniciarTiempoEsperaWatcher();

                // Los mensajes flash (session status/error) van en el layout,
                // fuera de este contenedor — no hay navegación real así que
                // no se ven solos: se muestran como toast.
                const flashStatus = doc.getElementById('flash-status');
                const flashError = doc.getElementById('flash-error');
                if (flashStatus && window.toastAccion) window.toastAccion(flashStatus.textContent.trim(), 'ok');
                if (flashError && window.toastAccion) window.toastAccion(flashError.textContent.trim(), 'error');

                return true;
            }

            // Expuesto global: lo usa también el poller de "estadoEnVivo" de
            // arriba para refrescar sin recargar toda la página.
            window.refrescarDetallePapeleta = async function (mostrarToast) {
                try {
                    const res = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        cache: 'no-store',
                    });
                    if (!res.ok) return false;

                    const ok = aplicarFragmento(await res.text());
                    if (ok && mostrarToast && window.toastAccion) {
                        window.toastAccion('Papeleta actualizada.');
                    }
                    return ok;
                } catch (e) {
                    return false;
                }
            };

            // Delegación: cualquier <form> dentro del contenedor (aprobar,
            // rechazar, observar, responder observación, subir/quitar
            // adjunto) se manda por fetch en vez de navegar. Laravel no
            // cambia en nada — sigue redirigiendo con back()->with(...); el
            // fetch sigue ese redirect y trae el HTML normal de esta misma
            // página, del cual solo se toma el contenedor.
            contenedor.addEventListener('submit', function (e) {
                const form = e.target.closest('form');
                if (!form) return;

                e.preventDefault();
                e.stopPropagation();

                if (form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) {
                    return;
                }

                const boton = form.querySelector('button[type="submit"], button:not([type])');
                if (boton) boton.disabled = true;

                const datos = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: datos,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('respuesta-no-ok');
                        return res.text();
                    })
                    .then(function (html) {
                        if (!aplicarFragmento(html)) window.location.reload();
                    })
                    .catch(function () {
                        // Cualquier falla (sin red, sesión expirada, etc.):
                        // se hace el submit real como respaldo, sin perder
                        // la acción del usuario.
                        form.submit();
                    });
            });

            iniciarTiempoEsperaWatcher();
        })();
    </script>

    <script>
        function cooldownCodigo(segundosIniciales, codigo) {
            return {
                restante: segundosIniciales,
                listo: segundosIniciales <= 0,
                intervalo: null,

                init() {
                    if (this.listo) {
                        // El x-if hijo todavía no insertó el <canvas> en el
                        // DOM en este mismo tick — sin $nextTick,
                        // getElementById no lo encuentra y no dibuja nada.
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
                            // Colchón extra por si el x-if aún no montó el
                            // canvas ni con $nextTick.
                            if (reintentos > 0) setTimeout(() => intentar(reintentos - 1), 50);
                            return;
                        }

                        if (typeof window.dibujarQrPapeleta === 'function') {
                            window.dibujarQrPapeleta('qr-papeleta', codigo);
                        } else {
                            // "app.js" (type="module") puede no haber
                            // terminado de ejecutarse todavía.
                            document.addEventListener('DOMContentLoaded', () => intentar(reintentos), { once: true });
                        }
                    };
                    intentar(10);
                },
            };
        }
    </script>
</x-app-layout>
