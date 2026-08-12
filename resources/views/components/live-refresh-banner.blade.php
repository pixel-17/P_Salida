{{--
    Detecta cambios en una tabla sin forzar recarga. Si nadie tocó nada,
    no pasa nada visible — solo cuando hay un cambio real aparece el
    banner para que el usuario decida actualizar.

    Uso: <x-live-refresh-banner tabla="areas" :count="$areas->total()" />
--}}
@props(['tabla', 'count'])

<div x-data="liveRefresh('{{ $tabla }}', {{ $count }})" x-init="init()">
    <div x-show="hayNovedades"
         x-cloak
         x-transition
         class="mb-3 flex items-center justify-between glass-card !rounded-xl border-l-4 !border-l-brand-400 text-brand-800 text-sm px-4 py-3">
        <span class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
            Hay cambios nuevos en esta lista.
        </span>
        <button @click="window.location.reload()" class="font-semibold hover:underline shrink-0">
            Actualizar
        </button>
    </div>
</div>

<script>
    function liveRefresh(tabla, countInicial) {
        return {
            hayNovedades: false,
            init() {
                setInterval(async () => {
                    if (this.hayNovedades || document.hidden) return;
                    try {
                        const res = await fetch(`/admin/live-check/${tabla}`, {
                            headers: { Accept: 'application/json' },
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data.count !== countInicial) this.hayNovedades = true;
                    } catch (e) {
                        // en espera, sin romper la UI si falla la red
                    }
                }, 15000);
            },
        };
    }
</script>
