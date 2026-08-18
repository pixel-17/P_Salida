{{--
    Barra inferior fija, solo en móvil (sm:hidden). Pensada para trabajador/
    jefe/RRHH que viven en el flujo de papeletas — el patrón de tab bar de
    app nativa es el que más rápido se aprende con el pulgar.

    El administrador se queda con la navegación superior de siempre: su uso
    es de escritorio/back-office, una tab bar ahí no aporta.
--}}
@php
    $usuario = auth()->user();
    $esAdmin = $usuario->hasRole(\App\Enums\RolUsuario::ADMINISTRADOR->value);
@endphp

@unless($esAdmin)
    <nav class="fixed bottom-0 inset-x-0 z-40 sm:hidden glass !rounded-none !border-x-0 !border-b-0"
         style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="grid grid-cols-3 h-16 max-w-md mx-auto">
            @if($usuario->esVigilante())
                <a href="{{ route('vigilancia.index') }}"
                   class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('vigilancia.*') ? 'text-brand-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('vigilancia.*') ? 2.25 : 1.75 }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                    </svg>
                    <span class="text-[10px] font-semibold">Garita</span>
                </a>
                <div></div>
                <a href="{{ route('profile.edit') }}"
                   class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('profile.*') ? 'text-brand-600' : 'text-gray-400' }}">
                    <span class="w-6 h-6 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-[10px] font-bold">
                        {{ strtoupper(substr($usuario->name, 0, 1)) }}
                    </span>
                    <span class="text-[10px] font-semibold">Perfil</span>
                </a>
            @else
                <a href="{{ route('papeletas.index') }}"
                   class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('papeletas.index') ? 'text-brand-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('papeletas.index') ? 2.25 : 1.75 }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    <span class="text-[10px] font-semibold">
                        {{ $usuario->esJefe() || $usuario->esRrhh() ? 'Bandeja' : 'Mis papeletas' }}
                    </span>
                </a>

                @can('crear', \App\Models\Papeleta::class)
                    <div class="relative flex items-center justify-center">
                        <a href="{{ route('papeletas.create') }}"
                           aria-label="{{ $usuario->esJefe() ? 'Mi papeleta' : 'Nueva papeleta' }}"
                           class="absolute -top-5 w-14 h-14 rounded-full flex items-center justify-center text-white shadow-glass transition-transform duration-200 active:scale-95"
                           style="background: linear-gradient(135deg, #3b6cf6 0%, #6d5bf0 100%);">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </a>
                    </div>
                @else
                    <div></div>
                @endcan

                <a href="{{ route('profile.edit') }}"
                   class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('profile.*') ? 'text-brand-600' : 'text-gray-400' }}">
                    <span class="w-6 h-6 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-[10px] font-bold">
                        {{ strtoupper(substr($usuario->name, 0, 1)) }}
                    </span>
                    <span class="text-[10px] font-semibold">Perfil</span>
                </a>
            @endif
        </div>
    </nav>
@endunless
