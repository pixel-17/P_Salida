{{--
    Notificación (no bloqueo): se muestra UNA SOLA VEZ en la vida del
    usuario, la primera vez que accede con must_change_password = true.
    El control de "una sola vez, para siempre" vive en
    User::marcarAvisoPasswordMostrado() (columna aviso_password_mostrado),
    no en sesión — así que ni cerrando/reabriendo sesión vuelve a salir.
    "Cancelar" solo cierra el modal; "Sí, cambiar" navega a la pantalla
    dedicada. Ninguna de las dos opciones afecta si vuelve a mostrarse:
    eso ya quedó decidido en cuanto se incluyó este partial.
--}}
<x-modal name="sugerencia-cambio-password" :show="true" focusable>
    <div class="p-6 text-center">
        <div class="mx-auto mb-3 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <h2 class="text-lg font-bold text-gray-800">Tu contraseña sigue siendo tu DNI</h2>
        <p class="mt-1 text-sm text-gray-500">
            ¿Quieres cambiarla ahora por una propia?
        </p>

        <div class="mt-6 flex justify-center gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">
                Cancelar
            </x-secondary-button>

            <a href="{{ route('password.forzado.edit') }}" class="btn-primary text-sm">
                Sí, cambiar
            </a>
        </div>
    </div>
</x-modal>
