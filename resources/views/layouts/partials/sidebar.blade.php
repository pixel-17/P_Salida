{{--
    Sidebar de escritorio, reutilizada por Admin / RRHH / Jefe de Área.
    Recibe:
      - $secciones: array de secciones, cada una ['label' => ?string, 'items' => [...]]
        cada item: ['label', 'route', 'active' (patrón para request()->routeIs), 'icon', 'badge' => ?int]
      - $rolLabel: texto mostrado bajo el nombre del sistema (p.ej. "Panel RRHH")
--}}
<div
    x-show="sidebarAbierto"
    x-cloak
    class="sidebar-overlay lg:hidden"
    @click="sidebarAbierto = false"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
></div>

<aside class="app-sidebar" :class="sidebarAbierto && 'is-open'">
    <div class="sidebar-brand">
        <div class="w-9 h-9 rounded-xl sidebar-brand-mark flex items-center justify-center shrink-0">
            <x-application-logo class="block h-5 w-auto fill-current text-white" />
        </div>
        <div class="min-w-0">
            <p class="text-sm font-bold text-white leading-tight truncate">Papeletas</p>
            <p class="text-xs text-gray-500 truncate">{{ $rolLabel }}</p>
        </div>
        <button @click="sidebarAbierto = false" class="ml-auto text-gray-400 hover:text-white lg:hidden" aria-label="Cerrar menú">
            <x-icon name="close" class="w-5 h-5" />
        </button>
    </div>

    <nav class="sidebar-nav">
        @foreach ($secciones as $seccion)
            @if(!empty($seccion['label']))
                <p class="sidebar-section-label">{{ $seccion['label'] }}</p>
            @endif

            @foreach ($seccion['items'] as $item)
                @php
                    $activo = is_bool($item['active']) ? $item['active'] : request()->routeIs(...(array) $item['active']);
                @endphp
                <a href="{{ $item['route'] }}"
                   class="sidebar-link {{ $activo ? 'is-active' : '' }}">
                    <x-icon :name="$item['icon']" class="sidebar-icon" />
                    <span>{{ $item['label'] }}</span>
                    @if(!empty($item['badge']))
                        <span class="sidebar-badge">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <button
            x-data="{ oscuro: document.documentElement.classList.contains('dark') }"
            @click="oscuro = !oscuro; document.documentElement.classList.toggle('dark', oscuro); localStorage.setItem('tema', oscuro ? 'oscuro' : 'claro')"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-400 hover:text-white hover:bg-white/5 transition-colors"
        >
            <x-icon x-show="!oscuro" name="sun" class="w-5 h-5 shrink-0" />
            <x-icon x-show="oscuro" x-cloak name="moon" class="w-5 h-5 shrink-0" />
            <span x-text="oscuro ? 'Modo claro' : 'Modo oscuro'"></span>
        </button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); this.closest('form').submit();"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                <x-icon name="logout" class="w-5 h-5 shrink-0" />
                <span>Cerrar sesión</span>
            </a>
        </form>
    </div>
</aside>
