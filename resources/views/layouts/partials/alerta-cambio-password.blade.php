{{--
    Alerta flotante (NO modal, NO bloquea nada): se muestra UNA SOLA VEZ en
    la vida del usuario, la primera vez que accede con must_change_password
    = true. El control de "una sola vez, para siempre" vive en
    User::marcarAvisoPasswordMostrado() (columna aviso_password_mostrado),
    no en sesión — así que ni cerrando/reabriendo sesión vuelve a salir.

    Es solo una sugerencia: el usuario puede cerrarla con la X y seguir
    trabajando con normalidad, o tocar "Cambiar contraseña" para ir a la
    pantalla dedicada. Ninguna de las dos opciones afecta si vuelve a
    mostrarse: eso ya quedó decidido en cuanto se incluyó este partial.
--}}
<div
    x-data="{ visible: true }"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:translate-x-6"
    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed z-50 bottom-4 inset-x-3 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-96"
>
    <div class="glass-strong rounded-2xl shadow-glass-lg p-4 flex gap-3 items-start">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900">Tu contraseña sigue siendo tu DNI</p>
            <p class="text-xs text-gray-500 mt-0.5">¿Quieres cambiarla ahora por una propia? Puedes hacerlo cuando quieras.</p>

            <div class="mt-3 flex items-center gap-3">
                <a href="{{ route('password.forzado.edit') }}" class="btn-primary text-xs !px-3 !py-1.5">
                    Cambiar contraseña
                </a>
                <button type="button" @click="visible = false" class="text-xs text-gray-400 hover:text-gray-600">
                    Ahora no
                </button>
            </div>
        </div>

        <button type="button" @click="visible = false" class="text-gray-300 hover:text-gray-500 shrink-0 -mt-1 -mr-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
