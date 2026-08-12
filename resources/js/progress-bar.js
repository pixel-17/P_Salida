// Barra de progreso de navegación, estilo NProgress, sin dependencias.
// Como la app es Blade renderizado en servidor (no SPA), esto solo da una
// señal visual inmediata al hacer clic — la barra queda "cargando" hasta
// que el navegador reemplaza la página con la respuesta del servidor.
(function () {
    const bar = document.createElement('div');
    bar.id = 'nav-progress-bar';
    Object.assign(bar.style, {
        position: 'fixed',
        top: '0',
        left: '0',
        height: '3px',
        width: '0%',
        zIndex: '9999',
        background: 'linear-gradient(90deg, #3b6cf6, #6d5bf0)',
        boxShadow: '0 0 8px rgba(59,108,246,0.6)',
        transition: 'width 0.3s ease, opacity 0.3s ease',
        opacity: '0',
    });
    document.addEventListener('DOMContentLoaded', () => document.body.appendChild(bar));

    let activo = false;

    function iniciar() {
        if (activo) return;
        activo = true;
        bar.style.opacity = '1';
        bar.style.width = '20%';
        requestAnimationFrame(() => {
            setTimeout(() => { if (activo) bar.style.width = '60%'; }, 150);
            setTimeout(() => { if (activo) bar.style.width = '85%'; }, 500);
        });
    }

    // Enlaces internos (que no abren en pestaña nueva ni son anclas/externos)
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;
        if (link.href && link.href.startsWith(window.location.origin) && !link.href.includes('#')) {
            iniciar();
        }
    });

    // Envíos de formulario (filtros, acciones de aprobación, etc.)
    document.addEventListener('submit', () => iniciar());

    window.addEventListener('pageshow', () => {
        activo = false;
        bar.style.width = '100%';
        setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0%'; }, 200);
    });
})();
