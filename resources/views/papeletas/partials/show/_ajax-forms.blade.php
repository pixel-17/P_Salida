{{--
    Contenedor "vivo": todas las acciones (aprobar/rechazar/observar/
    responder/adjuntar/cancelar) se interceptan y se envían por fetch en vez
    de hacer un POST con navegación completa. Laravel sigue respondiendo
    normal (redirect 302 -> GET show), así que no hace falta ninguna vista o
    endpoint nuevo: se pide esa misma página por fetch y se reemplaza solo
    el contenedor #detalle-papeleta con el HTML fresco. Si algo falla (sin
    red, sesión expirada, etc.) se hace un fallback a navegación real, así
    la acción nunca se pierde.
--}}
<script>
    (function () {
        const contenedor = document.getElementById('detalle-papeleta');
        if (!contenedor) return;

        function iniciarTiempoEsperaWatcher() {
            contenedor.querySelectorAll('[data-tiempo-espera]').forEach(function (el) {
                if (el.dataset.timerAttached) return;
                el.dataset.timerAttached = '1';

                const desde = new Date(el.dataset.desde);

                function actualizar() {
                    const diffMs = Math.max(0, Date.now() - desde.getTime());
                    const minutosTotales = Math.floor(diffMs / 60000);
                    const horas = Math.floor(minutosTotales / 60);
                    const minutos = minutosTotales % 60;

                    el.textContent = horas > 0
                        ? `Tienes ${horas} h ${minutos} min esperando`
                        : `Tienes ${minutos} min esperando`;
                }

                actualizar();
                setInterval(actualizar, 30000);
            });
        }

        function aplicarFragmento(html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nuevo = doc.getElementById('detalle-papeleta');

            if (!nuevo) return false;

            contenedor.innerHTML = nuevo.innerHTML;
            iniciarTiempoEsperaWatcher();

            // El mensaje flash de éxito (session status) va en el layout,
            // fuera de este contenedor — no hay navegación real así que no
            // se ve solo: se muestra como toast. Los errores de validación
            // ya vienen renderizados campo por campo dentro del fragmento
            // que se acaba de inyectar arriba (contenedor.innerHTML), así
            // que no hace falta un toast aparte para ellos.
            const flashStatus = doc.getElementById('flash-status');
            if (flashStatus && window.toastAccion) {
                window.toastAccion(flashStatus.textContent.trim(), 'ok');
            }

            return true;
        }

        // Expuesto global: lo usa también el poller de "estadoEnVivo" para
        // refrescar sin recargar toda la página.
        window.refrescarDetallePapeleta = async function (mostrarToast) {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    cache: 'no-store',
                });
                if (!res.ok) return false;

                const ok = aplicarFragmento(await res.text());
                if (ok && mostrarToast && window.toastAccion) {
                    window.toastAccion('Papeleta actualizada.');
                }
                return ok;
            } catch (e) {
                return false;
            }
        };

        // Delegación: cualquier <form> dentro del contenedor (aprobar,
        // rechazar, observar, responder observación, cancelar,
        // subir/quitar adjunto) se manda por fetch en vez de navegar.
        // Laravel no cambia en nada — sigue redirigiendo con
        // back()->with(...); el fetch sigue ese redirect y trae el HTML
        // normal de esta misma página, del cual solo se toma el contenedor.
        contenedor.addEventListener('submit', function (e) {
            const form = e.target.closest('form');
            if (!form) return;

            e.preventDefault();
            e.stopPropagation();

            if (form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) {
                return;
            }

            const boton = form.querySelector('button[type="submit"], button:not([type])');
            if (boton) boton.disabled = true;

            const datos = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: datos,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('respuesta-no-ok');
                    return res.text();
                })
                .then(function (html) {
                    if (!aplicarFragmento(html)) window.location.reload();
                })
                .catch(function () {
                    // Cualquier falla (sin red, sesión expirada, etc.): se
                    // hace el submit real como respaldo, sin perder la
                    // acción del usuario.
                    form.submit();
                });
        });

        iniciarTiempoEsperaWatcher();
    })();
</script>
