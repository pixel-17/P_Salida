{{--
    Campana de notificaciones.
    - Hace polling a /notificaciones cada 20s.
    - Si el conteo de no leídas sube respecto al último chequeo, suena un beep
      corto sintetizado con Web Audio API (sin archivo de audio externo).
    - Clic en una notificación la marca como leída y navega a la papeleta.
--}}
<div x-data="notificacionesBell()" x-init="init()" class="relative">
    <button @click="abierto = !abierto" class="relative p-2 rounded-full glass hover:bg-white/70 transition-all duration-200">
        <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>

        <span x-show="noLeidas > 0"
              x-text="noLeidas > 9 ? '9+' : noLeidas"
              x-cloak
              class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"></span>
    </button>

    <div x-show="abierto"
         x-cloak
         @click.away="abierto = false"
         x-transition
         class="absolute right-0 mt-2 w-80 max-w-[90vw] glass-strong rounded-2xl shadow-glass-lg z-50">

        <div class="flex justify-between items-center px-4 py-3 border-b border-white/40">
            <span class="font-semibold text-sm">Notificaciones</span>
            <button @click="marcarTodasLeidas()" x-show="noLeidas > 0" class="text-xs text-blue-600 hover:underline">
                Marcar todas como leídas
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-white/40">
            <template x-if="items.length === 0">
                <p class="text-center text-sm text-gray-400 py-8">No tienes notificaciones.</p>
            </template>

            <template x-for="item in items" :key="item.id">
                <a :href="item.papeleta_id ? `/papeletas/${item.papeleta_id}` : '#'"
                   @click="marcarLeida(item)"
                   class="block px-4 py-3 hover:bg-white/60 transition-colors"
                   :class="!item.leida_at ? 'bg-blue-50/60' : ''">
                    <div class="flex items-start gap-2">
                        <span class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="!item.leida_at ? 'bg-blue-500' : 'bg-transparent'"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate" x-text="item.titulo"></p>
                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2" x-text="item.mensaje"></p>
                            <p class="text-[11px] text-gray-400 mt-1" x-text="tiempoRelativo(item.created_at)"></p>
                        </div>
                    </div>
                </a>
            </template>
        </div>
    </div>
</div>

<script>
    function notificacionesBell() {
        return {
            items: [],
            noLeidas: 0,
            abierto: false,
            audioCtx: null,

            init() {
                this.cargar();
                setInterval(() => this.cargar(), 20000);
            },

            async cargar() {
                try {
                    const res = await fetch('{{ route('notificaciones.index') }}', {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;

                    const data = await res.json();
                    const antes = this.noLeidas;

                    this.items = data.notificaciones;
                    this.noLeidas = data.no_leidas;

                    if (this.noLeidas > antes) {
                        this.sonarBeep();
                    }
                } catch (e) {
                    // Silencioso: un fallo de red no debe interrumpir la UI.
                }
            },

            async marcarLeida(item) {
                if (item.leida_at) return;

                item.leida_at = new Date().toISOString();
                this.noLeidas = Math.max(0, this.noLeidas - 1);

                await fetch(`/notificaciones/${item.id}/leida`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
            },

            async marcarTodasLeidas() {
                this.items.forEach(i => i.leida_at = i.leida_at || new Date().toISOString());
                this.noLeidas = 0;

                await fetch('{{ route('notificaciones.leidas') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
            },

            sonarBeep() {
                try {
                    this.audioCtx ??= new (window.AudioContext || window.webkitAudioContext)();

                    const osc = this.audioCtx.createOscillator();
                    const gain = this.audioCtx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, this.audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.15, this.audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 0.35);

                    osc.connect(gain);
                    gain.connect(this.audioCtx.destination);

                    osc.start();
                    osc.stop(this.audioCtx.currentTime + 0.35);
                } catch (e) {
                    // Algunos navegadores bloquean audio sin interacción previa del usuario; se ignora.
                }
            },

            tiempoRelativo(fecha) {
                const segundos = Math.floor((new Date() - new Date(fecha)) / 1000);
                if (segundos < 60) return 'hace un momento';
                if (segundos < 3600) return `hace ${Math.floor(segundos / 60)} min`;
                if (segundos < 86400) return `hace ${Math.floor(segundos / 3600)} h`;
                return `hace ${Math.floor(segundos / 86400)} d`;
            },
        };
    }
</script>
