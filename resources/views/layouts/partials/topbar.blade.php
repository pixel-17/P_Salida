<header class="app-topbar">
    <div class="flex items-center gap-3 min-w-0">
        <button @click="sidebarAbierto = true" class="icon-btn lg:hidden shrink-0" aria-label="Abrir menú">
            <x-icon name="menu" class="w-5 h-5" />
        </button>

        <div class="min-w-0">
            @if (isset($header))
                {{ $header }}
            @else
                <h1 class="font-bold text-lg text-gray-800">{{ config('app.name', 'Papeletas') }}</h1>
            @endif
        </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <x-campana-notificaciones />

        <x-dropdown align="right" width="w-64">
            <x-slot name="trigger">
                <button class="inline-flex items-center gap-2 pl-1.5 pr-2.5 sm:pr-3 py-1.5 rounded-full border border-transparent hover:border-slate-200 hover:bg-slate-50 transition-colors">
                    <span class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[10rem] truncate">{{ Auth::user()->name }}</span>
                    <x-icon name="chevron-down" class="hidden sm:block w-4 h-4 text-gray-400" />
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Mi perfil') }}
                </x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
