{{--
    Aviso "esta papeleta se actualizó" + poller que consulta cada 5s si algo
    cambió (comparando updated_at). Solo se monta si la papeleta sigue viva
    (no está en un estado final), porque una vez finalizada/rechazada/etc.
    ya no tiene sentido seguir consultando.
--}}
<div x-data="estadoEnVivo(@js($papeleta->id))" x-init="init()">
    <div x-show="cambio"
         x-cloak
         x-transition
         class="mb-3 flex items-center justify-between glass-card !rounded-xl border-l-4 !border-l-brand-400 text-brand-800 text-sm px-4 py-3">
        <span class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
            Esta papeleta se actualizó. Recargando…
        </span>
        <button @click="window.refrescarDetallePapeleta ? window.refrescarDetallePapeleta(true) : window.location.reload()"
                class="font-semibold hover:underline shrink-0">
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

                    if (data.updated_at && data.updated_at !== this.ultimoUpdatedAt) {
                        this.cambio = true;
                        this.ultimoUpdatedAt = data.updated_at;

                        if (this.temporizador) {
                            clearInterval(this.temporizador);
                        }

                        // AJAX: se reemplaza el contenido en vivo (sin recargar
                        // la página entera) apenas 900ms después, para que se
                        // alcance a leer el aviso.
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
