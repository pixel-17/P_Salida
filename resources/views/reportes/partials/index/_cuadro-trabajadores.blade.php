{{-- =========================================================
     CUADRO DINÁMICO: RANKING POR TRABAJADOR
     Tabla buscable y ordenable (100% cliente, vía Alpine) con el
     total de salidas, el motivo más recurrente y las horas fuera
     de cada trabajador. RRHH la ve global; Jefe solo su equipo
     (el alcance ya viene resuelto desde el controlador).
========================================================== --}}
<div class="glass-panel p-5 sm:p-6 mb-4 animate-fade-in-up"
     x-data="{
        filas: {{ Illuminate\Support\Js::from($cuadroTrabajadores) }},
        busqueda: '',
        orden: { campo: 'total', asc: false },
        ordenarPor(campo) {
            if (this.orden.campo === campo) {
                this.orden.asc = !this.orden.asc;
            } else {
                this.orden = { campo, asc: false };
            }
        },
        get filasVisibles() {
            const texto = this.busqueda.trim().toLowerCase();
            let lista = this.filas;

            if (texto) {
                lista = lista.filter(f =>
                    f.nombre.toLowerCase().includes(texto) ||
                    f.area.toLowerCase().includes(texto) ||
                    f.jefe.toLowerCase().includes(texto) ||
                    f.motivo_top.toLowerCase().includes(texto)
                );
            }

            const campo = this.orden.campo;
            const asc = this.orden.asc;

            return [...lista].sort((a, b) => {
                const va = a[campo];
                const vb = b[campo];

                if (typeof va === 'string') {
                    return asc ? va.localeCompare(vb) : vb.localeCompare(va);
                }

                return asc ? va - vb : vb - va;
            });
        },
     }">

    {{-- Encabezado + buscador --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h3 class="font-bold text-base text-gray-800 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white text-sm shrink-0 shadow-glass">
                    🏆
                </span>
                Ranking por trabajador
            </h3>
            <p class="text-xs text-gray-400 mt-1">
                Total de salidas, motivo más recurrente y horas fuera de cada trabajador
                {{ $esSoloJefe ? 'de tu equipo' : '(todas las áreas)' }}. Haz clic en una columna para ordenar.
            </p>
        </div>

        <div class="relative w-full sm:w-72 shrink-0">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input type="text"
                   x-model="busqueda"
                   placeholder="Buscar trabajador, área o motivo…"
                   class="input-glass !py-2 !pl-9 text-sm w-full">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 border-b border-white/60">
                    <th class="py-2 pr-3 font-semibold w-8">#</th>

                    <th class="py-2 pr-3 font-semibold cursor-pointer select-none hover:text-gray-700 whitespace-nowrap"
                        @click="ordenarPor('nombre')">
                        Trabajador
                        <span x-show="orden.campo === 'nombre'" x-text="orden.asc ? '↑' : '↓'" x-cloak></span>
                    </th>

                    <th class="py-2 pr-3 font-semibold cursor-pointer select-none hover:text-gray-700 whitespace-nowrap"
                        @click="ordenarPor('area')">
                        Área
                        <span x-show="orden.campo === 'area'" x-text="orden.asc ? '↑' : '↓'" x-cloak></span>
                    </th>

                    @unless($esSoloJefe)
                        <th class="py-2 pr-3 font-semibold cursor-pointer select-none hover:text-gray-700 whitespace-nowrap"
                            @click="ordenarPor('jefe')">
                            Jefe
                            <span x-show="orden.campo === 'jefe'" x-text="orden.asc ? '↑' : '↓'" x-cloak></span>
                        </th>
                    @endunless

                    <th class="py-2 pr-3 font-semibold cursor-pointer select-none hover:text-gray-700 text-right whitespace-nowrap"
                        @click="ordenarPor('total')">
                        Salidas
                        <span x-show="orden.campo === 'total'" x-text="orden.asc ? '↑' : '↓'" x-cloak></span>
                    </th>

                    <th class="py-2 pr-3 font-semibold min-w-[200px]">
                        Motivo más recurrente
                    </th>

                    <th class="py-2 pr-3 font-semibold cursor-pointer select-none hover:text-gray-700 text-right whitespace-nowrap"
                        @click="ordenarPor('horas_fuera')">
                        Horas fuera
                        <span x-show="orden.campo === 'horas_fuera'" x-text="orden.asc ? '↑' : '↓'" x-cloak></span>
                    </th>

                    <th class="py-2 pr-0 font-semibold text-right whitespace-nowrap">
                        Última salida
                    </th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(fila, index) in filasVisibles" :key="fila.nombre + '-' + index">
                    <tr class="border-b border-white/40 last:border-0 hover:bg-brand-50/50 transition-colors">
                        <td class="py-2.5 pr-3 text-center">
                            <span x-show="index === 0" x-cloak>🥇</span>
                            <span x-show="index === 1" x-cloak>🥈</span>
                            <span x-show="index === 2" x-cloak>🥉</span>
                            <span class="text-gray-400" x-show="index > 2" x-cloak x-text="index + 1"></span>
                        </td>

                        <td class="py-2.5 pr-3 font-semibold text-gray-800 whitespace-nowrap" x-text="fila.nombre"></td>
                        <td class="py-2.5 pr-3 text-gray-600 whitespace-nowrap" x-text="fila.area"></td>

                        @unless($esSoloJefe)
                            <td class="py-2.5 pr-3 text-gray-600 whitespace-nowrap" x-text="fila.jefe"></td>
                        @endunless

                        <td class="py-2.5 pr-3 text-right font-bold text-gray-800" x-text="fila.total"></td>

                        <td class="py-2.5 pr-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-gray-700 truncate max-w-[140px]" x-text="fila.motivo_top"></span>
                                <span class="text-[11px] text-gray-400 whitespace-nowrap"
                                      x-text="fila.motivo_top_total + '/' + fila.total + ' · ' + fila.motivo_top_pct + '%'"></span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100 w-32 max-w-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full"
                                     :style="`width: ${fila.motivo_top_pct}%`"></div>
                            </div>
                        </td>

                        <td class="py-2.5 pr-3 text-right font-semibold text-gray-700 whitespace-nowrap" x-text="fila.horas_fuera + 'h'"></td>
                        <td class="py-2.5 pr-0 text-right text-gray-500 whitespace-nowrap" x-text="fila.ultima_salida ?? '—'"></td>
                    </tr>
                </template>

                <tr x-show="filasVisibles.length === 0" x-cloak>
                    <td colspan="8" class="py-6 text-center text-gray-400">
                        No hay trabajadores que coincidan con la búsqueda.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
