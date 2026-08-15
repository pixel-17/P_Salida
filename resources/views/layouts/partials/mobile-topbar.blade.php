<header class="mobile-topbar">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
        <div class="w-8 h-8 rounded-lg sidebar-brand-mark flex items-center justify-center shrink-0">
            <x-application-logo class="block h-4 w-auto fill-current text-white" />
        </div>
        <span class="font-bold text-gray-800 text-[15px] truncate">{{ $tituloTopbar ?? 'Papeletas' }}</span>
    </a>

    <div class="flex items-center gap-1.5 shrink-0">
        <x-campana-notificaciones />

        <button
            x-data="{ oscuro: document.documentElement.classList.contains('dark') }"
            @click="oscuro = !oscuro; document.documentElement.classList.toggle('dark', oscuro); localStorage.setItem('tema', oscuro ? 'oscuro' : 'claro')"
            type="button"
            class="icon-btn"
            aria-label="Cambiar tema"
        >
            <x-icon x-show="!oscuro" name="sun" class="w-5 h-5" />
            <x-icon x-show="oscuro" x-cloak name="moon" class="w-5 h-5" />
        </button>

        <x-dropdown align="right" width="w-56">
            <x-slot name="trigger">
                <button class="icon-btn" aria-label="Cuenta">
                    <span class="w-7 h-7 rounded-full avatar-mark flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
                <x-dropdown-link :href="route('profile.edit')">
                    Mi perfil
                </x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Cerrar sesión
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
