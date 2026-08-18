{{--
    Toast de ACCIÓN: feedback inmediato de algo que el propio usuario
    acaba de hacer sin recarga completa (aprobar/rechazar/observar,
    confirmar salida/retorno en garita, subir un documento, etc.).

    Distinto de <x-notification-toast />, que muestra avisos que llegan
    del servidor (nuevas notificaciones para el usuario, vía polling).
    Este es puramente local: no depende de Alpine ni de ningún store,
    para poder llamarse igual desde JS plano (_ajax-forms.blade.php) y
    desde componentes Alpine (vigilancia/index.blade.php).

    Se monta una sola vez, global, en el layout, y expone
    `window.toastAccion(mensaje, tipo)` — tipo: 'ok' (default) | 'error'.

    OJO: antes se llamaba a `window.toastAccion(...)` en tres sitios sin
    que existiera en ningún lado — por eso ninguna de esas acciones
    mostraba jamás una confirmación ni un error (p. ej. el vigilante no
    veía "Ya pasó el horario límite…" al marcar fuera de regla, y subir
    un documento no avisaba nada). Este componente es lo que faltaba.
--}}
<div id="toast-accion-contenedor"
     class="fixed bottom-20 sm:bottom-6 inset-x-0 sm:inset-x-auto sm:right-4 z-[110] flex flex-col items-center sm:items-end gap-2 px-3 sm:px-0 pointer-events-none"
     aria-live="polite"></div>

<script>
    window.toastAccion = function (mensaje, tipo) {
        mensaje = (mensaje || '').trim();
        if (!mensaje) return;

        tipo = tipo === 'error' ? 'error' : 'ok';

        const contenedor = document.getElementById('toast-accion-contenedor');
        if (!contenedor) return;

        const toast = document.createElement('div');
        toast.className = [
            'pointer-events-auto w-full sm:w-96 glass-strong rounded-2xl shadow-glass-lg',
            'p-3.5 flex items-start gap-2.5 border-l-4',
            'transition-all duration-300 ease-out opacity-0 -translate-y-2 sm:translate-y-0 sm:translate-x-4',
            tipo === 'error' ? '!border-l-rose-400' : '!border-l-emerald-400',
        ].join(' ');

        const icono = document.createElement('span');
        icono.className = 'text-base shrink-0 leading-5';
        icono.textContent = tipo === 'error' ? '⚠️' : '✅';

        const texto = document.createElement('p');
        texto.className = 'text-sm text-gray-700 leading-snug flex-1';
        texto.textContent = mensaje;

        const cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.setAttribute('aria-label', 'Cerrar aviso');
        cerrar.className = 'text-gray-300 hover:text-gray-500 shrink-0 -mt-0.5';
        cerrar.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';

        toast.append(icono, texto, cerrar);
        contenedor.appendChild(toast);

        function quitar() {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 250);
        }

        cerrar.addEventListener('click', quitar);

        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', '-translate-y-2', 'sm:translate-x-4');
        });

        // Los errores se quedan un poco más: suelen traer una explicación
        // (p. ej. "Ya pasó el horario límite para registrar salidas…")
        // que vale la pena dejar leer con calma.
        setTimeout(quitar, tipo === 'error' ? 7000 : 4000);
    };
</script>
