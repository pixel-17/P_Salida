{{--
    Banner tipo notificación push (estilo WhatsApp/celular). Se monta una
    sola vez, global, en el layout. Reacciona a $store.notificaciones.toasts.
--}}
<div class="fixed top-4 inset-x-0 sm:inset-x-auto sm:right-4 z-[100] flex flex-col items-center sm:items-end gap-2 px-3 sm:px-0 pointer-events-none">
    <template x-for="toast in $store.notificaciones.toasts" :key="toast._key">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-3 sm:translate-y-0 sm:translate-x-6 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$store.notificaciones.marcarLeida(toast); window.location.href = toast.papeleta_id ? `/papeletas/${toast.papeleta_id}` : '/dashboard'"
             class="pointer-events-auto w-full sm:w-96 glass-strong rounded-2xl shadow-glass-lg p-3.5 flex gap-3 cursor-pointer hover:-translate-y-0.5 transition-all duration-200">

            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shrink-0 text-white shadow-glass">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex justify-between items-start gap-2">
                    <p class="text-sm font-semibold text-gray-900 truncate" x-text="toast.titulo"></p>
                    <button @click.stop="$store.notificaciones.cerrarToast(toast._key)" class="text-gray-300 hover:text-gray-500 shrink-0 -mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2" x-text="toast.mensaje"></p>
                <p class="text-[11px] text-gray-400 mt-1">Papeletas · ahora</p>
            </div>
        </div>
    </template>
</div>
