<div x-data="{ abierto: false }" class="relative">

    {{-- Icono --}}
    <button
        @click="abierto = !abierto"
        type="button"
        class="relative flex items-center justify-center w-10 h-10 rounded-full glass
               text-gray-600 dark:text-gray-300 hover:text-gray-900 hover:bg-white/70 hover:-translate-y-0.5
               transition-all duration-200"
        :class="$store.notificaciones.noLeidas > 0 && 'animate-[pulse_2s_ease-in-out_1]'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
             class="w-5 h-5">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>

        <span
            x-show="$store.notificaciones.noLeidas > 0"
            x-transition.scale
            x-text="$store.notificaciones.noLeidas > 9 ? '9+' : $store.notificaciones.noLeidas"
            class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center
                   rounded-full bg-rose-500 text-white text-[10px] font-semibold
                   ring-2 ring-white/80 dark:ring-gray-900"
        ></span>
    </button>

    {{-- Panel --}}
    <div
        x-show="abierto"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.outside="abierto = false"
        x-cloak
        class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] overflow-hidden rounded-2xl glass-strong shadow-glass-lg"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/40">
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Notificaciones</span>
            <button
                x-show="$store.notificaciones.noLeidas > 0"
                @click="$store.notificaciones.marcarTodas()"
                class="text-xs font-medium text-brand-600 hover:text-brand-800 transition"
            >
                Marcar todas leídas
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-white/40">
            {{-- Skeleton loader: 3 filas fantasma mientras carga --}}
            <template x-if="$store.notificaciones.cargando">
                <div class="px-4 py-3 space-y-4">
                    <template x-for="i in 3" :key="i">
                        <div class="flex gap-3 animate-pulse">
                            <span class="mt-1 w-2 h-2 shrink-0 rounded-full bg-gray-300/60 dark:bg-gray-600/60"></span>
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <div class="h-2.5 rounded-full bg-gray-300/60 dark:bg-gray-600/60 w-3/4"></div>
                                <div class="h-2 rounded-full bg-gray-200/60 dark:bg-gray-700/60 w-full"></div>
                                <div class="h-2 rounded-full bg-gray-200/60 dark:bg-gray-700/60 w-1/3"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!$store.notificaciones.cargando && $store.notificaciones.notificaciones.length === 0">
                <div class="px-4 py-8 text-center text-sm text-gray-400">
                    Sin notificaciones por ahora
                </div>
            </template>

            <template x-for="n in $store.notificaciones.notificaciones" :key="n.id">
                <a
                    :href="n.papeleta_id ? `/papeletas/${n.papeleta_id}` : '#'"
                    @click="$store.notificaciones.marcarLeida(n)"
                    class="flex gap-3 px-4 py-3 hover:bg-white/60 dark:hover:bg-white/5 transition-colors"
                    :class="!n.leida_at && 'bg-brand-50/50 dark:bg-brand-500/10'"
                >
                    <span
                        class="mt-1.5 w-2 h-2 shrink-0 rounded-full"
                        :class="n.leida_at ? 'bg-transparent' : 'bg-brand-500'"
                    ></span>

                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="n.titulo"></p>
                        <p class="text-xs text-gray-500 line-clamp-2" x-text="n.mensaje"></p>
                        <p class="text-[11px] text-gray-400 mt-0.5" x-text="$store.notificaciones.formatoFecha(n.created_at)"></p>
                    </div>
                </a>
            </template>
        </div>
    </div>
</div>
