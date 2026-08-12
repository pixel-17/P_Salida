{{--
    Toast liviano para feedback de acciones enviadas por fetch (aprobar,
    rechazar, observar, responder, adjuntar, confirmar en garita...). Se monta
    una sola vez en el layout y queda accesible como window.toastAccion(msg,
    tipo), igual de simple que window.dibujarQrPapeleta.

    Independiente de x-notification-toast (ese es para notificaciones reales
    del sistema, con su propio store y navegación al hacer clic); este es
    solo un "listo" / "algo falló" que se autodestruye.
--}}
<div
    x-data="{
        toasts: [],
        agregar(mensaje, tipo) {
            if (!mensaje) return;
            const id = Date.now() + Math.random();
            this.toasts.push({ id, mensaje, tipo: tipo || 'ok' });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 3200);
        },
    }"
    x-init="window.toastAccion = (mensaje, tipo) => agregar(mensaje, tipo)"
    class="fixed z-[100] left-1/2 -translate-x-1/2 flex flex-col gap-2 items-center px-3 w-full sm:w-auto pointer-events-none"
    style="bottom: calc(4.5rem + env(safe-area-inset-bottom));"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="glass-strong rounded-full pl-3 pr-4 py-2 text-sm font-semibold shadow-glass flex items-center gap-2 max-w-[92vw] sm:max-w-sm"
            :class="toast.tipo === 'error' ? 'text-rose-700' : 'text-emerald-700'"
        >
            <svg x-show="toast.tipo !== 'error'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <svg x-show="toast.tipo === 'error'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="truncate" x-text="toast.mensaje"></span>
        </div>
    </template>
</div>
